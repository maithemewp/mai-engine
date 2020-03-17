<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

/**
 * Class Mai_Inline_Styles
 */
class Mai_Inline_Styles implements ArrayAccess {

	/**
	 * Styles to collect.
	 *
	 * @var array
	 */
	private $styles = [];

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $key
	 * @param mixed $value
	 *
	 * @return void
	 */
	public function offsetSet( $key, $value ) {
		if ( isset( $this->styles[ $key ] ) ) {
			$this->styles[ $key ] = array_merge_recursive( $this->styles[ $key ], $value );
		} else {
			$this->styles[ $key ] = $value;
		}
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $key
	 *
	 * @return bool
	 */
	public function offsetExists( $key ) {
		return isset( $this->styles[ $key ] );
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $key
	 *
	 * @return void
	 */
	public function offsetUnset( $key ) {
		unset( $this->styles[ $key ] );
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $key
	 *
	 * @return mixed
	 */
	public function offsetGet( $key ) {
		return $this->styles[ $key ];
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getStyles() {
		if ( empty( $this->styles ) ) {
			return '';
		}

		foreach ( $this->styles as $breakpoint => $selectors ) {
			$mq     = $breakpoint ? mai_get_breakpoint( $breakpoint ) : false;
			$styles = $mq ? "@media (min-width: {$mq}px){\n" : '';

			foreach ( $selectors as $selector => $rules ) {
				$styles .= $selector . "{\n";

				foreach ( $rules as $property => $values ) {
					$value  = is_array( $values ) ? end( array_values( $values ) ) : $values;
					$styles .= $property . ':' . $value . ";\n";
				}

				$styles .= "}\n";
			}

			$styles .= $mq ? "}\n" : '';
		}

		return mai_minify_css( $styles );
	}
}
