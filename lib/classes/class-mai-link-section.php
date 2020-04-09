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
 * Link Section.
 */
class Mai_Link_Section extends WP_Customize_Section {

	/**
	 * The section type.
	 *
	 * @var    string
	 */
	public $type = 'mai-link';

	/**
	 * Button Text
	 *
	 * @var string
	 */
	public $button_text = '';

	/**
	 * Button URL.
	 *
	 * @var string
	 */
	public $button_url = '';

	/**
	 * Gather the parameters passed to client JavaScript via JSON.
	 *
	 * @since  0.1.0
	 *
	 * @return array The array to be exported to the client as JSON.
	 */
	public function json() {
		$json = parent::json();

		$json['button_text'] = $this->button_text;
		$json['button_url']  = esc_url( $this->button_url );

		return $json;
	}

	/**
	 * Outputs the Underscore.js template.
	 *
	 * @since  0.1.0
	 *
	 * @return void
	 */
	protected function render_template() {
		?>
		<li id="accordion-section-{{ data.id }}"
		    class="accordion-section control-section control-section-{{ data.type }} cannot-expand">
			<h3 class="wp-ui-highlight">
				<a href="{{ data.button_url }}" class="wp-ui-text-highlight" target="_blank" rel="nofollow">
					{{ data.title }}
				</a>
			</h3>
		</li>
		<?php
	}
}
