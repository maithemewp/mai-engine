<?php

namespace BizBudding\MaiEngine\Tests\Integration;

use WP_HTML_Tag_Processor;
use WP_UnitTestCase;

/**
 * Base class for the WordPress-loaded suite.
 *
 * Tests must extend this rather than WP_UnitTestCase directly. See expectDeprecated() below
 * for why.
 */
abstract class MaiIntegrationTestCase extends WP_UnitTestCase {

	/**
	 * PHPUnit 10 removed PHPUnit\Util\Test::parseTestMethodAnnotations() and
	 * TestCase::getName(), both of which WP_UnitTestCase_Base::expectDeprecated() calls. It
	 * is invoked unconditionally from set_up(), so without this override every test in the
	 * suite errors before its first assertion. WordPress core has not adopted PHPUnit 10, and
	 * wp-phpunit 7.0.2 carries the identical code, so this is not fixed by a version bump.
	 *
	 * This re-registers the same hooks without the annotation parsing. The only thing lost is
	 * the @expectedDeprecated / @expectedIncorrectUsage docblock annotations, which PHPUnit 10
	 * does not read anyway; setExpectedDeprecated() and setExpectedIncorrectUsage() still work
	 * from inside a test body.
	 *
	 * @return void
	 */
	public function expectDeprecated() {
		add_action( 'deprecated_function_run', [ $this, 'deprecated_function_run' ], 10, 3 );
		add_action( 'deprecated_argument_run', [ $this, 'deprecated_function_run' ], 10, 3 );
		add_action( 'deprecated_class_run', [ $this, 'deprecated_function_run' ], 10, 3 );
		add_action( 'deprecated_file_included', [ $this, 'deprecated_function_run' ], 10, 4 );
		add_action( 'deprecated_hook_run', [ $this, 'deprecated_function_run' ], 10, 4 );
		add_action( 'doing_it_wrong_run', [ $this, 'doing_it_wrong_run' ], 10, 3 );

		add_action( 'deprecated_function_trigger_error', '__return_false' );
		add_action( 'deprecated_argument_trigger_error', '__return_false' );
		add_action( 'deprecated_class_trigger_error', '__return_false' );
		add_action( 'deprecated_file_trigger_error', '__return_false' );
		add_action( 'deprecated_hook_trigger_error', '__return_false' );
		add_action( 'doing_it_wrong_trigger_error', '__return_false' );
	}

	/**
	 * Has MySQL refuse one statement while the callback runs, without changing its text, as a
	 * timeout or a lock wait would. The refused statement is the first one $pick matches.
	 *
	 * A max_join_size of 1 is a session limit that refuses any SELECT expected to examine more
	 * than one row. It is set just before the picked statement and put back before the next one,
	 * so nothing else is refused. Database errors are not printed meanwhile.
	 *
	 * @param callable $pick     Receives each statement's final text, returns whether to refuse it.
	 * @param callable $callback The code to run.
	 *
	 * @return array{0:mixed,1:bool} What the callback returns, and whether a statement was refused.
	 */
	protected function refuse_once( callable $pick, callable $callback ): array {
		global $wpdb;

		$state  = 'waiting';
		$inside = false;

		// Latest priority, so $pick sees the text as it goes to the database. The SET statements
		// run from inside the filter, which core applies before it resets $wpdb for a statement.
		$filter = static function ( $sql ) use ( &$state, &$inside, $pick, $wpdb ) {
			if ( $inside ) {
				return $sql;
			}

			$inside = true;

			if ( 'armed' === $state ) {
				$wpdb->query( 'SET SESSION max_join_size = DEFAULT' );
				$state = 'done';
			}

			if ( 'waiting' === $state && $pick( $sql ) ) {
				$wpdb->query( 'SET SESSION max_join_size = 1' );
				$state = 'armed';
			}

			$inside = false;

			return $sql;
		};

		add_filter( 'query', $filter, PHP_INT_MAX );
		$suppress = $wpdb->suppress_errors( true );

		try {
			$result = $callback();
		} finally {
			remove_filter( 'query', $filter, PHP_INT_MAX );

			if ( 'armed' === $state ) {
				$wpdb->query( 'SET SESSION max_join_size = DEFAULT' );
			}

			$wpdb->suppress_errors( $suppress );
		}

		return [ $result, 'waiting' !== $state ];
	}

	/**
	 * Asserts no tag anywhere in the document carries a class.
	 *
	 * WP_HTML_Tag_Processor::has_class() is scoped to a single tag, so it cannot express the
	 * document-wide negative that actually catches a lost rewrite. It also returns null, not
	 * false, when the processor is not positioned on a tag, so a bare
	 * assertFalse( $p->has_class( ... ) ) passes whether the class is absent or the tag was
	 * never found.
	 *
	 * @param string $html    HTML to walk.
	 * @param string $class   Class that must not appear on any tag.
	 * @param string $message Optional. Custom failure message.
	 *
	 * @return void
	 */
	public function assertNoTagHasClass( string $html, string $class, string $message = '' ): void {
		$tags = new WP_HTML_Tag_Processor( $html );

		while ( $tags->next_tag() ) {
			$this->assertNotTrue(
				$tags->has_class( $class ),
				$message ?: sprintf( 'Expected no tag to carry "%s", found one in: %s', $class, $html )
			);
		}
	}
}
