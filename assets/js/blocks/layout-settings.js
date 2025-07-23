/**
 * To rebuild this script you need to run:
npm install
npm run blocks
 *
 * Since we're using gulp 3 still you may need to force NPM version:
export NVM_DIR="$HOME/.nvm"
[ -s "/usr/local/opt/nvm/nvm.sh" ] && . "/usr/local/opt/nvm/nvm.sh"
nvm use 11.15.0
find . -name ".DS_Store" -delete
 */
import assign from 'lodash.assign';

const { __ }                         = wp.i18n;
const { createHigherOrderComponent } = wp.compose;
const { Fragment, useEffect }        = wp.element;
const { InspectorControls }          = wp.blockEditor;
const { addFilter }                  = wp.hooks;
const { PanelBody, BaseControl, ButtonGroup, Button, SelectControl } = wp.components;

// Content Width, Max Width, Content Align, Padding.
const enableLayoutSettingsBlocks = [
	'core/cover',
	'core/group',
];

// Max Width.
const enableMaxWidthSettingsBlocks = [
	'core/heading',
	'core/paragraph',
];

// Legacy spacing blocks (deprecated - will migrate to core spacing).
const enableLegacySpacingSettingsBlocks = [
	'core/heading',
	'core/paragraph',
	'core/separator',
];

// Legacy margin blocks (deprecated - will migrate to core spacing).
const enableLegacyMarginSettingsBlocks = [
	'core/image',
	'core/cover',
	'core/group',
];

/**
 * Spacing value mapping from legacy to core spacing scale.
 */
const spacingValueMap = {
	// Padding mapping
	'no': '0',
	'xs': '20',
	'sm': '30',
	'md': '40',
	'lg': '50',
	'xl': '60',

	// Margin mapping
	'md': '40',
	'lg': '50',
	'xl': '60',
	'xxl': '70',
	'xxxl': '80',
	'xxxxl': '90',

	// Negative margin mapping - WordPress core spacing doesn't support negative values
	// These will remain as legacy attributes
	'-md': '-md',
	'-lg': '-lg',
	'-xl': '-xl',
	'-xxl': '-xxl',
	'-xxxl': '-xxxl',
	'-xxxxl': '-xxxxl',
};

/**
 * Check if block supports core spacing.
 */
const blockSupportsCoreSpacing = (blockName) => {
	const blockType = wp.blocks.getBlockType(blockName);
	return blockType && blockType.supports && blockType.supports.spacing;
};

/**
 * Migrate legacy spacing values to core spacing.
 */
const migrateSpacingValue = (legacyValue) => {
	if (!legacyValue || legacyValue === '') {
		return '';
	}

	// Don't migrate negative values - WordPress core spacing doesn't support them
	if (legacyValue.startsWith('-')) {
		return legacyValue; // Keep as legacy attribute
	}

	// Map to core spacing scale values
	const mappedValue = spacingValueMap[legacyValue];
	if (mappedValue) {
		// WordPress core spacing uses the format: var:preset|spacing|{slug}
		return `var:preset|spacing|${mappedValue}`;
	}

	return legacyValue;
};

/**
 * Add layout control attributes to block.
 *
 * @param {object} settings Current block settings.
 * @param {string} name Name of block.
 *
 * @returns {object} Modified block settings.
 */
const addLayoutControlAttribute = ( settings, name ) => {
	if ( enableLayoutSettingsBlocks.includes( name ) ) {
		/**
		 * Use Lodash's assign to gracefully handle if attributes are undefined.
		 */
		settings.attributes = assign( settings.attributes, {
			contentWidth: {
				type: 'string',
				default: '',
			},
			contentAlign: {
				type: 'string',
				default: '',
			},
			// Legacy spacing attributes (deprecated)
			verticalSpacingTop: {
				type: 'string',
				default: '',
			},
			verticalSpacingBottom: {
				type: 'string',
				default: '',
			},
			verticalSpacingLeft: {
				type: 'string',
				default: '',
			},
			verticalSpacingRight: {
				type: 'string',
				default: '',
			},
		} );
	}

	if ( enableMaxWidthSettingsBlocks.includes( name ) ) {
		/**
		 * Use Lodash's assign to gracefully handle if attributes are undefined.
		 */
		settings.attributes = assign( settings.attributes, {
			maxWidth: {
				type: 'string',
				default: '',
			},
			contentAlign: {
				type: 'string',
				default: '',
			},
		} );
	}

	// Add legacy spacing attributes for backward compatibility
	if ( enableLegacySpacingSettingsBlocks.includes( name ) ) {
		settings.attributes = assign( settings.attributes, {
			spacingTop: {
				type: 'string',
				default: '',
			},
			spacingBottom: {
				type: 'string',
				default: '',
			},
		} );
	}

	// Add legacy margin attributes for backward compatibility
	if ( enableLegacyMarginSettingsBlocks.includes( name ) ) {
		settings.attributes = assign( settings.attributes, {
			marginTop: {
				type: 'string',
				default: '',
			},
			marginBottom: {
				type: 'string',
				default: '',
			},
			marginLeft: {
				type: 'string',
				default: '',
			},
			marginRight: {
				type: 'string',
				default: '',
			},
		} );
	}

	return settings;
};

