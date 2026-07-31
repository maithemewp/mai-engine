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
