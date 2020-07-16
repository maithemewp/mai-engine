# Changelog

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
* Changed: Major update to color settings and some Customizer controls. May require some reconfiguring of color palettes if already using custom colors. This should be the last change that may require manual attention when upgrading.

## 0.1.0
* Initial release.