addFilter( 'blocks.registerBlockType', 'mai-engine/attribute/layout-settings', addLayoutControlAttribute );

/**
 * Create HOC to add contentWidth and Spacing controls to inspector controls of block.
 */
const withLayoutControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		if ( enableLayoutSettingsBlocks.includes( props.name ) ) {

			const contentWidthChoices = [
				{
					label: __( 'XS', 'mai-engine' ),
					value: 'xs',
				},
				{
					label: __( 'S', 'mai-engine' ),
					value: 'sm',
				},
				{
					label: __( 'M', 'mai-engine' ),
					value: 'md',
				},
				{
					label: __( 'L', 'mai-engine' ),
					value: 'lg',
				},
				{
					label: __( 'XL', 'mai-engine' ),
					value: 'xl',
				},
				{
					label: __( 'Full', 'mai-engine' ),
					value: 'no',
				},
			];

			const alignChoices = [
				{
					label: __( 'Start', 'mai-engine' ),
					value: 'start',
				},
				{
					label: __( 'Center', 'mai-engine' ),
					value: 'center',
				},
				{
					label: __( 'Right', 'mai-engine' ),
					value: 'end',
				},
			];

			const paddingChoices = [
				{
					label: __( 'XS', 'mai-engine' ),
					value: 'xs',
				},
				{
					label: __( 'S', 'mai-engine' ),
					value: 'sm',
				},
				{
					label: __( 'M', 'mai-engine' ),
					value: 'md',
				},
				{
					label: __( 'L', 'mai-engine' ),
					value: 'lg',
				},
				{
					label: __( 'XL', 'mai-engine' ),
					value: 'xl',
				},
			];

			const {
					contentWidth,
					contentAlign,
					verticalSpacingTop,
					verticalSpacingBottom,
					verticalSpacingLeft,
					verticalSpacingRight,
				} = props.attributes;

			return (
				<Fragment>
					<BlockEdit {...props} />
					<InspectorControls>
						<PanelBody
							title={__( 'Layout', 'mai-engine' )}
							initialOpen={false}
							className={'mai-content-width-align-settings'}
						>
							<BaseControl
								id="mai-content-width"
								label={__( 'Content Max Width', 'mai-engine' )}
							>
								<div>
									<ButtonGroup mode="radio" data-chosen={contentWidth}>
										{contentWidthChoices.map( sizeInfo => (
											<Button
											onClick={() => {
												props.setAttributes( {
													contentWidth: sizeInfo.value,
												} );
											}}
											data-checked={contentWidth === sizeInfo.value}
											value={sizeInfo.value}
											key={`mai-content-width-${sizeInfo.value}`}
											index={sizeInfo.value}
											isSecondary={contentWidth !== sizeInfo.value}
											isPrimary={contentWidth === sizeInfo.value}
											>
												<small>{sizeInfo.label}</small>
											</Button>
										) )}
									</ButtonGroup>
									<Button isDestructive isSmall isLink onClick={() => {
										props.setAttributes( {
											contentWidth: null,
										} );
									}}>
										{__( 'Clear', 'mai-engine' )}
									</Button>
								</div>
							</BaseControl>
							<BaseControl
								id="mai-content-align"
								label={__( 'Content Alignment', 'mai-engine' )}
							>
								<div>
									<ButtonGroup mode="radio" data-chosen={contentAlign}>
										{alignChoices.map( alignInfo => (
											<Button
											onClick={() => {
												props.setAttributes( {
													contentAlign: alignInfo.value,
												} );
											}}
											data-checked={contentAlign === alignInfo.value}
											value={alignInfo.value}
											key={`mai-content-align-${alignInfo.value}`}
											index={alignInfo.value}
											isSecondary={contentAlign !== alignInfo.value}
											isPrimary={contentAlign === alignInfo.value}
											>
												<small>{alignInfo.label}</small>
											</Button>
										) )}
									</ButtonGroup>
									<Button isDestructive isSmall isLink onClick={() => {
										props.setAttributes( {
											contentAlign: null,
										} );
									}}>
										{__( 'Clear', 'mai-engine' )}
									</Button>
								</div>
							</BaseControl>
						</PanelBody>
						{/* Legacy padding controls - only show if block doesn't support core spacing */}
						{!blockSupportsCoreSpacing(props.name) && (
							<PanelBody
								title={__( 'Padding (Legacy)', 'mai-engine' )}
								initialOpen={false}
								className={'mai-spacing-settings'}
							>
								<p><em>{ __( 'Note: This is a legacy control. Consider using the core spacing controls instead.', 'mai-engine' ) }</em></p>
								<BaseControl
									id="mai-vertical-spacing-top"
									label={__( 'Top', 'mai-engine' )}
								>
									<div>
										<ButtonGroup mode="radio" data-chosen={verticalSpacingTop}>
											{paddingChoices.map( sizeInfo => (
												<Button
												onClick={() => {
													props.setAttributes( {
														verticalSpacingTop: sizeInfo.value,
													} );
												}}
												data-checked={verticalSpacingTop === sizeInfo.value}
												value={sizeInfo.value}
												key={`mai-vertical-space-top-${sizeInfo.value}`}
												index={sizeInfo.value}
												isSecondary={verticalSpacingTop !== sizeInfo.value}
												isPrimary={verticalSpacingTop === sizeInfo.value}
												>
													<small>{sizeInfo.label}</small>
												</Button>
											) )}
										</ButtonGroup>
										<Button isDestructive isSmall isLink onClick={() => {
											props.setAttributes( {
												verticalSpacingTop: null,
											} );
										}}>
											{__( 'Clear', 'mai-engine' )}
										</Button>
									</div>
								</BaseControl>
								<BaseControl
									id="mai-vertical-spacing-bottom"
									label={__( 'Bottom', 'mai-engine' )}
								>
									<div>
										<ButtonGroup mode="radio" data-chosen={verticalSpacingBottom}>
											{paddingChoices.map( sizeInfo => (
												<Button
													onClick={() => {
														props.setAttributes( {
															verticalSpacingBottom: sizeInfo.value,
														} );
													}}
													data-checked={verticalSpacingBottom === sizeInfo.value}
													value={sizeInfo.value}
													key={`mai-vertical-space-bottom-${sizeInfo.value}`}
													index={sizeInfo.value}
													isSecondary={verticalSpacingBottom !== sizeInfo.value}
													isPrimary={verticalSpacingBottom === sizeInfo.value}
												>
													<small>{sizeInfo.label}</small>
												</Button>
											) )}
										</ButtonGroup>
										<Button isDestructive isSmall isLink onClick={() => {
											props.setAttributes( {
												verticalSpacingBottom: null,
											} );
										}}>
											{__( 'Clear', 'mai-engine' )}
										</Button>
									</div>
								</BaseControl>
								<BaseControl
									id="mai-vertical-spacing-left"
									label={__( 'Left', 'mai-engine' )}
								>
									<div>
										<ButtonGroup mode="radio" data-chosen={verticalSpacingLeft}>
											{paddingChoices.map( sizeInfo => (
												<Button
													onClick={() => {
														props.setAttributes( {
															verticalSpacingLeft: sizeInfo.value,
														} );
													}}
													data-checked={verticalSpacingLeft === sizeInfo.value}
													value={sizeInfo.value}
													key={`mai-vertical-space-left-${sizeInfo.value}`}
													index={sizeInfo.value}
													isSecondary={verticalSpacingLeft !== sizeInfo.value}
													isPrimary={verticalSpacingLeft === sizeInfo.value}
												>
													<small>{sizeInfo.label}</small>
												</Button>
											) )}
										</ButtonGroup>
										<Button isDestructive isSmall isLink onClick={() => {
											props.setAttributes( {
												verticalSpacingLeft: null,
											} );
										}}>
											{__( 'Clear', 'mai-engine' )}
										</Button>
									</div>
								</BaseControl>
								<BaseControl
									id="mai-vertical-spacing-right"
									label={__( 'Right', 'mai-engine' )}
								>
									<div>
										<ButtonGroup mode="radio" data-chosen={verticalSpacingRight}>
											{paddingChoices.map( sizeInfo => (
												<Button
													onClick={() => {
														props.setAttributes( {
															verticalSpacingRight: sizeInfo.value,
														} );
													}}
													data-checked={verticalSpacingRight === sizeInfo.value}
													value={sizeInfo.value}
													key={`mai-vertical-space-right-${sizeInfo.value}`}
													index={sizeInfo.value}
													isSecondary={verticalSpacingRight !== sizeInfo.value}
													isPrimary={verticalSpacingRight === sizeInfo.value}
												>
													<small>{sizeInfo.label}</small>
												</Button>
											) )}
										</ButtonGroup>
										<Button isDestructive isSmall isLink onClick={() => {
											props.setAttributes( {
												verticalSpacingRight: null,
											} );
										}}>
											{__( 'Clear', 'mai-engine' )}
										</Button>
									</div>
								</BaseControl>
							</PanelBody>
						)}
					</InspectorControls>
				</Fragment>
			);
		}

		return (
			<BlockEdit {...props} />
		);
	};

}, 'withLayoutControls' );

