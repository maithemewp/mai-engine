# Changelog

## 2.35.0 (TBD)
* Added: New `--button-outline-width` custom prop to more consistently override outline button styles.
* Changed: Update ACF to 6.3.9.
* Changed: More through and simpler encoding handling with PHP's `DOMDocument`.
* Changed: Better handling of post exclusion when using Mai Post Grid.
* Changed: Using 100svh instead of 100dvh for body height.
* Changed: Now using less specific `:has()` for CSS class checks.
* Changed: Add transient caching to classic editor styles from customizer.
* Changed: Tweak order of processing in `mai_get_processed_content()`.
* Changed: [Performance] More efficient `get_terms()` queries when we only need `id` and `name`.
* Changed: [Performance] Only run ACF field filters in the admin, when loading field data dynamically.
* Changed: Use `acf_verify_ajax()` for nonce check when editing Mai Post/Term Grid.
* Changed: Disable preloading of featured and page header images. This was often causing more issues than it was helping.
* Changed: Convert `DOMDocument` to `WP_HTML_Tag_Processor` for Mai custom attributes.
* Changed: Convert `DOMDocument` to `WP_HTML_Tag_Processor` for WooCommerce button classes.
* Changed: Check if post type exists after `mai_grid_post_types` filter.
* Changed: Validate ACF fields that don't use `field_` as the field key prefix.
* Changed: Update the updater.
* Fixed: Full aligned blocks did not always display correctly in the editor, particularly when nested, and/or when the content alignment was left or right.
* Fixed: The has-z-index-2 wasn't applying the correct z-index of 2.
* Fixed: Search block was not centering correctly in some scenarios, when set to be center aligned.
* Fixed: Author description not always showing correctly on the author archive.
* Fixed: Invalid markup in page header when a category/term has no intro text.
* Fixed: Site title was not always white when using a dark transparent header.
* Fixed: Menu toggle text color was not styed correctly when screen-reader-text class is removed.
* Fixed: Accent color being applied unexpectedly on select fields in some browsers, looking at you Safari.
* Fixed: Select field now sets min-height instead of height.
* Fixed: Removed the "remove global styles" setting to maintain compatibility with WP and fix "Missing Dependencies" error.

## 2.34.1 (4/18/24)
* Changed: Order of processing in `mai_get_processed_content()` to match `get_the_block_template_html()` function in WP core.
* Fixed: The Setup Wizard missing steps for some users.

