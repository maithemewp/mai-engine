<?php
declare(strict_types=1);

/**
 * Mai Post Grid query optimizer: the SQL.
 *
 * Rebuilds the taxonomy SQL WordPress wrote for a query, with WordPress's own WP_Tax_Query
 * code, and writes the faster EXISTS form from it. Mai never parses SQL: every piece comes
 * from WordPress, and the swap is three exact text replacements on the finished statement.
 * Anything unexpected returns null, so the caller sends today's statement unchanged.
 *
 * @package BizBudding\MaiEngine
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

final class Mai_Post_Grid_Query_Optimizer_Sql {

	/**
	 * The post columns a grid may sort by, before the ID tiebreaker. Only the sorts that met the
	 * speed bar are here. Modified date, title, slug, menu order and comment count sorts ran slower
	 * swapped on a mid-size category, and parent and type sorts were never measured, so those grids
	 * keep today's statement (spec "Which grids").
	 */
	public const SORT_COLUMNS = [ 'post_date', 'post_author', 'ID' ];

	/**
	 * Written right after SELECT inside each EXISTS on MySQL. It forbids the one plan that hits
	 * MySQL bug 120943 on 8.0 and 8.4, and nothing else.
	 */
	private const HINT = '/*+ NO_SEMIJOIN(DUPSWEEDOUT) */ ';

	/**
	 * Rebuilds the taxonomy join and condition WordPress wrote for a tax query.
	 *
	 * Uses a fresh WP_Tax_Query, because WordPress's own object never resets its list of table
	 * names, so writing its SQL again names the tables differently. Each filter's SQL comes from
	 * the public get_sql_for_clause(), called the way get_sql_for_query() calls it at the top
	 * level, then joined back together with core's exact separators.
	 *
	 * Returns null when the shape is not covered: nested filters, a filter whose terms or
	 * taxonomy no longer exist (core writes `0 = 1`), no filter with a join, or OR filters that
	 * are not all IN on one shared table.
	 *
	 * @param WP_Tax_Query $tax_query   The query's tax query, as WP_Query used it.
	 * @param string       $posts_table The posts table name.
	 *
	 * @return array{join:string,where:string,relation:string,pieces:list<array{where:string,alias:?string}>}|null
	 */
	public static function rebuild( WP_Tax_Query $tax_query, string $posts_table ): ?array {
		$fresh                    = new WP_Tax_Query( $tax_query->queries );
		$fresh->primary_table     = $posts_table;
		$fresh->primary_id_column = 'ID';

		// A copy, looped by reference as core does, so each filter can leave its table name for
		// the filters after it.
		$queries   = $fresh->queries;
		$relation  = 'AND';
		$joins     = [];
		$pieces    = [];
		$operators = [];

		foreach ( $queries as $key => &$clause ) {
			if ( 'relation' === $key ) {
				$relation = $queries['relation'];
				continue;
			}

			if ( ! is_array( $clause ) ) {
				continue;
			}

			if ( ! self::is_first_order_clause( $clause ) ) {
				return null;
			}

			$operator    = is_scalar( $clause['operator'] ?? null ) ? strtoupper( (string) $clause['operator'] ) : '';
			$operators[] = $operator;
			$sql         = $fresh->get_sql_for_clause( $clause, $queries );

			if ( count( $sql['where'] ) > 1 || in_array( '0 = 1', $sql['where'], true ) ) {
				return null;
			}

			$joins = array_merge( $joins, $sql['join'] );
			$where = $sql['where'][0] ?? '';

			if ( '' === $where ) {
				continue;
			}

			$pieces[] = [
				'where' => $where,
				'alias' => 'IN' === $operator && is_string( $clause['alias'] ?? null ) ? $clause['alias'] : null,
			];
		}

		unset( $clause );

		$joins = array_unique( array_filter( $joins ) );

		if ( ! $joins ) {
			return null;
		}

		if ( 'OR' === $relation && ( 1 !== count( $joins ) || array_diff( $operators, [ 'IN' ] ) ) ) {
			return null;
		}

		return [
			'join'     => implode( ' ', $joins ),
			'where'    => ' AND ( ' . "\n  " . implode( " \n  {$relation} \n  ", array_column( $pieces, 'where' ) ) . "\n)",
			'relation' => $relation,
			'pieces'   => $pieces,
		];
	}

	/**
	 * Writes the EXISTS condition that replaces WordPress's taxonomy condition.
	 *
	 * AND: each IN filter becomes its own EXISTS on the table name WordPress gave it, and the
	 * other filters stay as WordPress wrote them, all directly in the top-level AND. OR: the
	 * whole WordPress condition, brackets included, goes inside one EXISTS.
	 *
	 * @param array  $rebuilt     What rebuild() returned.
	 * @param string $posts_table The posts table name.
	 * @param string $term_table  The term relationships table name.
	 * @param bool   $hint        Whether to write the MySQL hint.
	 *
	 * @return string
	 */
	public static function condition( array $rebuilt, string $posts_table, string $term_table, bool $hint ): string {
		$select = 'SELECT ' . ( $hint ? self::HINT : '' ) . '1 FROM ';

		if ( 'OR' === $rebuilt['relation'] ) {
			return ' AND EXISTS ( ' . $select . $term_table . ' WHERE ' . $term_table . '.object_id = ' . $posts_table . '.ID AND ' . substr( $rebuilt['where'], 5 ) . ' )';
		}

		$parts = [];

		foreach ( $rebuilt['pieces'] as $piece ) {
			$alias = $piece['alias'];

			if ( null === $alias ) {
				$parts[] = $piece['where'];
				continue;
			}

			$from    = $alias === $term_table ? $term_table : "{$term_table} AS {$alias}";
			$parts[] = "EXISTS ( {$select}{$from} WHERE {$alias}.object_id = {$posts_table}.ID AND {$piece['where']} )";
		}

		return ' AND ' . implode( ' AND ', $parts );
	}

	/**
	 * Swaps a finished statement for the EXISTS form.
	 *
	 * Removes the taxonomy join, puts the condition in place of the taxonomy condition, and
	 * removes `GROUP BY {posts}.ID`. Returns null unless each of the three is non-empty and
	 * appears exactly once, and the condition is non-empty, so the filter is never dropped.
	 *
	 * @param string $statement   The statement WordPress finished.
	 * @param array  $rebuilt     What rebuild() returned.
	 * @param string $condition   What condition() returned.
	 * @param string $posts_table The posts table name.
	 *
	 * @return string|null
	 */
	public static function swap( string $statement, array $rebuilt, string $condition, string $posts_table ): ?string {
		if ( '' === $condition ) {
			return null;
		}

		$replacements = [
			[ $rebuilt['join'], '' ],
			[ $rebuilt['where'], $condition ],
			[ "GROUP BY {$posts_table}.ID", '' ],
		];

		// Checked before counting, since substr_count() throws on an empty needle.
		foreach ( $replacements as [ $needle ] ) {
			if ( '' === $needle || 1 !== substr_count( $statement, $needle ) ) {
				return null;
			}
		}

		foreach ( $replacements as [ $needle, $replacement ] ) {
			$statement = str_replace( $needle, $replacement, $statement, $count );

			if ( 1 !== $count ) {
				return null;
			}
		}

		return $statement;
	}

	/**
	 * Writes the ID-only statement WordPress sends first when it splits a query.
	 *
	 * Core builds both from one template, so the split form is the full form with `{posts}.ID`
	 * as the select list. Returns null unless the statement starts with core's exact prefix,
	 * which has three spaces because the found-rows and DISTINCT slots are empty.
	 *
	 * @param string $request     The full statement.
	 * @param string $posts_table The posts table name.
	 *
	 * @return string|null
	 */
	public static function split( string $request, string $posts_table ): ?string {
		$prefix = "SELECT   {$posts_table}.*";

		if ( ! str_starts_with( $request, $prefix ) ) {
			return null;
		}

		return "SELECT   {$posts_table}.ID" . substr( $request, strlen( $prefix ) );
	}

	/**
	 * Whether an ORDER BY has no ties and needs no join.
	 *
	 * True when it sorts only by posts table columns in SORT_COLUMNS and ends with the ID.
	 *
	 * @param string $orderby     The ORDER BY clause, without the keywords.
	 * @param string $posts_table The posts table name.
	 *
	 * @return bool
	 */
	public static function orderby_ok( string $orderby, string $posts_table ): bool {
		$table   = preg_quote( $posts_table, '/' );
		$columns = implode( '|', array_map( static fn( string $column ): string => preg_quote( $column, '/' ), self::SORT_COLUMNS ) );

		return 1 === preg_match( "/\A(?:{$table}\.(?:{$columns})(?: (?:ASC|DESC))?, )*{$table}\.ID (?:ASC|DESC)\z/", $orderby );
	}

	/**
	 * Whether a tax query clause is a filter rather than a nested query. Mirrors the protected
	 * WP_Tax_Query::is_first_order_clause().
	 *
	 * @param array $clause The clause.
	 *
	 * @return bool
	 */
	private static function is_first_order_clause( array $clause ): bool {
		return [] === $clause
			|| array_key_exists( 'terms', $clause )
			|| array_key_exists( 'taxonomy', $clause )
			|| array_key_exists( 'include_children', $clause )
			|| array_key_exists( 'field', $clause )
			|| array_key_exists( 'operator', $clause );
	}
}