addFilter( 'editor.BlockEdit', 'mai-engine/with-layout-settings', withLayoutControls );


/**********************
 * Max Width Settings *
 **********************/


/**
 * Create HOC to add maxWidth control to inspector controls of block.
 */
const withMaxWidthControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {

		if ( enableMaxWidthSettingsBlocks.includes( props.name ) ) {

			const widthChoices = [
				{
					label: __( 'XS', 'mai-engine' ),
					value: 'xs',
				},
				{
					label: __( 'S', 'mai-engine' ),
					value: 'sm',
				},
				{
					label: __( 'M', 'mai-engine' ),
					value: 'md',
				},
				{
					label: __( 'L', 'mai-engine' ),
					value: 'lg',
				},
				{
					label: __( 'XL', 'mai-engine' ),
					value: 'xl',
				},
			];

			const alignChoices = [
				{
					label: __( 'Start', 'mai-engine' ),
					value: 'start',
				},
				{
					label: __( 'Center', 'mai-engine' ),
					value: 'center',
				},
				{
					label: __( 'Right', 'mai-engine' ),
					value: 'end',
				},
			];

			const {
					maxWidth,
					contentAlign,
				} = props.attributes;

			return (
				<Fragment>
					<BlockEdit {...props} />
					<InspectorControls>
						<PanelBody
							title={__( 'Layout', 'mai-engine' )}
							initialOpen={false}
							className={'mai-width-settings'}
						>
							<BaseControl
								id="mai-width"
								label={__( 'Max Width', 'mai-engine' )}
							>
								<div>
									<ButtonGroup mode="radio" data-chosen={maxWidth}>
										{widthChoices.map( sizeInfo => (
											<Button
											onClick={() => {
												props.setAttributes( {
													maxWidth: sizeInfo.value,
												} );
											}}
											data-checked={maxWidth === sizeInfo.value}
											value={sizeInfo.value}
											key={`mai-width-${sizeInfo.value}`}
											index={sizeInfo.value}
											isSecondary={maxWidth !== sizeInfo.value}
											isPrimary={maxWidth === sizeInfo.value}
											>
												<small>{sizeInfo.label}</small>
											</Button>
										) )}
									</ButtonGroup>
									<Button isDestructive isSmall isLink onClick={() => {
										props.setAttributes( {
											maxWidth: null,
										} );
									}}>
										{__( 'Clear', 'mai-engine' )}
									</Button>
								</div>
							</BaseControl>
							<BaseControl
								id="mai-width-content-align"
								label={__( 'Content Alignment', 'mai-engine' )}
							>
								<div>
									<ButtonGroup mode="radio" data-chosen={contentAlign}>
										{alignChoices.map( alignInfo => (
											<Button
											onClick={() => {
												props.setAttributes( {
													contentAlign: alignInfo.value,
												} );
											}}
											data-checked={contentAlign === alignInfo.value}
											value={alignInfo.value}
											key={`mai-width-content-align-${alignInfo.value}`}
											index={alignInfo.value}
											isSecondary={contentAlign !== alignInfo.value}
											isPrimary={contentAlign === alignInfo.value}
											>
												<small>{alignInfo.label}</small>
											</Button>
										) )}
									</ButtonGroup>
									<Button isDestructive isSmall isLink onClick={() => {
										props.setAttributes( {
											contentAlign: null,
										} );
									}}>
										{__( 'Clear', 'mai-engine' )}
									</Button>
								</div>
							</BaseControl>
						</PanelBody>
					</InspectorControls>
				</Fragment>
			);
		}

		return (
			<BlockEdit {...props} />
		);
	};

}, 'withMaxWidthControls' );

