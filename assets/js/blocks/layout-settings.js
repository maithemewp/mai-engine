import assign from 'lodash.assign';

const { __ }                         = wp.i18n;
const { createHigherOrderComponent } = wp.compose;
const { Fragment }                   = wp.element;
const { InspectorControls }          = wp.blockEditor;
const { addFilter }                  = wp.hooks;
const { PanelBody, BaseControl, ButtonGroup, Button } = wp.components;

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

	return settings;
};

addFilter( 'blocks.registerBlockType', 'mai-engine/attribute/layout-settings', addLayoutControlAttribute );

/**
 * Create HOC to add contentWidth control to inspector controls of block.
 */
const withLayoutControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		if ( enableLayoutSettingsBlocks.includes( props.name ) ) {

			const layoutSizeScale = [
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
							title={__( 'Content Width', 'mai-engine' )}
							initialOpen={false}
							className={'mai-width-settings'}
						>
							<SelectControl
								label={__( 'Max Width', 'mai-engine' )}
								value={contentWidth}
								options={sizeScale}
								onChange={( size ) => { props.setAttributes( { contentWidth : size } ) }}
							/>
						</PanelBody>
						<PanelBody
							title={__( 'Spacing', 'mai-engine' )}
							initialOpen={false}
							className={'mai-spacing-settings'}
						>
							<BaseControl
								id="mai-vertical-spacing-top"
								label={__( 'Top', 'mai-engine' )}
							>
								<div>
									<ButtonGroup mode="radio" data-chosen={verticalSpacingTop}>
										{sizeScale.map( sizeInfo => (
											<Button
											onClick={() => {
												props.setAttributes( {
													verticalSpacingTop: sizeInfo.value,
												} );
											}}
											data-checked={verticalSpacingTop === sizeInfo.value}
											value={sizeInfo.value}
											key={`vertical-space-top-${sizeInfo.value}`}
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
										{sizeScale.map( sizeInfo => (
											<Button
												onClick={() => {
													props.setAttributes( {
														verticalSpacingBottom: sizeInfo.value,
													} );
												}}
												data-checked={verticalSpacingBottom === sizeInfo.value}
												value={sizeInfo.value}
												key={`vertical-space-bottom-${sizeInfo.value}`}
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
										{sizeScale.map( sizeInfo => (
											<Button
												onClick={() => {
													props.setAttributes( {
														verticalSpacingLeft: sizeInfo.value,
													} );
												}}
												data-checked={verticalSpacingLeft === sizeInfo.value}
												value={sizeInfo.value}
												key={`vertical-space-left-${sizeInfo.value}`}
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
										{sizeScale.map( sizeInfo => (
											<Button
												onClick={() => {
													props.setAttributes( {
														verticalSpacingRight: sizeInfo.value,
													} );
												}}
												data-checked={verticalSpacingRight === sizeInfo.value}
												value={sizeInfo.value}
												key={`vertical-space-right-${sizeInfo.value}`}
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

			const maxWidthSizeScale = [
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
							title={__( 'Max Width', 'mai-engine' )}
							initialOpen={false}
							className={'mai-width-settings'}
						>
							<SelectControl
								label={__( 'Width', 'mai-engine' )}
								value={maxWidth}
								options={maxWidthSizeScale}
								onChange={( size ) => { props.setAttributes( { maxWidth : size } ) }}
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

}, 'withMaxWidthControls' );

addFilter( 'editor.BlockEdit', 'mai-engine/with-max-width-settings', withMaxWidthControls );


/*****************************
 * Spacing Settings (margin) *
 *****************************/


/**
 * Create HOC to add maxWidth control to inspector controls of block.
 */
const withSpacingControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {

		if ( enableSpacingSettingsBlocks.includes( props.name ) ) {

			// Values mapped to a spacing sizes, labels kept consistent. Matches grid/archive column and row gap.
			const sizeScale = [
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
							title={__( 'Spacing', 'mai-engine' )}
							initialOpen={false}
							className={'mai-spacing-settings'}
						>
							<BaseControl
								id="mai-spacing-top"
								label={__( 'Top', 'mai-engine' )}
							>
								<div>
									<ButtonGroup mode="radio" data-chosen={spacingTop}>
										{sizeScale.map( sizeInfo => (
											<Button
											onClick={() => {
												props.setAttributes( {
													spacingTop: sizeInfo.value,
												} );
											}}
											data-checked={spacingTop === sizeInfo.value}
											value={sizeInfo.value}
											key={`space-top-${sizeInfo.value}`}
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
										{sizeScale.map( sizeInfo => (
											<Button
											onClick={() => {
												props.setAttributes( {
													spacingBottom: sizeInfo.value,
												} );
											}}
											data-checked={spacingBottom === sizeInfo.value}
											value={sizeInfo.value}
											key={`space-top-${sizeInfo.value}`}
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


const addCustomAttributes = createHigherOrderComponent( ( BlockListBlock ) => {
	return ( props ) => {

		const wrapperProps = {};

		if ( enableLayoutSettingsBlocks.includes( props.name ) ) {
			wrapperProps['data-content-width']  = props.attributes.contentWidth;
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
