import assign from 'lodash.assign';

const { __ }                         = wp.i18n;
const { createHigherOrderComponent } = wp.compose;
const { Fragment }                   = wp.element;
const { InspectorControls }          = wp.blockEditor;
const { addFilter }                  = wp.hooks;
const { PanelBody, BaseControl, SelectControl } = wp.components;


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
					label: __( 'SM', 'mai-engine' ),
					value: 'sm',
				},
				{
					label: __( 'MD', 'mai-engine' ),
					value: 'md',
				},
				{
					label: __( 'LG', 'mai-engine' ),
					value: 'lg',
				},
				{
					label: __( 'XL', 'mai-engine' ),
					value: 'xl',
				},
			];

			const contentWidthSizeScale = [
				{
					label: __( 'Auto', 'mai-engine' ),
					value: '',
				},
				...layoutSizeScale,
			];

			const spacingSizeScale = [
				{
					label: __( 'None', 'mai-engine' ),
					value: '',
				},
				...layoutSizeScale,
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
								options={contentWidthSizeScale}
								onChange={( size ) => { props.setAttributes( { contentWidth : size } ) }}
							/>
						</PanelBody>
						<PanelBody
							title={__( 'Spacing', 'mai-engine' )}
							initialOpen={false}
							className={'mai-spacing-settings'}
						>
							<SelectControl
								label={__( 'Top', 'mai-engine' )}
								value={verticalSpacingTop}
								options={spacingSizeScale}
								onChange={( size ) => { props.setAttributes( { verticalSpacingTop : size } ) }}
							/>
							<SelectControl
								label={__( 'Bottom', 'mai-engine' )}
								value={verticalSpacingBottom}
								options={spacingSizeScale}
								onChange={( size ) => { props.setAttributes( { verticalSpacingBottom : size } ) }}
							/>
							<SelectControl
								label={__( 'Left', 'mai-engine' )}
								value={verticalSpacingLeft}
								options={spacingSizeScale}
								onChange={( size ) => { props.setAttributes( { verticalSpacingLeft : size } ) }}
							/>
							<SelectControl
								label={__( 'Right', 'mai-engine' )}
								value={verticalSpacingRight}
								options={spacingSizeScale}
								onChange={( size ) => { props.setAttributes( { verticalSpacingRight : size } ) }}
							/>
							<p><em>{__( 'Note: Left and Right overlap settings are only applied on larger screens.', 'mai-engine' )}</em></p>
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
					label: __( 'Auto', 'mai-engine' ),
					value: '',
				},
				{
					label: __( 'XS', 'mai-engine' ),
					value: 'xs',
				},
				{
					label: __( 'SM', 'mai-engine' ),
					value: 'sm',
				},
				{
					label: __( 'MD', 'mai-engine' ),
					value: 'md',
				},
				{
					label: __( 'LG', 'mai-engine' ),
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
			const spacingSizeScale = [
				{
					label: __( 'Auto', 'mai-engine' ),
					value: '',
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
					label: __( 'SM', 'mai-engine' ),
					value: 'lg',
				},
				{
					label: __( 'MD', 'mai-engine' ),
					value: 'xl',
				},
				{
					label: __( 'LG', 'mai-engine' ),
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
							<SelectControl
								label={__( 'Top', 'mai-engine' )}
								value={spacingTop}
								options={spacingSizeScale}
								onChange={( size ) => { props.setAttributes( { spacingTop : size } ) }}
							/>
							<SelectControl
								label={__( 'Bottom', 'mai-engine' )}
								value={spacingBottom}
								options={spacingSizeScale}
								onChange={( size ) => { props.setAttributes( { spacingBottom : size } ) }}
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