addFilter( 'editor.BlockEdit', 'mai-engine/with-max-width-settings', withMaxWidthControls );


/*****************************
 * Legacy Spacing Settings *
 *****************************/


/**
 * Create HOC to add legacy SpacingTop and SpacingBottom controls to inspector controls of block.
 * @deprecated Use core spacing controls instead.
 */
const withLegacySpacingControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {

		if ( enableLegacySpacingSettingsBlocks.includes( props.name ) && !blockSupportsCoreSpacing(props.name) ) {

			// Values mapped to a spacing sizes, labels kept consistent. Matches grid/archive column and row gap.
			const marginTopBottomChoices = [
				{
					label: __( 'Default', 'mai-engine' ),
					value: '',
				},
				{
					label: __( 'None', 'mai-engine' ),
					value: 'no',
				},
				{
					label: __( 'XXS', 'mai-engine' ),
					value: 'sm',
				},
				{
					label: __( 'XS', 'mai-engine' ),
					value: 'md',
				},
				{
					label: __( 'S', 'mai-engine' ),
					value: 'lg',
				},
				{
					label: __( 'M', 'mai-engine' ),
					value: 'xl',
				},
				{
					label: __( 'L', 'mai-engine' ),
					value: 'xxl',
				},
				{
					label: __( 'XL', 'mai-engine' ),
					value: 'xxxl',
				},
				{
					label: __( '2XL', 'mai-engine' ),
					value: 'xxxxl',
				},
			];

			const {
					spacingTop,
					spacingBottom,
				} = props.attributes;

			return (
				<Fragment>
					<BlockEdit {...props} />
					<InspectorControls>
						<PanelBody
								title={__( 'Margin (Legacy)', 'mai-engine' )}
								initialOpen={false}
								className={'mai-margin-settings'}
							>
							<p><em>{ __( 'Note: This is a legacy control. Consider using the core spacing controls instead.', 'mai-engine' ) }</em></p>
							<SelectControl
								label={ __( 'Top', 'mai-engine' ) }
								value={ spacingTop }
								onChange={ ( spacingTop ) => {
									props.setAttributes( {
										spacingTop: spacingTop,
									} );
								}}
								options={ marginTopBottomChoices }
							/>
							<SelectControl
								label={ __( 'Bottom', 'mai-engine' ) }
								value={ spacingBottom }
								onChange={ ( spacingBottom ) => {
									props.setAttributes( {
										spacingBottom: spacingBottom,
									} );
								}}
								options={ marginTopBottomChoices }
							/>
						</PanelBody>
					</InspectorControls>
				</Fragment>
			);
		}

		return (
			<BlockEdit {...props} />
		);
	};

}, 'withLegacySpacingControls' );

