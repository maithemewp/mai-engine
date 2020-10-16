<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2020 BizBudding
 * @license   GPL-2.0-or-later
 */

return [
	'demos' => [
		'home-garden'   => 35,
		// 'health-beauty' => 36,
		// 'travel'        => 37,
	],
	'global-styles' => [
		'colors' => [
			'link'      => '#894b32',
			'primary'   => '#002627',
			'secondary' => '#894b32',
			'heading'   => '#002627',
			'body'      => '#252323',
			// 'alt'    => '#e6e0ce',
			'black'     => '#222222',
		],
		'fonts' => [
			'body'       => 'Lato:400',
			'heading'    => 'Playfair Display:900',
			'subheading' => 'Playfair Display:500italic',
		],
	],
	'image-sizes' => [
		'add' => [
			'square' => '1:1',
		],
	],
	'settings' => [
		'content-archives' => [
			'post' => [
				'boxed'   => false,
				'columns' => 2,
				'row_gap' => 'xxxl',
			],
		],
	],
	'custom-functions' => function() {
		/**
		 * Add custom body class.
		 *
		 * @since TBD
		 *
		 * @param   array  The existing body classes.
		 *
		 * @return  array  Modified classes.
		 */
		add_filter( 'body_class', 'mai_inspire_body_class' );
		function mai_inspire_body_class( $classes ) {
			$classes[] = 'has-dark-header';
			return $classes;
		}

		add_action( 'init', 'mai_inspire_register_testimonial_block_pattern' );
		/**
		 *
		 * @since TBD
		 *
		 * @return void
		 */
		function mai_inspire_register_testimonial_block_pattern() {
			if ( ! function_exists( 'register_block_pattern' ) ) {
				return;
			}
			register_block_pattern(
				'mai-engine/mai-inspire-testimonial',
				array(
					'title'         => 'Mai Inspire - Testimonial',
					// 'viewportWidth' => The width of the pattern preview (int),
					// 'categories'    => An array of categories,
					// 'description'   => A description of the pattern,
					'keywords'      => [
						'mai',
						'theme',
						'inspire',
						'testimonial',
						'blockquote',
					],
					'content'       => '<!-- wp:group {"backgroundColor":"black","textColor":"white","verticalSpacingTop":"sm","verticalSpacingBottom":"sm","verticalSpacingLeft":"sm","verticalSpacingRight":"sm"} -->
						<div class="wp-block-group has-white-color has-black-background-color has-text-color has-background"><div class="wp-block-group__inner-container"><!-- wp:columns -->
						<div class="wp-block-columns"><!-- wp:column {"width":33.33} -->
						<div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:image {"id":438,"sizeSlug":"square-sm","marginTop":"-xxxl","marginLeft":"-xxxl"} -->
						<figure class="wp-block-image size-square-sm"><img src="https://maitheme.local/inspire/wp-content/uploads/sites/12/2020/10/IMG_E0595-400x400.jpg" alt="" class="wp-image-438"/></figure>
						<!-- /wp:image --></div>
						<!-- /wp:column -->

						<!-- wp:column {"width":66.66} -->
						<div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:quote {"className":"is-style-default"} -->
						<blockquote class="wp-block-quote is-style-default"><p>Ne utinam delenit mnesarchum nam. Fabellas convenire reprimique duo eu, ad sea ferri maiorum. Enim luptatum conclusionemque mel ei, falli facilisi lobortis at vix, eam ei illud accusamus suscipiantur. Dicit quaestio mandamus ne mea, in quod etiam per.</p><cite>Jamie</cite></blockquote>
						<!-- /wp:quote --></div>
						<!-- /wp:column --></div>
						<!-- /wp:columns --></div></div>
						<!-- /wp:group -->',
				)
			);
		}
	},
];
