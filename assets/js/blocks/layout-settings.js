import assign from 'lodash.assign';

const { __ }                         = wp.i18n;
const { createHigherOrderComponent } = wp.compose;
const { Fragment }                   = wp.element;
const { InspectorControls }          = wp.blockEditor;
const { addFilter }                  = wp.hooks;
const { PanelBody, BaseControl, ButtonGroup, Button, SelectControl } = wp.components;

const enableLayoutSettingsBlocks = [
	'core/cover',
	'core/group',
];

const enableMaxWidthSettingsBlocks = [
	'core/heading',
	'core/paragraph',
];

const enableSpacingSettingsBlocks = [
	'core/heading',
	'core/paragraph',
	'core/separator',
];

const enableMarginSettingsBlocks = [
	'core/image',
	'core/cover',
	'core/group',
];

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
		 *
		 * TODO: These should be named spacingTop not verticalSpacingTop since left/right aren't vertical.
		 * I wonder if it's too late to change and safely deprecate?
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
		} );
	}

	if ( enableSpacingSettingsBlocks.includes( name ) ) {
		/**
		 * Use Lodash's assign to gracefully handle if attributes are undefined.
		 */
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

	if ( enableMarginSettingsBlocks.includes( name ) ) {
		/**
		 * Use Lodash's assign to gracefully handle if attributes are undefined.
		 */
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
						<PanelBody
							title={__( 'Padding', 'mai-engine' )}
							initialOpen={false}
							className={'mai-spacing-settings'}
						>
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

			const {
					maxWidth,
				} = props.attributes;

			return (
				<Fragment>
					<BlockEdit {...props} />
					<InspectorControls>
						<PanelBody
							title={__( 'Width', 'mai-engine' )}
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
 * Spacing Settings (padding) *
 *****************************/


/**
 * Create HOC to add SpacingTop and SpacingBottom controls to inspector controls of block.
 */
const withSpacingControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {

		if ( enableSpacingSettingsBlocks.includes( props.name ) ) {

			// Values mapped to a spacing sizes, labels kept consistent. Matches grid/archive column and row gap.
			const widthChoices = [
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
					label: __( 'XXL', 'mai-engine' ),
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
							title={__( 'Margin', 'mai-engine' )}
							initialOpen={false}
							className={'mai-spacing-settings'}
						>
							<BaseControl
								id="mai-spacing-top"
								label={__( 'Top', 'mai-engine' )}
							>
								<div>
									<ButtonGroup mode="radio" data-chosen={spacingTop}>
										{widthChoices.map( sizeInfo => (
											<Button
											onClick={() => {
												props.setAttributes( {
													spacingTop: sizeInfo.value,
												} );
											}}
											data-checked={spacingTop === sizeInfo.value}
											value={sizeInfo.value}
											key={`mai-space-top-${sizeInfo.value}`}
											index={sizeInfo.value}
											isSecondary={spacingTop !== sizeInfo.value}
											isPrimary={spacingTop === sizeInfo.value}
											>
												<small><small>{sizeInfo.label}</small></small>
											</Button>
										) )}
									</ButtonGroup>
									<Button isDestructive isSmall isLink onClick={() => {
										props.setAttributes( {
											spacingTop: null,
										} );
									}}>
										{__( 'Clear', 'mai-engine' )}
									</Button>
								</div>
							</BaseControl>
							<BaseControl
								id="mai-spacing-bottom"
								label={__( 'Bottom', 'mai-engine' )}
							>
								<div>
									<ButtonGroup mode="radio" data-chosen={spacingBottom}>
										{widthChoices.map( sizeInfo => (
											<Button
											onClick={() => {
												props.setAttributes( {
													spacingBottom: sizeInfo.value,
												} );
											}}
											data-checked={spacingBottom === sizeInfo.value}
											value={sizeInfo.value}
											key={`mai-space-top-${sizeInfo.value}`}
											index={sizeInfo.value}
											isSecondary={spacingBottom !== sizeInfo.value}
											isPrimary={spacingBottom === sizeInfo.value}
											>
												<small><small>{sizeInfo.label}</small></small>
											</Button>
										) )}
									</ButtonGroup>
									<Button isDestructive isSmall isLink onClick={() => {
										props.setAttributes( {
											spacingBottom: null,
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

}, 'withSpacingControls' );

addFilter( 'editor.BlockEdit', 'mai-engine/with-spacing-settings', withSpacingControls );

/*******************
 * Margin Settings *
 *******************/

/**
 * Create HOC to add Margin controls to inspector controls of block.
 */
const withMarginControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {

		if ( enableMarginSettingsBlocks.includes( props.name ) ) {

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
					label: __( 'XXL', 'mai-engine' ),
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
							title={__( 'Margin', 'mai-engine' )}
							initialOpen={false}
							className={'mai-margin-settings'}
						>
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

}, 'withMarginControls' );

addFilter( 'editor.BlockEdit', 'mai-engine/with-margin-settings', withMarginControls );


/**********************************
 * Block attributes (editor only) *
 **********************************/


const addCustomAttributes = createHigherOrderComponent( ( BlockListBlock ) => {
	return ( props ) => {

		const wrapperProps = {};

		if ( enableLayoutSettingsBlocks.includes( props.name ) ) {
			wrapperProps['data-content-width']  = props.attributes.contentWidth;
			wrapperProps['data-content-align']  = props.attributes.contentAlign;
			wrapperProps['data-spacing-top']    = props.attributes.verticalSpacingTop;
			wrapperProps['data-spacing-bottom'] = props.attributes.verticalSpacingBottom;
			wrapperProps['data-spacing-left']   = props.attributes.verticalSpacingLeft;
			wrapperProps['data-spacing-right']  = props.attributes.verticalSpacingRight;
		}

		if ( enableMaxWidthSettingsBlocks.includes( props.name ) ) {
			wrapperProps['data-max-width']  = props.attributes.maxWidth;
		}

		if ( enableSpacingSettingsBlocks.includes( props.name ) ) {
			wrapperProps['data-spacing-top']    = props.attributes.spacingTop;
			wrapperProps['data-spacing-bottom'] = props.attributes.spacingBottom;
		}

		if ( enableMarginSettingsBlocks.includes( props.name ) ) {
			wrapperProps['data-margin-top']    = props.attributes.marginTop;
			wrapperProps['data-margin-bottom'] = props.attributes.marginBottom;
			wrapperProps['data-margin-left']   = props.attributes.marginLeft;
			wrapperProps['data-margin-right']  = props.attributes.marginRight;
		}

		if ( wrapperProps ) {
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