addFilter( 'editor.BlockEdit', 'mai-engine/with-legacy-spacing-settings', withLegacySpacingControls );

/*******************
 * Legacy Margin Settings *
 *******************/

/**
 * Create HOC to add legacy Margin controls to inspector controls of block.
 * @deprecated Use core spacing controls instead.
 */
const withLegacyMarginControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {

		if ( enableLegacyMarginSettingsBlocks.includes( props.name ) && !blockSupportsCoreSpacing(props.name) ) {

			// Values mapped to a spacing sizes, labels kept consistent. Matches grid/archive column and row gap.
			const widthChoices = [
				{
					label: __( 'Default', 'mai-engine' ),
					value: '',
				},
				{
					label: __( 'None', 'mai-engine' ),
					value: 'no',
				},
				{
					label: __( 'XS', 'mai-engine' ),
					value: 'md',
				},
				{
					label: __( 'S', 'mai-engine' ),
					value: 'lg',
				},
				{
					label: __( 'M', 'mai-engine' ),
					value: 'xl',
				},
				{
					label: __( 'L', 'mai-engine' ),
					value: 'xxl',
				},
				{
					label: __( 'XL', 'mai-engine' ),
					value: 'xxxl',
				},
				{
					label: __( '2XL', 'mai-engine' ),
					value: 'xxxxl',
				},
				{
					label: __( 'XS Overlap', 'mai-engine' ),
					value: '-md',
				},
				{
					label: __( 'S Overlap', 'mai-engine' ),
					value: '-lg',
				},
				{
					label: __( 'M Overlap', 'mai-engine' ),
					value: '-xl',
				},
				{
					label: __( 'L Overlap', 'mai-engine' ),
					value: '-xxl',
				},
				{
					label: __( 'XL Overlap', 'mai-engine' ),
					value: '-xxxl',
				},
				{
					label: __( 'XXL Overlap', 'mai-engine' ),
					value: '-xxxxl',
				},
			];

			const {
					marginTop,
					marginBottom,
					marginLeft,
					marginRight,
				} = props.attributes;

			return (
				<Fragment>
					<BlockEdit {...props} />
					<InspectorControls>
						<PanelBody
							title={__( 'Margin (Legacy)', 'mai-engine' )}
							initialOpen={false}
							className={'mai-margin-settings'}
						>
							<p><em>{ __( 'Note: This is a legacy control. Consider using the core spacing controls instead.', 'mai-engine' ) }</em></p>
							<SelectControl
								label={ __( 'Top', 'mai-engine' ) }
								value={ marginTop }
								onChange={ ( marginTop ) => {
									props.setAttributes( {
										marginTop: marginTop,
									} );
								}}
								options={ widthChoices }
							/>
							<SelectControl
								label={ __( 'Bottom', 'mai-engine' ) }
								value={ marginBottom }
								onChange={ ( marginBottom ) => {
									props.setAttributes( {
										marginBottom: marginBottom,
									} );
								}}
								options={ widthChoices }
							/>
							<SelectControl
								label={ __( 'Left', 'mai-engine' ) }
								value={ marginLeft }
								onChange={ ( marginLeft ) => {
									props.setAttributes( {
										marginLeft: marginLeft,
									} );
								}}
								options={ widthChoices }
							/>
							<SelectControl
								label={ __( 'Right', 'mai-engine' ) }
								value={ marginRight }
								onChange={ ( marginRight ) => {
									props.setAttributes( {
										marginRight: marginRight,
									} );
								}}
								options={ widthChoices }
							/>
							<p><em>{ __( 'Note: Left/right overlap settings are disabled on smaller screens.', 'mai-engine' ) }</em></p>
						</PanelBody>
					</InspectorControls>
				</Fragment>
			);
		}

		return (
			<BlockEdit {...props} />
		);
	};

}, 'withLegacyMarginControls' );