## 2.34.0 (4/9/24)
* Added: Support for the new "Mai Side Hustle" theme.
* Added: New "Alternate" typography/font setting.
* Added: New "Alternate" block style option on Paragraph and Heading blocks, to use the new "Alternate" font settings in the Customizer.
* Added: New "Oval" divider option. Props Jo Waltham.
* Added: New XXL title size option in Customizer content archive settings and Mai Post/Term Grid title size block settings.
* Added: New Custom Content 2 setting for Customizer Single/Archive COntent settings and Mai Post/Term Grid blocks.
* Added: Better default styling for the core Query block.
* Added: [Developers] New `has-line-height-1`, has-line-height-xs`, `has-line-height-sm`, and `has-line-height-md` helper classes to override default line-height values.
* Added: [Developers] New `mai_has_dark_background_first` filter for custom handling of dark transparent header background.
* Added: [Developers] Override WP Recipe Maker buttons/styles for better theme integration with `mai_enable_wprm_support` filter (false by default). Basic styling support is still always loaded.
* Added: [Developers] New `--button-outline-width` css custom property to override the button border width without overriding other border styles.
* Changed: Minor list styling tweaks on front end and in the editor.
* Changed: Tightened up default body line-height as well as specific areas like archive/grid excerpts, breadcrumbs, and more.
* Changed: Exclude Gravity Forms and WP Forms field labels from default styling so their forms are more consistently styled.
* Changed: [WooCommerce] Better styling for order details/meta and quantity input.
* Changed: [LearnDash] Better styling for deeper visual integration with the theme/settings.
* Changed: Update ACF to 6.2.9.
* Changed: More z-index tweaks so overlap and stacking is more natural and expected.
* Changed: Allow shortcodes, etc. in single content excerpt.
* Changed: Mai Divider now only uses -.5px to handle the slight gap sometimes caused by pixel rounding in some browsers.
* Changed: Better handling of input placeholder color.
* Changed: Update the updater.
* Fixed: Mai Columns was wrapping unexpectedly, more often in the editor.
* Fixed: Logo not inverting on the login page when using the setting to force the logo to white on dark backgrounds.
* Fixed: Better styling of the Search block, including when using the width settings.
* Fixed: Some logic from the setup wizard was running outside of the setup wizard itself.
* Fixed: The icon field picker on various blocks was showing SVG markup instead of the actual icon.
* Fixed: Alignment settings on Embed blocks not working correctly.
* Fixed: Better handling of non-existent taxonomies in Mai Post/Term Grid blocks. This was causing some issues when an instance of the block was configured with a taxonomy that was no longer registered, typically by deactivating a plugin that registered a custom taxonomy.
* Fixed: Inline highlights when using link color as the color setting.
* Fixed: Competing filter-hover declarations in CSS. Now developers only need to use `--menu-item-name-filter-hover` custom property in CSS to remove the brightness dimming of menu items with a dark header.
* Fixed: Better spacing/styling of LearnDash content by adding `entry-content` class to the `ld-tab-content` div.
* Fixed: Better handling of dark/transparent header when registered gradients are used in config.php.
* Fixed: Using square brackets in the post title was breaking `aria-label` in Mai Post/Term Grid.
* Fixed: The `display_post_states` filter adding "Active" Content Area was running on the front end unnecessarily.

## 2.33.1 (2/7/24)
* Added: [Developers] Add entry-grid order to the editor CSS.
* Added: New `has-z-index-2` helper class along with the existing `has-z-index-1`, `has-z-index-0`, and `has-z-index--1` classes.
* Changed: Update ACF to 6.2.6.1.
* Fixed: The `font_size` param on `[mai_menu]` shortcode was not working correctly for custom values.
* Fixed: Negative margin on nested elements was not overlapping correctly in some configurations.
* Fixed: Unwanted vertical scroll on ACF top tabs in the block editor.

## 2.33.0 (1/30/24)
* Added: New setting to force the logo to white on dark backgrounds (dark header or transparent header background). Find the setting in Customizer > Theme Settings > Site Header.
* Added: [Developers] New `mai_before_entry` and `mai_after_entry` action hooks.
* Added: [Developers] New `mai_get_index()` function to increment and reset an index/count based on context string.
* Changed: Adds CSS order/index to Mai Post/Term Grid entries.
* Fixed: Escapes `aria-label` attribute in entry titles, so HTML in the editor title field won't break the markup.

## 2.32.2 (1/19/24)
* Fixed: Even more efficient JS to change no-js to js class on the body. In some instances, this class change was not firing.

## 2.32.1 (1/19/24)
* Fixed: Encoded special characters were displayed on the front end in some configurations.

## 2.32.0 (1/18/24)
* Added: Better support for WP Recipe Maker. Recipe buttons now inherit styling from Mai.
* Changed: Update ACF to 6.2.5.
* Changed: Simplified inline script to toggle no-js to js body class if JS is enabled in the browser.
* Fixed: Clean up confusing notices from ACF.
* Fixed: Remove unnecessary encoding in PHP's DOMDocument which was unintentionally encoding some special characters from non-English languages.

## 2.31.3 (1/8/24)
* Fixed: Added back updater icon functions.

## 2.31.2 (1/8/24)
* Changed: Now Load core getter functions immediately instead of `after_setup_theme`.
* Fixed: Cover block overlay was not rendering correctly when using Link as the overlay color.

## 2.31.1 (12/28/23)
* Added: Better PHP 8.2 compatibility.
* Changed: Element stacking via z-index is now less aggressive by default. This fixes some edge-case scenarios where floating elements are behind other elements on the page.
* Changed: Converts has-link-background-color to has-links-background-color to fix WP 6.4 compatibility issue. This was causing some color choices to remove the background color from the element.
* Changed: Only apply hidden-{break} and hidden-{break}-down/up classes on the front end. These classes were causing elements to be hidden in the editor.
* Changed: Better compatibility with the latest version of Mai Archive Pages plugin.
* Changed: CSS styling consistencies for Latest Posts block.
* Fixed: Latest Posts block now correctly shows the "Read more" link.

## 2.31.0 (11/28/23)
* Added: PHP 8.2 compatibility.
* Added: ACF sidebar metaboxes now match WordPress core styling.
* Changed: Update ACF to 6.2.4.
* Changed: Converts has-link-color to has-links-color to fix WP 6.4 compatibility issue. This was causing some color choices to force text to the Mai link color value.

## 2.30.4 (11/8/23)
* Fixed: Wrong closing tag on inline style causing broken or missing JS notice in the block editor in some scenarios.
* Fixed: Force all attributes in `mai_get_image_src_srcset_sizes()` for scenarios where a plugin filters the values and removes some required attributes.

## 2.30.3 (10/25/23)
* Added: Support for Mai Studio Real Estate demo.
* Changed: Update ACF to 6.2.2.
* Changed: Style improvements for Genesis eNews Extended widget.
* Fixed: Cover block markup logic not applying in some configurations when the wrapper element is not a div.
* Fixed: Search block styles updated with changes in WP styles.
* Fixed: Hide entry custom content element if it's empty.
* Fixed: Duplicate style tags on some block svg icons in the editor.
* Fixed: Preload sometimes using the wrong image on mobile.
* Fixed: Scroll logo CSS wrongfully affecting site logo block.
* Fixed: Blog archive posts per page not pulling correct value in some configurations.

## 2.30.2 (8/25/23)
* Fixed: Broken inline CSS on Cover blocks in some configurations.

## 2.30.1 (8/16/23)
* Fixed: Custom inline styles breaking when buttons had multiple customizations like custom font sizes and colors.

## 2.30.0 (8/16/23)
* Added: Support for AIOSEO breadcrumbs.
* Changed: Update ACF to 6.2.0.
* Changed: Show all markup when - displaying full content of an entry.
* Changed: Cover block markup is now manipulated via `WP_HTML_Tag_Processor` instead of PHP `DOMDocument`.
* Fixed: Conflict with a new change in WP 6.3 where Cover block has overflow clip/hidden added and cuts off overlap from inner blocks.

## 2.29.1 (6/28/23)
* Changed: Update ACF to 6.1.7.

## 2.29.0 (6/26/23)
* Changed: Mai Post/Term Grid “no results” text now respects text alignment setting.
* Changed: Update Kirki to 4.2.0.
* Fixed: Multiple custom classes on Mai Post/Term Grid were getting joined into a single class.

## 2.28.2 (6/21/23)
* Added: Better compatibility when using core Post Content and Post Featured Image blocks.
* Added: [Developers] New `$mai_term` global variable for terms in the loop via Mai Term Grid block.
* Changed: Renamed Mai Design Pack to Mai Theme Pro.
* Changed: Added more side spacing to the default select field styling.
* Changed: Update dependencies.
* Fixed: Layout setting per-post not working correctly in some scenarios.
* Fixed: Entry images not filling container when showing left/right full.
* Fixed: Social icons alignment in editor was not working in some configurations.
* Fixed: Function does not exist error for a Genesis function in some edge-case scenarios.
* Fixed: Unexpected border showing on some entry images in Mai Chic.

## 2.28.1 (5/10/23)
* Fixed: Wrong ID being returned on front page for site layout in some scenarios.
* Fixed: Replace deprecated `get_page_by_title()` function.
* Fixed: Mai Inspire demos not working in Mai Setup Wizard.

## 2.28.0 (5/5/23)
* Changed: Update ACF to 6.1.6.
* Fixed: More thorough handling of markup processing to make sure valid markup is returned.

## 2.27.2 (5/2/23)
* Fixed: Remove beta label from plugin version.

## 2.27.1 (5/2/23)
* Changed: Update ACF to 6.1.5.
* Fixed: The sizes attribute is only added to preload links if a srcset value is present. This prevents invalid markup.

## 2.27.0 (4/13/23)
* Added: PHP 8.1 compatibility.
* Changed: Mai Theme admin submenu items are now separated with a divider, and plugin settings pages are now alphabetized.
* Changed: Content is fully processed to allow embeds, shortcodes, and blocks when displaying full content via the settings.
* Changed: Entry excerpt content is now sanitized via `wp_kses_post` for better security.
* Changed: Updated `.pot` file for translations.
* Changed: [WooCommerce] Better mobile styling for WooCommerce Account navigation.
* Changed: [Developers] Previous/Next text filters are now run earlier, so it's easier to filter and override with a default priority.
* Changed: [Developers] The `mai_entry_content` filter is now run before the conditional check for empty content.
* Changed: [Developers] Flexbox now uses start/end instead of flex-start/flex-end.
* Changed: Update ACF to 6.1.4.
* Changed: Update dependency installer.
* Fixed: Cover block images sizes attribute now defaults to 50vw on desktop, when not Full Aligned.
* Fixed: Mai Post/Term Grid anchor and additional classes fields now work correctly.
* Fixed: Mai Term Grid Entries by choice, and Exclude fields were showing empty on page refresh even when there were values saved.
* Fixed: Mai Term Grid offset field still showing when getting entries by Choice.
* Fixed: Better alignment support for Group block's Row variation.
* Fixed: An occasional conflict with other plugins would throw a `get_current_screen()` function does not exist error.
* Fixed: Entries with fixed aspect ratio were not expanding correctly when content was larger than the ratio allowed. #630.

## 2.26.1 (3/3/23)
* Fixed: Make sure full/wide aligned Cover blocks use larger image from srcset/sizes.

## 2.26.0 (3/3/23)
* Added: [Accessibility] New `aria-label` support for menus, including a new `label` parameter in `[mai_menu]` shortcode.
* Added: [Accessibility] New `aria-label` attribute added to entry title links when needed.
* Added: [Accessibility] Entry overlay links how have `aria-labelledby` or `aria-label` with title added.
* Added: [Developers] New `--button-filter` CSS property added to buttons.
* Changed: [Accessibility] Before and After Header Content Areas now use `<section>` element instead of `<div>`.
* Changed: [Accessibility] Mai Icon links now add title as screen reader text.
* Changed: [Accessibility] Removed `role="banner"` from page header so the default `<header>` element can remain the default banner.
* Changed: [Performance] Entry images now use the smallest size for `src` since the `srcset` will serve the correct size image based on screen size.
* Changed: Archive or Mai Post Grid entries show the full block content when "Show" has "Content" with no limit set.
* Changed: Updated Kirki to 4.1.0, and disabled their new settings page.
* Changed: Updated ACF Pro to 6.0.7.
* Changed: Now using ACF's new built in Composer support for updates.
* Fixed: Editors were not able to access Reusable Blocks or Content Areas posts in the Dashboard.

## 2.25.6 (1/16/23)
* Changed: [Performance] Converted to dwv units when available, to calculate full viewport width via CSS instead of JS.
* Changed: [Performance] Only load ACF block field filters in the editor.
* Changed: [Performance] Removed unused Genesis filter that adds a featured image class.
* Changed: Icon picker field filters are cached for 1 hour instead of 8.
* Changed: Updated dependencies script.
* Fixed: Social Icons block was using the wrong https value for xmls attribute.
* Fixed: Character encoding for special characters from non-English languages.

## 2.25.5 (12/22/22)
* Changed: [Performance] Now preloading page header image, single entry featured image, and cover block image if it's the first block in the content.
* Changed: [Performance] All woff2 files via config and settings are now preloaded.
* Changed: Updated ACF Pro to 6.0.6
* Changed: [WooCommerce] Product summary is not vertically aligned with product image on product pages.
* Changed: `is-sticky` class now has `z-index` added, to make sure sticky element is on top of other elements.
* Changed: Updates pot file with missing and changed text strings from past updates.
* Fixed: [Performance] Logos not preloading correctly in some instances.
* Fixed: [Performance] Some files were incorrectly getting loaded in the footer.
* Fixed: Better handling of first full aligned block top margin when plugins inject scripts or styles as the first element in the content.
* Fixed: Hiding entry title via Mai Elements settings was not working in some edge-case areas of the site.
* Fixed: Boxed layout showing when first block was full aligned on Mai Achieve theme.
* Fixed: PHP 8.1 deprecated function warning with `http_build_query()`.

## 2.25.4 (11/29/22)
* Added: Hide Elements metabox now available on Category, Tag, and Term archives.
* Changed: Updated ACF Pro to 6.0.5
* Changed: Minor CSS tweaks around WP's new is-layout-{type} classes.
* Fixed: Division by zero error when using an SVG logo in some configurations.
* Fixed: Mai Columns vertical alignment setting was not working in the editor.
* Fixed: Logo was dimming on hover if using a dark site header background color.
* Fixed: Gap was too large on Navigation menu block when inside Mai Columns.

## 2.25.3 (11/8/22)
* Changed: Updated ACF Pro to 6.0.4
* Fixed: PHP warning from required parameter following optional parameter in some blocks.

## 2.25.2 (11/4/22)
* Added: [Developers] New `mai_entry_excerpt` filter to add excerpt content even if there is not manual excerpt added.
* Fixed: Compatibility issues with WP 6.1 that was affecting alignment on buttons, galleries, and likely other blocks.
* Fixed: WYSIWYG/Classic editor background color should stay white when using boxed container setting on your site.
* Fixed: Inline block editor CSS was being added to pages that weren't using the block editor, and caused some compatibilty issues with some other plugins.

## 2.25.1 (10/27/22)
* Fixed: Blocks with margin overlap should now correctly show on top of sibling elements in the editor.
* Fixed: Error preloading logo if theme name is switched while active.

## 2.25.0 (10/27/22)
* Added: New `relative` and `relative_max` attributes for `[mai_date]` shortcode. Allows you to show "2 days ago" as the date for a limited amount of time after publishing.
* Added: New setting in Mai Post/Term Grid blocks to add text when there are no entry results.
* Added: [Performance] New setting in Customizer > Theme Settings > Performance to preload the main heading and body font files when Google fonts are the chosen font families. This setting defaults to on.
* Added: Using a dark body background color is much more usable on the front end and in the editor.
* Added: [SEO] Category/Tag/Term featured image is now used as Open Graph image.
* Added: [Performance] Removed lazy loading and added srcset on site header logos.
* Changed: [Performance] Now using woff2 font files which are significantly smaller and more performant.
* Changed: [Performance] Now preloading the logo file(s).
* Changed: Updated ACF Pro to v6.0.0. Loading Mai blocks in the editor should now be significantly faster.
* Changed: Updated all blocks to use new block.json format to register.
* Changed: Rebuilt the output logic for Mai Columns with new features available in ACF v6.
* Changed: ACF Pro will now remain active if installed standalone and running a version newer than what is in Mai Engine.
* Fixed: Remove left margin on list blocks when setting a background color.
* Fixed: Button link text and arrow were not centered when a button was set to a larger width.
* Fixed: Better handling of dark background colors for body and other areas, as well as better handling of text color in these scenarios.
* Fixed: Site header items now always remain in the same row on mobile.
* Fixed: Pagination arrows not aligning to the edge when adjacent post does not have a featured image.
* Fixed: Custom colors in the config are now correctly shown as options in ACF color picker fields.
* Fixed: Mai Post Grid taxonomy relation not working in some configurations.
* Fixed: Error when aria-current attribute is not available on a menu item.
* Fixed: FacetWP pagination now uses arrows from config.

## 2.24.0 (8/9/22)
* Added: [Performance] New setting to remove jQuery Migrate script. Default is on (removes script).
* Changed: Previous and next icons are now added and overridden via the `config.php` file with automatically added CSS custom properties.
* Changed: Moved updater script to `plugins_loaded` hook so it should update via WP-CLI now.
* Fixed: Mai Grid blocks getting wrong taxonomy/terms when adding multiple taxonomy conditions.

## 2.23.0 (7/20/22)
* Added: New "More Link Style" setting to choose the style for the "Read More" link/button on Mai Post/Grid blocks and Content Archives settings in Customizer.
* Added: Form `accent-color` declaration so choice fields like radio and checkbox use chosen primary color.
* Fixed: Post type and taxonomy choices not loading correctly when multiple Mai Post/Grid blocks were used with the edit mode enabled.
* Fixed: Entry image width when Mai Post Grid is used in a registered block pattern.
* Fixed: [Developers] Added `style` attribute to `register_block_type` in ACF so patterns work better in pattern inserter until ACF 6.0 is released.

## 2.22.0 (7/11/22)
* Added: New "Has border" and "Has border radius" settings on Mai Column blocks.
* Added: New "Content Alignment" setting on Heading and Paragraph blocks for times when the Max Width setting is used and you want to align the content independent from the text alignment.
* Added: [Performance] New setting to remove unused global styles and inline SVGs added by WP 6.0 for features rarely used in WP. These extra styles and inline SVGs are now removed by default.
* Added: [Performance] The comment-reply JS script is no longer loaded unless comments are enabled and there is at least 1 comment.
* Added: Mai Term Grid now allows you to search by term ID when choosing specific terms to include/exclude, etc.
* Added: Plugin version numbers are now shown in Dashboard > Mai Theme > Plugins.
* Added: [Developers] New `mai_has_sticky_header` and `mai_has_transparent_header` filters so developer can enable or disable conditionally.
* Changed: Mai Theme now requires PHP 7 and up.
* Changed: Showing Content on content archives now shows the full content, markup, blocks, shortcodes, etc.
* Changed: Added filter to remove deprecated code from even loading in Genesis.
* Changed: Columns CSS now works the same way everywhere, whether it's in Mai Grid blocks, content archives, or Mai plugins that use our columns clone fields and helper functions.
* Changed: "Width" panel on Heading and Paragraph blocks is now called "Layout" since the "Content Alignment" setting has been added.
* Changed: The `has-light-background` class is now added to Mai Column if a light background is set. This helps force default colors on the inner content, especially when the columns are over a dark background.
* Changed: Themes that use heading font for the menu now use the 'font-variant' attribute in config.php.
* Changed: More thorough application of `hidden-sm`, `hidden-sm-up`, and `hidden-sm-down` and all size helper classes to hide elements.
* Changed: Site description margin tweak when the site title and description are both shown.
* Changed: Much simpler CSS for `is-circle` and `is-square` helper classes.
* Changed: The config.php now uses the full, absolute URL to the import files for the setup wizard. This is so the demos are no longer reliant on the site ID.
* Changed: [Developers] The `genesis_disable_microdata` filter is now respected when we add navigation microdata.
* Fixed: Some general compatibility fixes for WP 6.0.
* Fixed: Number setting not working in Mai Term Grid if the taxonomy is Post Tag or another non-hierarchical taxonomy.
* Fixed: Nested columns are now even more thorough in the editor, so it should more closely match the front end.
* Fixed: Potentially fixed a random bug where Customizer CSS would break while setting a transient and required flushing transients (or opening Customizer and updating) to fix.
* Fixed: Outline buttons were larger than standard buttons from changes in WP 6.0.
* Fixed: Added some custom property fallback values for edge-case configurations where the value is not set by the theme.
* Fixed: CSS tweak to fix entry title links when Mai Grid blocks are not boxed and over a dark background.
* Fixed: CSS tweak to fix entry-wrap width in some edge-case configurations.
* Fixed: CSS tweak for inline highlighted text to fix changes in WP 6.0.
* Fixed: CSS tweak that adds z-index to `has-shadow` class.
* Fixed: Margin being added unexpectedly to Mai Grid blocks when the margin settings are set to None.
* Fixed: Edge-case scenarios where a custom font is added via the config using `400italic` instead of just `italic` will now work either way.
* Fixed: More Customizer settings should show in live preview from a fix in static caching in our helper functions.

## 2.21.3 (4/27/22)
* Added: Basic support for bbPress.
* Added: Show Mai Engine version number on Mai Theme > Plugins page in the Dashboard.
* Changed: [WooCommerce] Small checkout button layout tweaks when Google and/or Apple Pay buttons are used.
* Fixed: Edge-case compatibility issue with any plugins that may trigger conditional tags to get called before they are available.
* Fixed: Mai Post/Grid image width button label inconsistency.
* Fixed: [FacetWP] Add button-small class to pager buttons so they match default archive pagination button styles.

## 2.21.2 (4/14/22)
* Changed: Allow Mai Icons plugin to be installed via Dashboard > Mai Theme > Plugins without requiring Mai Design Pack plugin.
* Fixed: Color settings in the Customizer were not saving in some scenarios.
* Fixed: Missing fallback for `--body-font-weight-bold` when a non-Google font is selected in the Typography settings.
* Fixed: Mai Icon block link no longer 404's and links to Plugins page when Mai Icons plugin is not installed.
* Fixed: The `is-column` class was getting added to `entry-single` wrap when it should only be on archive and grid entries.

## 2.21.1 (4/12/22)
* Fixed: Italicized text showing as incorrectly showing as bold and italic in some configurations.
* Fixed: Errors and empty menu search icon when Mai Icons plugin is not active. Now using HTML entities as fallbacks for search and close icon.
* Fixed: Entry index was incrementing in Grid when it should only be for archive entries. This was breaking the layout of in-content content areas in Mai Custom Content Areas plugin.
* Fixed: [WooCommerce] Add to cart loading animation was also animating the hiding of the button text while showing the loading animation, not it's immediately hidden.

## 2.21.0 (4/6/22)
* Added: Support for Easy Digital Downloads. Customizer archive/single, layout, and color now work with Downloads out of the box.
* Added: Support for RankMath, including RankMath breadcrumbs.
* Added: Larger padding and gap settings to blocks like Mai Columns.
* Added: Margin top and bottom settings to Mai Post/Term Grid blocks.
* Added: [WooCommerce] New loading icon on buttons when clicking add to cart.
* Added: [Developers] New `config.php` syntax to allow custom font weights based on the currently selected heading/body font, with automatically generated `-light` and `-bold` custom properties.
* Added: [Developers] New `has-drop-shadow` class to easily add consistent shadow to transparent images inside of a container.
* Added: [Developers] New `has-background` class on Mai Column block when a background color is added.
* Added: [Developers] New `--image-filter` custom property for setting image filter globally with CSS.
* Added: [Developers] New `--entry-order` custom property to manipulate entry order or custom content in archives.
* Added: [Developers] New entry meta custom properties for easily changing meta font styles.
* Changed: Converted many block fields to ACF clone fields so they can easily be re-used and paired with some useful new helper functions.
* Changed: Slightly changed the color variant amount when auto-generating light/dark color variants.
* Changed: Minor blockquote styling tweaks.
* Changed: Columns "Auto" setting is now "Fit" to match consistency with other blocks.
* Changed: Icon link to Font Awesome now points to new url on their updated website.
* Changed: Bump ACF to 5.12.2 which fixes the bug where duplicating a block would cause issues when changing settings of one of them.
* Changed: Bumped to Kirki v4. Color and other controls/settings now use the React components bundled in WP.
* Changed: Added an ajax check when using transients for dynamically generating font and typography CSS.
* Changed: Internal CSS cache via transients are automatically cleared after 1 hour now instead of 1 minute.
* Changed: Clear internal CSS cache when anything updates mai-engine option.
* Fixed: Mai Post/Term Grid images not centering correctly in some configurations.
* Fixed: Mai Divider now uses a fixed height scaling system to match padding scale. This fixes the occasional small gap that would appear above or below the divider and random window widths.
* Fixed: Font scale breakpoint custom properties had the wrong name for tablets in last update.
* Fixed: Content alignment sometimes not centered in the editor.
* Fixed: Alternating images in archives or Mai Post Grid did not have the correct margin and spacing in some configurations.
* Fixed: First version wasn't being correctly stored.
* Fixed: Occasional squished display when viewing on Instagram and Facebook's internal browser.
* Fixed: Edge-case error if a Mai Post/Term Grid was set to a post type or taxonomy that no longer exists.
* Fixed: [WooCommerce] Hiding most elements on Shop page should work now.

## 2.20.0 (2/15/22)
* Added: New XXL and XXXL padding settings on Mai Column block.
* Changed: Typography and spacing ratios are now incremented smaller on tablet and mobile for better responsive dislay on smaller screens.
* Changed: Updated dependency manager.
* Changed: Wide align now has a set extended width regardless of content width as well as better handling on tablet/mobile.
* Fixed: Wide align wasn't displaying correctly in the editor.
* Fixed: Image spacing when centered in a boxed entry.
* Fixed: Compatibility with some plugins checking layouts before `get_current_screen()` function is available.
* Fixed: Missing bottom margin on editor title wrapper since 5.9 is using custom properties that aren't set.
* Fixed: [WooCommerce] Stars not displaying correctly on single product pages.

## 2.19.6 (1/31/22)
* Changed: Reverts clearing floats on headings. Headings now wrap around floated images and other elements as they did before 2.19.

## 2.19.5 (1/31/22)
* Fixed: Function `bcsub()` does not exist on some hosting environments. Converted to a different method of calculation so that function is not required.

## 2.19.4 (1/28/22)
* Fixed: Editor layout, full align, and margin settings now more closely match the front end.

## 2.19.3 (1/28/22)
* Fixed: Layout fallbacks not working, again.

## 2.19.2 (1/27/22)
* Fixed: Layout fallbacks were not working in some configurations.
* Fixed: Site layouts now work (again) in the editor (editor content width).

## 2.19.1 (1/27/22)
* Changed: Removed global styles unused CSS added in WP 5.9.
* Fixed: Error when editing content areas in the admin.
* Fixed: Login form styling tweak for language switcher.

## 2.19.0 (1/26/22)
* Added: New Plugins admin page which enables an easy install and activate screen when new Mai Design Pack plugin is installed.
* Added: New Patterns admin page which gives quick access to Mai Pattern Library.
* Added: New after and before date settings to Mai Post Grid block.
* Added: New `[mai_date]` shortcode to allow much more control over published and updated dates in entries.
* Added: New `has-auto-margin-top` and `has-auto-margin-bottom` helper CSS classes.
* Added: Custom image sizes added via config.php are now added to editor media chooser.
* Added: Custom image orientations can now be added via config.php and are automatically available in Customizer and Mai Post/Term Grid blocks.
* Added: [Developers] New `mai_entry_image_size` filter.
* Added: [Developers] New `mai_alignfull_first_blocks` filter to allow Mai Theme to check for other blocks that may be the first full aligned block in the content. Our CSS is adjusted for these blocks to remove whitespace on full width sections.
* Added: [Developers] New `mai_hide_search_toggle_text` filter to enable text next to the search toggle icon.
* Added: [Developers] Entry content now has `entry-content-single` class to differentiate when it's archive, single, or a grid entry.
* Changed: Columns via archives, Mai Columns, or Mai Post/Term Grid now use `gap` for CSS instead of negative margin. This works in enough recent major browser versions that it's worth using.
* Changed: Aspect ratio CSS now uses `aspect-ratio` property, with a fallback to the old padding trick for unsupported browsers.
* Changed: Helper `has-{color}-color` classes now use custom property values accordingly, instead of raw hex values.
* Changed: [Performance] Better responsive images by removing our max srcset size filter. This was causing full size image to load in some scenarios.
* Changed: [Performance] Removed lazy loading attribute from page header image since that will almost always be loaded above the fold.
* Changed: [Accessibility] Menu toggle search forms now have a default placeholder.
* Changed: Headings now clear floats in CSS.
* Changed: List item margin is now applied a bit more aggressively to override WP core styles.
* Changed: Clearer image field labels when editing categories, tags, and custom taxonomies.
* Changed: Image srcset is now sorted to be mobile first. This controversially doesn't actually do anything, but we think it's much more readable.
* Changed: Admin menu support and docs links are now combined with an added capability check.
* Changed: Genesis Connect for WooCommerce is now recommended via the engine, not in the theme.
* Changed: Updated dependency installer.
* Changed: Dependency load order and other tweaks.
* Changed: Updated ACF to 5.11.4.
* Changed: Processing content function now conditionally checks for blocks to avoid extra `br` and `p` tags.
* Changed: Box shadow is now `none` instead of `0` when trying to removing via CSS.
* Changed: Menu search toggle in footer now opens the search box above the icon.
* Changed: Image blocks now have no margin when they are last item in a Mai Column block.
* Fixed: Editing Content Areas was showing a whitescreen in WP 5.9.
* Fixed: Mobile header search box now aligns correctly no matter where it is in the header.
* Fixed: Entry image margin and spacing fixes, especially when aligned left or right.
* Fixed: Scroll logo was not working on smaller screens when the header was still sticky.
* Fixed: Custom border radius on buttons weren't working.
* Fixed: Custom button-small and button-large classes now display button styles correctly in the editor.
* Fixed: Archive and Mai Post Grid alternating images weren't working when a custom image size was used.
* Fixed: Edge-case error when logo or scroll logo file was corrupt or missing dimensions.
* Fixed: Edge-case styling issue when some WooCommerce (or other) plugins use an iframe as an input.
* Fixed: Edge-case error when no site layout could be found and final fallback was needed.
* Fixed: Creating custom template content areas for translations was not working with WPML or Polylang.
* Fixed: [Accessibility] Aria labels were reversed on mobile menu toggle.
* Fixed: [WooCommerce] Hiding entry titles now work on product pages.

## 2.18.0 (10/25/21)
* Added: You can now use shortcodes and blocks (via [mai_content]) in author bios.
* Added: [Developers] New `mai_before_entry_content_inner` and `mai_after_entry_content_inner` hooks.
* Added: Default button classes added to WooCommerce blocks to help with styling consistency.
* Added: Custom properties on WooCommerce onsale badge for easier CSS customizations.
* Changed: Template Parts in Mai Theme are now called Content Areas. This avoids confusion as WordPress is introducing template parts into core.
* Changed: Main plugin file is now `Mai_Engine` class for easier checking if Mai Engine is running via other plugins.
* Changed: ACF Pro and Kirki are now loaded earlier.
* Changed: Removed the ability to add new Content Areas. They do not display unless registered via config.php so this was adding confusion.
* Changed: Default 'cover' registered image size is now 2048 instead of 1536, to match the core image size.
* Changed: Default embed, iframe, object, and video elements no longer have `width: 100%;` as default CSS.
* Changed: Refactored mobile menu and search toggle JS.
* Changed: Search form input is now required when trying to submit and empty search query.
* Changed: Admin menu label and order tweaks.
* Changed: Simplified Content Area checker background in editor.
* Changed: Use background alt color for striped table block style.
* Fixed: ACF's [acf] shortcode now works in Custom Content field in Mai Post Grid blocks to show custom field values.
* Fixed: Error if thumbnail image size was 0.
* Fixed: Favicon 404 error in editor when non set.
* Fixed: Error when there was a scroll logo value but no site logo.
* Fixed: Hide Elements metabox settings now work on the static Page for Posts (Blog) page.
* Fixed: Mai Icon block allows numeric border radius by forcing to px when rendering.
* Fixed: Mai Columns now works correctly when setting column as first on mobile or tablet when using custom arrangements.
* Fixed: Sub-menus not working if mobile menu toggle is shown on desktop.
* Fixed: Mobile header search icon bar now displays correctly when mobile menu is shown on large screens.
* Fixed: Mobile menu is now closed when you open the mobile search bar.
* Fixed: Button block style previews were not displaying in some scenarios.
* Fixed: Cover block image srcset was sometimes getting removed in some instances.
* Fixed: Login form margin tweaks.
* Fixed: Add nopin attribute on scroll logo.
* Fixed: Missing itemscope on menus added via [mai_menu] shortcode.
* Fixed: Remove async on styles, since this is not valid HTML.
* Fixed: Invalid HTML from xmlns using https when it requires http.

## 2.17.1 (9/17/21)
* Fixed: Archive single sortable settings not showing checkmarks in Chrome.
* Fixed: New color picker settings weren't always showing the color name on hover.

## 2.17.0 (9/16/21)
* Added: Mai Columns now has a "Space" option for alignment to align the columns with space between each item.
* Added: New shadow setting on individual Mai Column blocks to add default box shadow to the column item.
* Added: New CSS custom properties to global override spacing sizes.
* Added: New CSS custom property to easily override input placeholder font size.
* Changed: Mai Icons plugin is no longer required for Mai Engine to run.
* Changed: New block icons for Mai Columns and Mai Post/Term Grid blocks.
* Changed: Settings and UI is much better when using Mai Columns blocks in the editor, including visible text when adding a new Mai Column.
* Changed: Mai Icon, Mai Column, and Mai Divider color picker now matches native block style and UI.
* Changed: Mai Icon, Mai Column, and Mai Divider color picker now uses site color palette when changing the color value in the Customizer, or pasting from another site or our pattern library.
* Changed: Mai Icon border radius default now uses the theme default for new instances.
* Changed: Bump ACF to 5.10.2
* Changed: Reversed primary and secondary button colors on archive pagination.
* Changed: Add some spacing back to top of login logo.
* Changed: Remove deprecated header left/right sidebar (widget area) checks.
* Changed: Mai Theme admin page content.
* Changed: Mai Icon SVG picker now uses href instead of deprecated xlink attribute.
* Fixed: Mai Divider custom classes weren't adding spaces correctly on front end.
* Fixed: Center logo on mobile it's the only element displaying.
* Fixed: Blockquote paragraph spacing when used in a column.
* Fixed: Image not filling full width in some configurations when Mai Post/Term grid is set to show a background image.
* Fixed: Alignment width not going full width in the editor in some instances.
* Fixed: JS error when mobile menu toggle is not displayed.
* Fixed: Mai Reusable Block widget link to edit the actual block was not working.
* Fixed: Hidden text links on login page in some configurations when header is set to a dark color.
* Fixed: [WooCommerce] The show password icon was not displaying when login form was set to display on My Account page.

## 2.16.0 (8/10/21)
* Added: WPML and Polylang translation support for Template Parts.
* Fixed: Mai Columns now automatically makes text white when you set an individual column to a dark background color.
* Fixed: PHP error when settings the site logo to 0 width.
* Fixed: Mobile menu breakpoint was not using saved value in Customizer.
* Fixed: [WooCommerce] Links on checkout looked like regular text. Now they use the theme link color.

## 2.15.1 (7/21/21)
* Changed: Bump ACF to 5.9.9
* Changed: Embed blocks are now the full width of their container. This mostly helps in Mai Columns and when nested other layout blocks.

## 2.15.0 (7/21/21)
* Added: Compatibility for WP 5.8 editor style changes.
* Added: Support for PHP 8.
* Added: Support for Seriously Simple Podcasting via Mai Theme Customizer settings.
* Added: New "None" option for margin top and bottom settings on paragraph, heading, and separator blocks.
* Added: Mai Post Grid now allows you to search for posts by ID. Great for sites with a lot of content.
* Added: [Developers] New `mai_entry_args` filter on all archive, single, and Mai Post/Term Grid entries.
* Added: [Developers] New `mai_before_entry_{$element}` and `mai_after_entry_{$element}` action hooks for entry elements.
* Changed: Menu search icon now shows an X to close when open.
* Changed: Consecutive menu icon labels now have tightened spacing.
* Changed: Outline button now uses max-height instead of line-height to match button heights.
* Changed: Page Header CSS is now in it's own file that is only loaded if the page header is displaying.
* Changed: Button padding and font size now uses rem instead of em for consistency.
* Changed: Button link style now has more spacing when next to another button.
* Changed: Remove image size limit filter on image blocks.
* Changed: [WooCommerce] Product gallery thumbnails CSS now simplified with CSS grid.
* Changed: Bump ACF to 5.9.7.
* Changed: Bump Kirki to 3.1.9
* Fixed: Mai Post/Term Grid block margin when alternating images.
* Fixed: Mai Post Grid block get entries by current parent setting now works in editor.
* Fixed: Mai Columns fill space setting was not working correctly in some instances.
* Fixed: Entry image gap when image position is centered.
* Fixed: Login page CSS tweaks for reset password and other views.
* Fixed: Search icon JS event was still firing after the search box was closed.
* Fixed: Scroll logo now has matching transition when going back to normal logo.
* Fixed: Different author avatars not working when `[mai_avatar]` shortcode was used on archives.
* Fixed: Repeatable image on Cover block not working in some scenarios.
* Fixed: Search block editor style tweaks.
* Fixed: Remove button hover shadow on button link style.
* Fixed: Some CPTs were not available in Customizer settings.
* Fixed: [WooCommerce] cart and checkout style tweaks.
* Fixed: Mai Catalina sub-menu styling on mobile.

## 2.14.1 (5/17/21)
* Fixed: [WooCommerce] Scroll logo was too tall on WooCommerce pages from Woo's overly aggressive CSS affecting all images on a page.

## 2.14.0 (5/17/21)
* Added: Mai Icons plugin is now auto-installed and required. This update removes all icon SVG's from the engine and makes the plugin much smaller. Future updates will be extremely fast to install/update. Mai Icons plugin only contains the SVG files, Mai Icon block and `[mai_icon]` shortcode are still in the engine itself, the output will render the icons from Mai Icons plugin. If Mai Icons doesn't exist there will be no errors and nothing will break, you will only be missing the icons in the page HTML.
* Added: Login page now styled to match theme color settings.
* Added: You can now add shortcodes to custom Excerpts in content archives. Useful for `[mai_rating]` or other content specific shortcodes.
* Added: The last step in setup wizard now has a reminder to regenerate images.
* Added: [Developers] Added `mai_alignfull_first_blocks` filter to enable other blocks to be supported as the first block in the content. This removes default spacing in many themes for the look of full width sections.
* Changed: Mai Columns block is no longer in beta.
* Changed: Scroll logo is now an inline image instead of background image on a psuedo-element.
* Changed: Template part transient is now deleted any time a post is saved, even if it's a revision.
* Changed: [WooCommerce] Checkout and shop page layout tweaks.
* Changed: [WooCommerce] Proceed to checkout button now slightly larger than default buttons.
* Fixed: [Accessibility] Entry image links now only have aria-hidden if there is another entry link inside the entry.
* Fixed: [Accessibility] Added aria-hidden to entry overlay.
* Fixed: [Accessibility] Entry background color is now black (not seen visually) when image location is set to background.
* Fixed: Reusable blocks no longer break layout of the blocks inside, especially noticeable with full aligned inner blocks.
* Fixed: Edge-case bug that caused horizontal jitter on some Android devices.
* Fixed: Alignfull blocks had too much padding when page layout had sidebar.
* Fixed: Mai Term Grid default "Get Entries By" setting was empty.
* Fixed: Mai Term Grid default "Order By" setting was empty.
* Fixed: Mai Term Grid parent setting not working in some scenarios.
* Fixed: Cart total was cut off when double digit cart items and menu font size was customized to be larger than the default.
* Fixed: Content Archives "Align Text Vertical" setting was hidden when image is Left/Right Middle.
* Fixed: [WooCommerce] Gallery thumbnails were too big if only 1-2 were loaded.

## 2.13.0 (4/20/21)
* Added: [Beta] New scroll logo setting to change logos when a sticky header is stuck/scrolled.
* Added: Mai icon to Customizer when preview is loading.
* Added: New "user" value for the "id" parameter on `[mai_avatar]` to get the currently logged in user's avatar. Great for membership-type sites.
* Added: [Developers] More custom properties on entry link.
* Added: [Developers] New defer attribute available when loading scripts via the config.
* Added: [Developers] New `mai_page_header_image_size` filter to change the image size that's loaded in page header.
* Added: [Developers] New `has-page-header-image` class added to the page header when there is an image.
* Added: [Developers] Added back `mai_cover_block_image_id` filter.
* Changed: Dynamic CSS now uses custom properties as inline style values instead of hex values when possible. This allows easier hijacking, overriding, and consistency when using custom CSS.
* Changed: [Developers] Deprecated `mai_get_color()` function. Please use `mai_get_color_value()` instead when you want a color value, and `mai_get_color_css()` when you want custom properties when available.
* Changed: Reverted paragraphs using `--body-color` custom property. This was causing issues in some dark background scenarios and for those still using page builders.
* Changed: Hide customizer panels for content types that no longer exist, typically from plugins being deactivated.
* Changed: Renamed "Spacing" to "Margin" on heading, paragraph, and separator blocks.
* Changed: Fully rebuilt how HTML attributes are added to scripts/styles via config.
* Changed: [Performance] Setup Wizard now only imports full size and limits other crop/resized image sizes to a specific "allowed" list of sizes. Greatly reduces import time when loading content.
* Changed: [Performance] Remove loading attribute from featured image on single posts/pages. Testing showed this improved LCP/CLS.
* Changed: [Performance] Mai Columns CSS is now loaded with other block CSS instead of late loading. This helps with CLS.
* Changed: [Performance] Now wp-block-library styles are loaded asynchronous by default.
* Changed: [Performance] Entry images now check if the smaller crop sizes exist before falling back to the full size image when a specific image size doesn't exist.
* Changed: CSS when JS is not loaded is now in footer so it doesn't create any CLS when JS is initially loaded for typical users.
* Changed: Make sure style.css is loaded last when loading in header when customizer setting to load in footer is unchecked.
* Changed: Switch `get_the_author_meta( 'ID' )` to `get_post_field( 'post_author' )` since it wasn't working in page header and
other contexts.
* Changed: Setup wizard now uses `WP_Dependency_Installer` to install/activate plugins.
* Fixed: [Performance] Make sure dynamic CSS filter only runs when updating transient/cache.
* Fixed: Missing page header image setting is now back.
* Fixed: No longer cache page header dynamic CSS since different content types can have different values.
* Fixed: Before header search input and sub-menus hidden behind the site header.
* Fixed: Padding on alignfull blocks incorrect when boxed-container is used.
* Fixed: Margin on heading, paragraph, and separator was way too big in editor when using the Margin block settings.
* Fixed: Entries loading too small of an image when the using the Wide Content layout.
* Fixed: Entry text color should use body color setting when the entries are boxed on dark background.
* Fixed: Entry titles from other queries were getting removed on 404 page when the 404 Page template part was used.
* Fixed: Header CSS not loading when transparent header was disabled.
* Fixed: Table CSS was not loading in editor so table styles did not match front end.
* Fixed: Setup Wizard no longer asks to install plugins that are already active.
* Fixed: Setup Wizard no longer breaks blocks with HTML in the block settings.
* Fixed: No longer cache logo scroll offset value twice.
* Fixed: FacetWP results broken when the filters return no items.

## 2.12.0 (4/2/21)
* Added: New Site Header color setting. Easily change your site header color, including dark headers with automatic white text!
* Added: New "Full" option for Content Width in Group and Cover blocks.
* Added: New "Content Alignment" settings in Group and Cover blocks. Set your content width to something smaller and align the inner container left/center/right.
* Added: New `font_size` parameter in `[mai_menu]` shortcode. Accepts `xs`, `sm`, `md`, `lg`, `xl`, `xxl`, `xxxl`, `xxxxl`, and any CSS unit value like `16px`, `1em`, `1.2rem`. Number values like `16` will automatically be converted to pixel values like `16px` during display.
* Added: New `post_type` parameter in `[mai_content]` shortcode so you can display any post type by slug. The `id` parameter already allowed this without needing `post_type` but now you can also display any post type by slug.
* Added: [Developers] New `mai_remove_entries` (bool) filter so developers can now easily remove entries from default loops.
* Added: [Accessibility] Added "Link Title" field in Mai Icon block and `link_title` parameter in `[mai_icon]` shortcode to add a visually hidden label for accessibility.
* Changed: [Performance] Simplify caching and transients to a single cached request.
* Changed: Block settings panel labels are now more literal. "Spacing" is now "Padding" and "Content Width" is now "Layout" since adding new settings inside.
* Changed: Update the updater package.
* Changed: Simplified breadcrumb output hook/function so they are easier to move while respecting Hide Elements setting.
* Changed: Custom color label and description in Customizer.
* Changed: Paragraphs now use `--body-color` custom property. This makes it much easier to hijack/intercept and change the default text color when using dark backgrounds or any other fitting scenario.
* Changed: Default side spacing (mostly noticeable on mobile) is now slightly larger.
* Changed: Multiple CSS classes can now be used with `[mai_back_to_top]` shortcode via the `class` parameter.
* Fixed: [Accessibility] Added missing screen-reader-text to search submit buttons.
* Fixed: Cover block images being rendered twice due to changes in WP 5.7 for new instances of the block added after 5.7.
* Fixed: Layout bug after saving/updating posts that would cause the logo/menu to be positioned wrong (and other weirdness) until you open the Customizer or transients are flushed.
* Fixed: 404 template part output altering the content in some instances.
* Fixed: Author avatar not showing in `[mai_avatar]` even though the author/user does have one.
* Fixed: Remove unused left/center/right alignment settings in Mai Post/Term Grid block toolbar.
* Fixed: Mai Columns new column appender button position was often hard to click/find.
* Fixed: Mai Delight "Background" color setting was not working with anything other than the default.
* Fixed: Page Header description now works on Blog page.
* Fixed: Margin top/bottom settings on Image block were not overriding default margins in some instances.
* Fixed: Search icon in After Header menu was cutting off the search field when After Header template part was present.
* Fixed: Mobile menu could be cut off if the menu is really tall/long and requires scrolling on smaller devices.
* Fixed: Star ratings in `[mai_rating]` shortcode were not showing the full amount of total stars in some instances.
* Fixed: Warning from a genesis_attr filter referencing 3 parameters when only 1 was called.
* Fixed: Search button styling changes introduced in WP 5.7.
* Fixed: Content before entries on the blog page was stripping inline styles.
* Fixed: Mobile toggle buttons wrongly inheriting button border/shadow when custom properties were used globally.
* Fixed: Custom post types in config.php single-content and archive-content settings now use defaults for any args that are not set.
* Fixed: Scrollbar width is now calculated during a screen resize or orientation change. This is used for precise full width containers due to a browser inconsistency with "100vw" when scrollbars are present.

## 2.11.3 (3/12/21)
* Added: [Developers] New `mai_before_{$template_part}_template_part`, `mai_after_{$template_part}_template_part` action hooks before and after template parts and template part content.
* Added: [Developers] New `mai_menu_defaults` filter for developers to change `[mai_menu]` shortcode defaults.
* Fixed: Logo not centering correctly in some configurations.

## 2.11.2 (3/11/21)
* Fixed: Error when using `[mai_search_form]` shortcode without any attributes.

## 2.11.1 (3/11/21)
* Changed: [Performance] PHP processing for color and typography css is now cached with transients.
* Changed: Customizer plugins link now goes to new location.
* Fixed: Bug in WP 5.7 due to changes in the alignment markup where aligned buttons are not working correctly.

## 2.11.0 (3/10/21)
* Added: New mobile header settings to rearrange elements and display search icon and/or custom content including `[mai_icon]` links. This is great for search, phone, cart icons and anything else you want on mobile.
* Added: New setting to show posts in current category/term in Mai Post Grid. Yay for related posts blocks automatically now.
* Added: New [mai_rating] shortcode to show star ratings. Works great in Custom Content field of Mai Grid blocks or Archive/Single settings in Customizer.
* Added: Author box is now an option in Hide Elements metabox.
* Added: Built in support/styling for Mai Accordion block/plugin.
* Added: [Developers] New `mai_entry_content` filter on display of all entries.
* Added: [Developers] New `mai_cover_block_image_id` filter allows developers to easily swap out the image ID of a Cover block.
* Added: [Developers] New `mai_archive_pagination_link_classes` filter allows developers to change archive pagination classes.
* Added: [Developers] WP-CLI command for developers to flush the font cache via `wp mai flush`.
* Added: [Developers] New `mai_write_to_file()` debugging function for developers to write data to a file when debugging.
* Changed: [Performance] All helper functions now make use of static caching to drop PHP processing time down tremendously.
* Changed: [Performance] Template Parts are now cached with a transient so there is no database query to get the content.
* Changed: [Performance] Icons now have height/width attributes and help eliminate CLS (Cumulative Layout Shift).
* Changed: [Performance] CSS files are now split into smaller files for the performance benefits of HTTP2.
* Changed: [Performance] Only call query args method once per instance of Mai Post/Term Grid block.
* Changed: [Performance] Featured image query is now cached on all default archives and Mai Post/Term Grid instances. There are now even less database queries on every page.
* Changed: [Performance] Template parts content is now parsed during rendering instead of during the query to retrieve them. This improves performance by not running code on template parts that may not be displayed.
* Changed: [Performance] Menu search icons are now rendered in PHP so there is no flash when JS is loaded.
* Changed: Output from `WP_Query` to `get_post()` for `mai_get_post_content()` function. Fixes classes with `is_main_query()` checks in other plugins.
* Changed: Entry padding is now applied to entry-wrap instead of the main entry so we no longer need negative margin on the entry-image when set to "Full".
* Changed: Hide exclude settings if get entries by choice in Mai Post/Term Grid.
* Changed: Buttons now use inline-flex so icons inside buttons are automatically vertically centered.
* Changed: Allow shortcodes and HTML in read more text field. You can now use `[mai_icon]` shortcode in your Read More buttons.
* Changed: Converted all instances of 100vw to new var(--viewport-width) custom property which accounts for scrollbars.
* Changed: Editor background is now always white.
* Changed: Bold/strong text now has break-word CSS applied.
* Changed: Edit comment link is now visible on comments when user has correct privileges.
* Changed: Content width now expands to fill container if the main container width is customized to be larger.
* Changed: Cart total displayed via `[mai_icon]` now displays more consistently regardless of where it's used.
* Changed: Updated pot file for translations.
* Changed: Updated ACF to 5.9.5.
* Changed: Updated all dependencies to use Composer 2.
* Changed: Mai Plugins admin menu content now displays the bundle and all plugins available.
* Fixed: Customizer "Colors" panel now uses color palette for color pickers.
* Fixed: Pages with a layout stored in the database that is no longer registered were not using the correct layout.
* Fixed: Entry pagination not displaying when using page breaks for paginated posts.
* Fixed: Missing fallback for column count when entries are set to display in a single column. Fixes minor horizontal scroll in some scenarios.
* Fixed: Page Header image field was not being used on static front page.
* Fixed: Error with Mai Columns block when using a custom arrangement and "Auto".
* Fixed: Default column count in Mai Columns.
* Fixed: Images in classic editor wider than their container.
* Fixed: Search block styling with various configurations.
* Fixed: More refined button styling in the editor.
* Fixed: Author box avatar not centering correctly on mobile in some instances.
* Fixed: Colors not inheriting correctly when displaying custom content in Mobile Menu template part.
* Fixed: Remove box-shadow on link button style.
* Fixed: Edge-case error when using a gallery shortcode without the default correct parameters.
* Fixed: Minor issue when using async on a script from the config.
* Fixed: [WooCommerce] Shipping and payment style tweaks.
* Fixed: [WooCommerce] Shop table spacing tweaks.
* Fixed: [WooCommerce] Gallery max width not working correctly.
* Fixed: [WooCommerce] Action button styling tweaks.

## 2.10.0 (1/26/21)
* Added: Mai Columns block! Super powerful responsive controls. This is marked as beta while we confirm settings and controls. Let us know how you like it.
* Added: LearnDash support! Courses and course content (lessons/topics/etc.) now have their own Content Archives and Single Content settings.
* Added: LearnDash content now inherits theme styling.
* Added: 404 Page template part.
* Added: Disable entry links setting to Mai Post/Term Grid blocks.
* Added: Link to search Font Awesome icons in Mai Icon block settings.
* Added: Forms/inputs in the editor use the front-end styling.
* Added: Support for native galleries in classic editor for older sites.
* Added: New built in styling for soon to be released update to Genesis eNews Extended plugin.
* Added: Full support and styling for search block button location settings.
* Added: [WooCommerce] New Edit page link to shop page on the front end.
* Changed: Template Parts no longer auto-generate when you trash/delete or visit Template Parts list on the Dashboard. You have to manually initiate via a "Generate Now" button any time default template parts are available and not yet created.
* Changed: Default button word-break is now normal.
* Changed: Header and footer meta fields are now text areas with more room for editing multi-line entry meta.
* Changed: Admin menu location to make sure it's always next to Genesis.
* Changed: Customizer content settings now use post type label for panel names.
* Changed: has-alignfull-first class is now added regardless of whether the page header is displaying on the page.
* Changed: [WooCommerce] login form no longer takes full width of it's container.
* Fixed: Edge-case bug where unused default template parts would get created in the database.
* Fixed: Author archive intro text and description displaying incorrectly or not at all in some configurations.
* Fixed: Entry read more links not aligning to the bottom when no images are displayed.
* Fixed: Cover block image positioning when using plugins that provide custom lazy load solutions.
* Fixed: Text align on adjacent entry navigation in some instances.
* Fixed: Outline button hover not working when a custom background color was set.
* Fixed: Horizontal scroll in some instances (google ads) from sidebar layouts using CSS grid when not necessary (on mobile).
* Fixed: Page header content width not working on Mai Delight.
* Fixed: Page header text color now transitions the inner container background color on Mai Delight.
* Fixed: [WooCommerce] Page header and cover block image position on WooCommerce pages.
* Fixed: [WooCommerce] Shipping method list styling.

## 2.9.2 (1/7/21)
* Added: Custom content option to Hide Elements metabox.
* Added: Links to shortcode documentation related to Header/Footer Meta fields.
* Added: Support for search block button inside setting.
* Added: [Developers] `mai_get_option{$name}` filter for developers to manipulate option values.
* Changed: Header/Footer Meta text fields are now larger textarea fields for easier editing.
* Changed: Updated pot file for translations.
* Fixed: Entry meta not preserving whitespace in between elements.
* Fixed: Anchor link scroll margin top now uses shrunk header height if applicable.
* Fixed: Page header image filter not updating cached variable correctly.
* Fixed: [WooCommerce] Cart styling when cross-sells are displaying.

## 2.9.1 (1/5/21)
* Added: Entry Pagination Type setting to Content Archives panel in the Customizer to choose whether to use numeric or previous/next pagination on archives.
* Changed: Restructured pagination CSS to be more lightweight.

## 2.9.0 (1/4/21)
* Added: Page header content width setting to change the max width of the content in the page header.
* Added: Header left and right menu alignment settings. Now you can align left, right, or center, independently, without extra code.
* Added: Custom content field now available for Mai Post/Term Grid and Single/Archive Content settings.
* Added: `[mai_price]` shortcode to display a product's price in Mai Post Grid or anywhere else you want it.
* Added: Automatically generate dark/light custom properties for primary, secondary, and link colors.
* Changed: Button hover styles no longer use psuedo-element overlay for darkening the color and use auto-generated darker shade color.
* Changed: In order to match front end button styles in the editor, button hover color changes are not present in the editor.
* Changed: Mai Theme admin menu item order.
* Fixed: Extra side spacing on Mai Post Grid block on mobile in some instances.
* Fixed: Logo not centering in some instances when no header left or right menu is set.
* Fixed: Lists not indented in the editor to match the front end.
* Fixed: Page header image not covering full container when using some image optimization plugins.
* Fixed: Mai Inspire Author box author image not centered correctly on mobile.
* Fixed: [WooCommerce] Shop pagination now matches theme archive pagination styling.

## 2.8.0 (12/17/20)
* Added: Header and footer meta options added to Hide Elements metabox.
* Added: [Developers] New `mai_adjacent_entry_nav_taxonomy` filter to allow adjacent entry nav to show previous/next post in same taxonomy.
* Added: Dashboard menu item to show what Mai Plugins are available.
* Added: More Google fonts available via Kirki update.
* Added: Mai Plugins button link in Customizer.
* Changed: Entry meta before/after-content classes now more element specific.
* Changed: Dashboard menu icon SVG now more efficient and no longer flashes on hover.
* Changed: Updated pot file for translations.
* Changed: [WooCommerce] trim zeros filter runs early now, so filters on the default priority override our default.
* Fixed: Shrink header now buttery smooth even when logo shrinks on scroll.
* Fixed: Duplicate search results text in page header.
* Fixed: Mai Post Grid get entries by choice not allowing more than 12 entries.
* Fixed: Mai Term Grid get entries by choice reversing display order.
* Fixed: More thorough has-light/dark-background color CSS.
* Fixed: No longer override menu-item-link classes being filtered by other plugins.
* Fixed: Error when `WP_Widget_Recent_Comments` is no longer available when a plugin (Perfmatters) or custom code removes it altogether.
* Fixed: Anchor links not going to correct location due to conflict with scroll-behavoir CSS.
* Fixed: [WooCommerce] Full align blocks in product descriptions being cut off.
* Fixed: [WooCommerce] Zoom magnifying glass was still showing behind our custom icon.

## 2.7.1 (12/1/20)
* Fixed: Removed PHP 7.3+ function.

## 2.7.0 (11/30/20)
* Added: New `cart_total="true"` parameter for `[mai_icon]` shortcode to display current cart total over an icon (great for menu items).
* Added: New `[mai_cart_total]` shortcode to display current cart total.
* Added: New `[mai_avatar]` shortcode for use in Header/Footer meta fields on single/archive content or in Mai Grid blocks.
* Added: Latest icons from Font Awesome Pro.
* Changed: Widget Areas config syntax now uses id for the array key.
* Changed: Logo spacing value output now output separate properties.
* Changed: Much more thorough and performant header JS.
* Changed: Header/logo shrink animation is now faster when scrolling back to top.
* Fixed: Header jank when shrinking/scrolling in some instances.
* Fixed: Bold font weight not loading or displaying correctly in some instances.
* Fixed: AMP menu not displaying at all, or without styling in some instances.
* Fixed: Error when using spacing or content width settings on a block without content.
* Fixed: More thorough handling of has-light-background helper class.
* Fixed: Body min-height now accounts for admin bar.
* Fixed: Error with first/last menu item class when the first or last item has child menu items.
* Fixed: Cover block inner content aligning incorrectly on layouts with a sidebar.
* Fixed: Menu hover colors when using a dark, transparent header.
* Fixed: [WooCommerce] star ratings not displaying correctly.
* Fixed: [WooCommerce] Better default styling for reviews.

## 2.6.3.1 (11/13/20)
* Fixed: Force update to show.

## 2.6.3 (11/13/20)
* Added: Much more performance template parts query.
* Changed: Reorganized font custom properties for easier overriding when customizing CSS.
* Changed: All font custom properties now output manually in the same function.
* Fixed: Submenu alignment when centering menus in `[mai_menu]` shortcode.

## 2.6.2 (11/12/20)
* Changed: Added has-overlap class to any block that uses overlap margin settings.
* Fixed: Better default Pullquote block styles on front end and editor.
* Fixed: Page Header overlay fallbacks not working correctly in some instances.
* Fixed: Better default font CSS when use standard (non-Google) fonts in Customizer.

## 2.6.1 (11/11/20)
* Changed: Nav menu slug to menu wrap when using `[mai_menu]` shortcode.
* Fixed: Sticky header not sticking to top when logged out.
* Fixed: Syntax error warnings when debugging is on.

## 2.6.0 (11/11/20)
* Added: Documentation and Support links under Mai Theme Dashboard menu.
* Added: Also added Template Parts and Reusable Blocks link under Mai Theme Dashboard menu, leaving them under Appearance as well.
* Added: Margin settings, inclucing Overlap, to Group/Cover/Image blocks.
* Added: Heading and Subheading block styles to the Paragraph block.
* Added: Alternating images setting to Mai Post/Term Grid blocks.
* Added: The block editor now respects content width of chosen layout. Page refresh required when changing the layout in the editor itself.
* Added: New has-light-background helper class to force dark text on Cover block when using light images.
* Added: New has-{size}-margin-left/right helper classes.
* Added: New is-sticky helper class.
* Added: New has-border and has-border-radius helper classes.
* Added: New "Import From Demo" link in Template Parts to automatically import individual template parts from the demo.
* Added: New "Flush local fonts"
* Added: SVG's now work as logos when using a plugin or code to enable SVG uploads.
* Added: Dismissable admin notice on Widgets screen to get to Template Parts.
* Changed: WP toolbar is now fixed positioned on large screens, and uses default positioning from WP.
* Changed: WP core Columns block now uses core breakpoints for responsive behavior.
* Changed: Search block now uses secondary button color.
* Changed: More refined cite/caption CSS styling.
* Changed: Submenus now inherit default border-radius from the theme.
* Changed: Entry meta wrap is now a div instead of span, so you can do more with Header/Footer meta Customizer fields.
* Changed: Remove unnecessary EDD dependency plugin when using Easy Digital Downloads.
* Changed: [Developers] Now passing all `$args` to grid query args PHP filters.
* Changed: Remove unnecessary styling on After Entry template part.
* Changed: Load child theme stylesheet last when "Load in footer" is not on in Customizer.
* Changed: Update ACF Pro to 5.9.3.
* Changed: More efficient image loading when using "Auto" columns in Mai Post/Term Grid blocks.
* Changed: Template Parts uses a new structure in config.php.
* Changed: Simplified menu and submenu CSS.
* Changed: Page Header overlay now allows 0 for overlay, or 1 to use default/fallback overlay.
* Changed: Removed unnecessary border bottom on page header.
* Changed: Added top spacing on button block wrap and entry read more link to match paragraph bottom spacing and line height.
* Changed: Added more button margin to the main entry title on singular content.
* Fixed: Social links block breaking links (adding an extra s in httpss) in some instances.
* Fixed: Bold and italic and sometimes default font weights and variants not loading correctly in some instances.
* Fixed: Google fonts still being loaded even when none are used in Customizer.
* Fixed: Links and headings with long words not wrapping on smaller screens.
* Fixed: More refined transparent/fixed header JS to fix various glitches when resizing/scrolling in some instances.
* Fixed: Fixed background image on Cover block not working when transparent header was enabled.
* Fixed: More performant/efficient background cover image.
* Fixed: More srcset/performance improvements for entry images.
* Fixed: Margin default causing issues on Heading block in some scenarios.
* Fixed: Mobile menu links not visible when using a dark background and sticky header at certain screen widths.
* Fixed: Unecessary :visited styles for buttons.
* Fixed: Button classes being duplicated when multiple menus are on a page.
* Fixed: Search and date archives not using choice in Site Layouts.
* Fixed: PHP error when using Exclude Displayed setting in Mai Post Grid block in some scenarios.
* Fixed: Fatal error when switching directly from a v1 to v2 theme.
* Fixed: Wide align causing content to get cut off in the editor on smaller screens.
* Fixed: Paragraph block padding when setting a background color.
* Fixed: Structured data error with page-header itemref.
* Fixed: Allow menu search icon to work in Before Header template part.
* Fixed: Menu item buttons now use menu font size.
* Fixed: Make sure "Active" post state only shows on Template Parts.
* Fixed: Entry content margins being when nesting entries in some scenarios.
* Fixed: Grid "Display boxed styling" is now available even if not showing images.
* Fixed: Blog description not showing when Page Header was using on the blog archive.
* Fixed: Body and heading Google fonts can now use italic as the default style.

## 2.5.2 (10/2/20)
* Changed: Editors can now edit template parts.
* Changed: Slightly more performant query when getting template parts.
* Fixed: More thorough fix for Mai Divider gap issue.

## 2.5.1 (10/1/20)
* Changed: After Header template part now displays after the After Header Menu location.
* Changed: Color palette now shows the actual color setting name instead of the hex code.
* Changed: Grid/entry images now use more thorough logic to build srcset and size image attributes, for even better performance.
* Changed: All SVG icons now use https for xmlns attribute.
* Changed: Remove underline when hovering entry title links and adjacent entry navigation.
* Changed: [WooCommerce] Much cleaner account navigation styling.
* Fixed: After Header template part now works with transparent header.
* Fixed: Timeout error when trying to view revisions.
* Fixed: Transparent header was showing a gap when after header navigation was displaying.
* Fixed: Menu item button color was not inheriting button color.
* Fixed: Mai Divider would sometimes show a small gap in Chrome/Firefox.
* Fixed: Mai Grid blocks had a side gap when set to Align Full.
* Fixed: Mai Delight blockquote quote was cut off in Safari in some instances.
* Fixed: Mai Delight page header only shows transparent white box if there is content in it.
* Fixed: 404 pages were displaying posts unintentionally in some instances.

## 2.5.0 (9/25/20)
* Added: New After Header template part.
* Added: New Spacing settings to Heading, Paragraph, and Separator blocks.
* Added: New Width settings to Heading and Paragraph blocks.
* Added: The ability to align a menu item left or right with has-auto-margin-left/right.
* Changed: Hide Elements now show all available settings, even if those elements aren't currently displaying.
* Changed: Spacing settings (padding) now scales down slightly less on mobile.
* Changed: Sizing button group labels are now S/M/L instead of SM/MD/LG.
* Changed: Button menu items now have the same spacing as regular non-button menu items.
* Fixed: Some scss calculations converting px to rem with wrong base font size.
* Fixed: More thorough and lightweight background cover images.
* Fixed: Mai Reusable Block widget wasn't showing the saved value when refreshing the page.
* Fixed: Removed extra padding on Group and Cover blocks when they are nested.
* Fixed: [WooCommerce] Even more refined styling to match the theme.
* Fixed: [WooCommerce] Cart layout issues with some add-ons like WooCommerce Product Add-ons.

## 2.4.5 (9/18/20)
* Fixed: [WooCommerce] Cart layout was broken on extra small mobile devices.

## 2.4.4 (9/17/20)
* Added: Support for Mai Delight Travel demo.
* Added: Support for Mai Studio Agency demo.
* Changed: Mai Term Grid only shows top level terms as the default.
* Fixed: Removed link button side padding if it's the first button.
* Fixed: Better check for paginated archives.
* Fixed: List styles in term descriptions.
* Fixed: Make sure links in comments wrap.
* Fixed: Align Full option was missing from Mai Term Grid.
* Fixed: Column gap wasn't working when set to None in some instances.
* Fixed: Search icon in menu was wrongly inheriting border and box shadow in some themes.
* Fixed: Posts per page now set to the theme default when activating the theme.
* Fixed: Typo in page header CSS custom properties.
* Fixed: [WooCommerce] Sale badge was missing on single product pages in some instances.
* Fixed: [WooCommerce] My Account navigation links weren't taking up full container width.
* Fixed: [WooCommerce] Duplicate category descriptions displaying on product category archives in some instances.
* Fixed: [WooCommerce] Shop title size and product count styling.

## 2.4.3 (9/4/20)
* Added: New helper utility classes for `hidden-{breakpoint}`, `hidden-{breakpoint}-up`, and `hidden-{breakpoint}-down` to hide elements at various breakpoints.
* Added: Now use Mai Theme icon as favicon if none set in Customizer.
* Added: Mai Icon now has a setting to open links in new window.
* Changed: Deprecated heading font size settings now that they are in WP core.
* Fixed: Buttons not working correctly/efficiently when adding button classes to menu items.
* Fixed: Author box not 100% width of container in some scenarios.
* Fixed: Default footer credits template part showing a warning/error when trying to edit the text.
* Fixed: Ellipsis was showing on content/excerpts even when text is not truncated.
* Fixed: Mai Grid markup was being output even if no entries matched the query.
* Fixed: Breadcrumbs not matching content side spacing.
* Fixed: Custom button border radius not working in some instances.
* Fixed: Better visited button default styling.
* Fixed: Entries wrap not taking up full width of the container.
* Fixed: Mai Grid entry layout being affected by WooCommerce CSS when a grid is used on a product page.
* Fixed: [WooCommerce] checkout page, and payment gateway layout and styling.
* Fixed: [WooCommerce] product grid showing list styles.
* Fixed: [WooCommerce] Duplicate term descriptions showing on some WooCommerce taxonomy archives.
* Fixed: [WooCommerce] Store notice now matches theme styling.

## 2.4.2 (8/27/20)
* Added: Updated Google Font list in Typography customizer fields via Kirki.
* Added: Before and after content HTML classes to entry meta.
* Added: Screen reader text title to read more links.
* Changed: Updated ACF Pro to 5.9.0.
* Changed: Updated Kirki.
* Changed: Updated the updater.
* Changed: More default left margin on lists in content.
* Changed: Remove unused body classes added from Genesis.
* Changed: After Entry template part is displayed by default if there is content in the template part.
* Fixed: Some site layout settings where not working in some configurations.
* Fixed: Button controls scrolling out of view when editing some blocks.
* Fixed: Spacing settings not showing spacing on blocks in editor.
* Fixed: Spacing and content width setting sizing in editor to fit changes in WP 5.5.
* Fixed: Deprecated `wp_make_content_images_responsive()` in WP 5.5.
* Fixed: Make sure theme CSS is loaded after all engine CSS.
* Fixed: Default submenu dropdown caret alignment was off.
* Fixed: Visible menu's menu items were not wrapping on mobile.
* Fixed: Align center vertically wasn't working correctly in some configurations of grid/archives.
* Fixed: Mai Grid blocks where not full width if nested in other blocks in some scenarios.
* Fixed: Better handling and consistent display of category/term description and intro text, including when page header is enabled.
* Fixed: Author box spacing.
* Fixed: Avatar was not using default size if unmodified.
* Fixed: [WooCommerce] Default button styling now matches the theme better.
* Fixed: [WooCommerce] Gallery image layout and styling tweaks.

## 2.4.1 (8/10/20)
* Added: New entry-wrap-{context} class for simpler CSS targeting.
* Added: Custom property for avatar border-radius.
* Changed: Single entry elements after all Genesis hooks are now outside of the entry-wrap.
* Changed: Revert buttons to use body font family/weight again.
* Fixed: Added back `has-alignfull-first` body class for simpler CSS targeting.
* Fixed: Duplicate cover images when nesting Cover block.
* Fixed: Remove menu item underline when a menu is in a template part.
* Fixed: Search icon in menu getting cut off in Before Header template part.
* Fixed: Page Header background color not working when a page header image is used.
* Fixed: Page Header description now processes the content so you can use curly quotes, shortcodes, HTML, etc.

## 2.4.0 (8/6/20)
* Added: Mai Grid blocks now have a setting to remove bottom spacing.
* Added: Top level template parts now have a div wrap with the template part name.
* Added: Show tagline setting in Site Identity customizer panel.
* Added: Link/URL field to Mai Icon block.
* Added: CSS only anchor link slow scroll, and scroll offset when sticky header is enabled.
* Added: Template Parts now respect Private post status for logged in admins. This is a great way to test before making them live/published.
* Added: Template Parts editor background styles to show transparency.
* Added: Template Parts can now be exported by WP exporter.
* Added: New has-sidebar or no-sidebar body class.
* Changed: Require a default site layout in customizer.
* Changed: Customizer setting defaults are now config based.
* Changed: Row and Column Gap settings are now a scale (sm, md, lg, etc.) instead of a text field for layout and design consistency.
* Changed: Default container margins and main container elements to make full width section layouts easier to remove margin.
* Changed: Font size when button-small class is used on buttons.
* Changed: Archive pagination buttons are now smaller by default.
* Changed: Remove border radius on entries when either row/column gap are 0.
* Changed: Replace clear link on many customizer settings with None/Default/Auto button.
* Changed: Center author bio on bio.
* Changed: Better default quote styling.
* Changed: Better dropcap default styling.
* Changed: Remove unnecessary post classes.
* Changed: Added entry-wrap div on entries for more flexible and consistent styling.
* Changed: Use heading color setting as text color for white buttons.
* Changed: Align full/wide tweaks on mobile when layout has a sidebar.
* Changed: Add itemtype schema to nav menus when using `[mai_menu]` shortcode or helper function.
* Changed: Sub menu toggle icon now correctly scales with menu font size.
* Changed: Remove default margin on separator block.
* Fixed: Site layouts not falling back correctly in some configurations.
* Fixed: Mobile menu displaying duplicate or overlapping menus when using Mobile Menu template part.
* Fixed: Archive/Single layout settings not working correctly on post types.
* Fixed: Template Parts auto creating unintentionally when posts per page is set less than 8.
* Fixed: Stop using the_content filter in template parts as some plugins incorrectly use the filter and add content to the template.
* Fixed: Border radius on entries when Image Position is Background.
* Fixed: Mai Post Grid meta queries not working correctly with some configurations.
* Fixed: Mai Post Grid meta value field not hidden compare is exists or not exists.
* Fixed: Mai Term Grid showing all terms when choosing terms and choices are empty.
* Fixed: Mai Grid block images when columns are set to Auto.
* Fixed: Mai Grid block not hiding Boxed setting when Image Position is Background.
* Fixed: Mai Grid block skewing background images on mobile.
* Fixed: Mai Grid block content overlapping image on mobile when stacking image/content is checked.
* Fixed: Mai Grid block image is always first on mobile if stacking, whether image position is left or right.
* Fixed: Safari desktop/iOS scrollbar causing horizontal scroll with alignfull blocks.
* Fixed: Fallback image not working correctly on page header in some configurations.
* Fixed: Allow page header overlay opacity to be 0.
* Fixed: Center aligned images not correctly centering.
* Fixed: Mobile menu and `[mai_menu]` shortcode styling tweaks.
* Fixed: `[mai_menu]` incorrectly showing a default menu when id is not a valid menu value.
* Fixed: Block container CSS tweaks in the editor.
* Fixed: Button border radius not working on hover when using a custom border radius.
* Fixed: Header gap when using transparent header and refreshing the page when already scrolled down and going back to top.
* Fixed: Default button styles in editor.
* Fixed: Nested group spacing settings overriding each other in editor.
* Fixed: Image size chooser incorrectly showing custom image sizes as the same size.
* Fixed: Transparent header not working correctly in some scenarios of hidden elements and header settings.
* Fixed: Adjacent entry pagination adding top margin incorrectly.
* Fixed: Input placeholder text getting lost on dark backgrounds.
* Fixed: Center aligned search block.
* Fixed: Cite elements not respecting color setting when in a group block.
* Fixed: Empty admin notice showing when deactivating some plugins.
* Fixed: [WooCommerce] onsale badge size.

## 2.3.0 (7/21/20)
* Added: Title size setting to Mai Grid blocks and Content Archive settings.
* Added: Size setting to core Heading block.
* Changed: Use chosen heading color for overlays to keep consistent styling.
* Changed: Removed default bottom margin on Mai Grid and archive entries.
* Changed: Remove border radius on entries if column or row gap is 0.
* Fixed: Inability to sort posts in the Dashboard.
* Fixed: Horizontal scrollbars shown in some scenarios when using full aligned blocks on a page.
* Fixed: Duplicate h1's on homepage. Site title is no longer an h1, ever.
* Fixed: Submit buttons being styled unexpectedly in the Dashboard.

## 2.2.2 (7/16/20)
* Changed: Group and cover blocks no longer have top and bottom default spacing set when adding a new instance of the block.
* Changed: Grid and archive entries now have gradient overlays with background image position when aligning text vertically.
* Changed: Add default footer credits text on new installs when footer credit template part is first created.
* Fixed: Divider not full width when used in Page Header with some configurations.
* Fixed: Default inner content width when using group and cover blocks in template parts.
* Fixed: Transparent header throwing a JS error if site header was hidden.
* Fixed: Template parts unintentionally showing socical icons and other 3rd part content that uses `the_content` filter.
* Fixed: Menu links when displaying a menu in a dark background group block.
* Fixed: Some color CSS not being output correctly.
* Fixed: Mai Grid block with boxed entry was showing white titles when inside a dark background group block.
* Fixed: Mai Grid block not working when columns were set to auto.

## 2.2.1 (7/15/20)
* Changed: Entry titles should be lg instead of xl on archives and grid blocks, as a default.

## 2.2.0 (7/15/20)
* Added: New left and right spacing settings on group and cover blocks.
* Changed: More performant template part query.
* Changed: Remove core block library theme CSS and include only necessary styling inside Mai Engine.
* Changed: Slightly larger line height on buttons.
* Changed: Mai Grid blocks are no longer clickable in the editor.
* Changed: Mai Icon now defaults to center alignment when adding a new instance.
* Fixed: Transparent header when entry title is not hidden.
* Fixed: Mai Icon defaults now work when adding a new block.
* Fixed: Mai Icon selected icon was too large in select field.
* Fixed: Mobile menu JS error in some scenarios.
* Fixed: Image block default margins, mostly a Chrome user agent override.

## 2.1.1 (7/14/20)
* Added: Template Parts and Reusable Blocks links to Toolbar > {Site Title} dropdown menu.
* Changed: Automatically create default template parts so users know what is available.
* Changed: Sub-menu animation is now 200ms. Subtle and noticeable, but not too slow.
* Changed: Updated Kirki to latest version.
* Changed: Automatically disable plugin dependencies (ACF, ACF Pro, Kirki) when installed as separate plugins.
* Fixed: Added missing accessibility skip links for template parts and header nav menus and removed sidebar skip link when there is no sidebar.
* Fixed: More thorough handling of text color when a group block has a dark background color.
* Fixed: After Header navigation not showing in some instances.
* Fixed: Grid/archive images not stacking correctly on mobile.
* Fixed: Transparent header showing when it shouldn't in some configurations.
* Fixed: Image block alignment tweaks.
* Fixed: Social icons block "No background" style not using default text color.
* Fixed: Social icons block alignment.
* Fixed: Nested sub-menu caret icon not pointing in the correct direction.
* Fixed: Setup wizard improvements when run multiple times for different Mai Themes.
* Fixed: Search block styling on small screens.
* Fixed: Cover block styling when using a background image.
* Fixed: Image caption alignment.
* Fixed: Small buttons missing smaller padding.
* Fixed: Missing "Toggle Hooks" link when sorting entry elements.

## 2.1.0 (7/10/20)
* Added: "None" setting to Group/Cover block spacing settings.
* Fixed: Page Header spacing settings not working in some instances.
* Fixed: Mai Divider not displaying correctly when used inside a Group or Cover block.
* Fixed: After Entry template part not displaying correctly.
* Fixed: Template parts not showing as options in the Hide Elements metabox.
* Fixed: Not all color were available in the editor color palette.
* Fixed: New installs of Mai Sparkle not using the correct alternate background color.
* Fixed: Transparent header not displaying correctly when a full aligned Group block was the first block in the content.
* Fixed: Heading colors now respect the color setting in Group and Cover blocks.
* Changed: Removed default margin on align left/right elements.

## 2.0.1 (7/9/20)
* Added: Typography/font settings.
* Changed: Major update to color settings and some Customizer controls. May require some re-configuring of color palettes if already using custom colors. This should be the last change that may require manual attention when upgrading.

## 0.1.0
* Initial release.