addFilter( 'editor.BlockEdit', 'mai-engine/with-legacy-margin-settings', withLegacyMarginControls );


/**********************************
 * Block attributes (editor only) *
 **********************************/


const addCustomAttributes = createHigherOrderComponent( ( BlockListBlock ) => {
	return ( props ) => {

		const wrapperProps = {};

		if ( enableLayoutSettingsBlocks.includes( props.name ) ) {
			wrapperProps['data-content-width']  = props.attributes.contentWidth;
			wrapperProps['data-content-align']  = props.attributes.contentAlign;

			// Legacy spacing attributes - only apply if no core spacing exists
			if (!props.attributes.style || !props.attributes.style.spacing || !props.attributes.style.spacing.padding) {
				wrapperProps['data-spacing-top']    = props.attributes.verticalSpacingTop;
				wrapperProps['data-spacing-bottom'] = props.attributes.verticalSpacingBottom;
				wrapperProps['data-spacing-left']   = props.attributes.verticalSpacingLeft;
				wrapperProps['data-spacing-right']  = props.attributes.verticalSpacingRight;
			}
		}

		if ( enableMaxWidthSettingsBlocks.includes( props.name ) ) {
			wrapperProps['data-max-width']     = props.attributes.maxWidth;
			wrapperProps['data-content-align'] = props.attributes.contentAlign;
		}

		// Legacy spacing attributes - only apply if no core spacing exists
		if ( enableLegacySpacingSettingsBlocks.includes( props.name ) && (!props.attributes.style || !props.attributes.style.spacing || !props.attributes.style.spacing.margin) ) {
			wrapperProps['data-spacing-top']    = props.attributes.spacingTop;
			wrapperProps['data-spacing-bottom'] = props.attributes.spacingBottom;
		}

		// Legacy margin attributes - only apply if no core spacing exists
		if ( enableLegacyMarginSettingsBlocks.includes( props.name ) && (!props.attributes.style || !props.attributes.style.spacing || !props.attributes.style.spacing.margin) ) {
			wrapperProps['data-margin-top']    = props.attributes.marginTop;
			wrapperProps['data-margin-bottom'] = props.attributes.marginBottom;
			wrapperProps['data-margin-left']   = props.attributes.marginLeft;
			wrapperProps['data-margin-right']  = props.attributes.marginRight;
		}

		if ( Object.keys(wrapperProps).length > 0 ) {
			return (
				<BlockListBlock {...props} wrapperProps={wrapperProps}/>
			);
		}

		return (
			<BlockListBlock {...props}/>
		);
	};

}, 'addCustomAttributes' );

addFilter( 'editor.BlockListBlock', 'mai-engine/add-custom-attributes', addCustomAttributes );


/**********************************
 * Block Migration Support *
 **********************************/

/**
 * Add migration support to blocks that have legacy spacing attributes.
 */
const addMigrationSupport = ( settings, name ) => {
	// Only add migration for blocks that had legacy spacing
	const hasLegacySpacing = enableLayoutSettingsBlocks.includes( name ) ||
							enableLegacySpacingSettingsBlocks.includes( name ) ||
							enableLegacyMarginSettingsBlocks.includes( name );



	if ( hasLegacySpacing && settings.deprecated ) {
		// Add our migration to existing deprecated versions
		settings.deprecated = settings.deprecated.map( deprecated => {
			if ( deprecated.migrate ) {
				// Wrap existing migrate function
				const originalMigrate = deprecated.migrate;
				deprecated.migrate = ( attributes, innerBlocks ) => {
					// Call original migrate function
					const migratedAttributes = originalMigrate( attributes, innerBlocks );

					// Apply our migration
					return migrateLegacySpacingAttributes( migratedAttributes );
				};
			} else {
				// Add our migration function
				deprecated.migrate = ( attributes ) => {
					return migrateLegacySpacingAttributes( attributes );
				};
			}
			return deprecated;
		});
	} else if ( hasLegacySpacing ) {
		// Add deprecated version with migration
		settings.deprecated = settings.deprecated || [];
		settings.deprecated.push({
			attributes: assign( {}, settings.attributes, {
				// Add legacy attributes for migration
				verticalSpacingTop: { type: 'string' },
				verticalSpacingBottom: { type: 'string' },
				verticalSpacingLeft: { type: 'string' },
				verticalSpacingRight: { type: 'string' },
				spacingTop: { type: 'string' },
				spacingBottom: { type: 'string' },
				marginTop: { type: 'string' },
				marginBottom: { type: 'string' },
				marginLeft: { type: 'string' },
				marginRight: { type: 'string' },
			}),
			migrate: ( attributes ) => {
				return migrateLegacySpacingAttributes( attributes );
			},
		});
	}

	return settings;
};

/**
 * Migrate legacy spacing attributes to core spacing format.
 */
const migrateLegacySpacingAttributes = ( attributes ) => {
	console.log( 'Migrating legacy spacing attributes:', attributes );
	const migratedAttributes = assign( {}, attributes );

	// Initialize style object if it doesn't exist
	if ( !migratedAttributes.style ) {
		migratedAttributes.style = {};
	}
	if ( !migratedAttributes.style.spacing ) {
		migratedAttributes.style.spacing = {};
	}

	// Migrate padding attributes
	if ( attributes.verticalSpacingTop || attributes.verticalSpacingBottom ||
		 attributes.verticalSpacingLeft || attributes.verticalSpacingRight ) {

		if ( !migratedAttributes.style.spacing.padding ) {
			migratedAttributes.style.spacing.padding = {};
		}

		if ( attributes.verticalSpacingTop ) {
			migratedAttributes.style.spacing.padding.top = migrateSpacingValue( attributes.verticalSpacingTop );
		}
		if ( attributes.verticalSpacingBottom ) {
			migratedAttributes.style.spacing.padding.bottom = migrateSpacingValue( attributes.verticalSpacingBottom );
		}
		if ( attributes.verticalSpacingLeft ) {
			migratedAttributes.style.spacing.padding.left = migrateSpacingValue( attributes.verticalSpacingLeft );
		}
		if ( attributes.verticalSpacingRight ) {
			migratedAttributes.style.spacing.padding.right = migrateSpacingValue( attributes.verticalSpacingRight );
		}

		// Remove legacy attributes
		delete migratedAttributes.verticalSpacingTop;
		delete migratedAttributes.verticalSpacingBottom;
		delete migratedAttributes.verticalSpacingLeft;
		delete migratedAttributes.verticalSpacingRight;
	}

	// Migrate margin attributes
	if ( attributes.spacingTop || attributes.spacingBottom ) {
		if ( !migratedAttributes.style.spacing.margin ) {
			migratedAttributes.style.spacing.margin = {};
		}

		if ( attributes.spacingTop ) {
			migratedAttributes.style.spacing.margin.top = migrateSpacingValue( attributes.spacingTop );
		}
		if ( attributes.spacingBottom ) {
			migratedAttributes.style.spacing.margin.bottom = migrateSpacingValue( attributes.spacingBottom );
		}

		// Remove legacy attributes
		delete migratedAttributes.spacingTop;
		delete migratedAttributes.spacingBottom;
	}

	if ( attributes.marginTop || attributes.marginBottom ||
		 attributes.marginLeft || attributes.marginRight ) {

		if ( !migratedAttributes.style.spacing.margin ) {
			migratedAttributes.style.spacing.margin = {};
		}

		if ( attributes.marginTop ) {
			migratedAttributes.style.spacing.margin.top = migrateSpacingValue( attributes.marginTop );
		}
		if ( attributes.marginBottom ) {
			migratedAttributes.style.spacing.margin.bottom = migrateSpacingValue( attributes.marginBottom );
		}
		if ( attributes.marginLeft ) {
			migratedAttributes.style.spacing.margin.left = migrateSpacingValue( attributes.marginLeft );
		}
		if ( attributes.marginRight ) {
			migratedAttributes.style.spacing.margin.right = migrateSpacingValue( attributes.marginRight );
		}

		// Remove legacy attributes
		delete migratedAttributes.marginTop;
		delete migratedAttributes.marginBottom;
		delete migratedAttributes.marginLeft;
		delete migratedAttributes.marginRight;
	}

	return migratedAttributes;
};

addFilter( 'blocks.registerBlockType', 'mai-engine/migration-support', addMigrationSupport );

/**
 * Migrate legacy blocks when they're parsed from content.
 */
const migrateLegacyBlocks = ( block ) => {
	// Check if block has legacy spacing attributes
	const hasLegacySpacing = block.attributes && (
		block.attributes.verticalSpacingTop ||
		block.attributes.verticalSpacingBottom ||
		block.attributes.verticalSpacingLeft ||
		block.attributes.verticalSpacingRight ||
		block.attributes.spacingTop ||
		block.attributes.spacingBottom ||
		block.attributes.marginTop ||
		block.attributes.marginBottom ||
		block.attributes.marginLeft ||
		block.attributes.marginRight
	);

	if ( hasLegacySpacing ) {
		console.log( 'Migrating legacy block:', block.name, block.attributes );
		block.attributes = migrateLegacySpacingAttributes( block.attributes );
	}

	// Recursively migrate inner blocks
	if ( block.innerBlocks ) {
		block.innerBlocks = block.innerBlocks.map( migrateLegacyBlocks );
	}

	return block;
};

/**
 * Migrate legacy blocks when they're edited in the editor.
 */
const withLegacyMigration = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		// Use useEffect to handle migration after render
		useEffect( () => {
			// Check if block has legacy spacing attributes that need migration
			const hasLegacySpacing = props.attributes && (
				props.attributes.verticalSpacingTop ||
				props.attributes.verticalSpacingBottom ||
				props.attributes.verticalSpacingLeft ||
				props.attributes.verticalSpacingRight ||
				props.attributes.spacingTop ||
				props.attributes.spacingBottom ||
				props.attributes.marginTop ||
				props.attributes.marginBottom ||
				props.attributes.marginLeft ||
				props.attributes.marginRight
			);

						if ( hasLegacySpacing && (!props.attributes.style || !props.attributes.style.spacing) ) {
				// Migrate the attributes
				const migratedAttributes = migrateLegacySpacingAttributes( props.attributes );

				// Update the block attributes
				props.setAttributes( migratedAttributes );
			}
		}, [props.attributes] ); // Only run when attributes change

		return <BlockEdit {...props} />;
	};
}, 'withLegacyMigration' );

addFilter( 'editor.BlockEdit', 'mai-engine/with-legacy-migration', withLegacyMigration );
