import assign from 'lodash.assign';

const { __ }                         = wp.i18n;
const { createHigherOrderComponent } = wp.compose;
const { Fragment }                   = wp.element;
const { InspectorControls }          = wp.blockEditor;
const { addFilter }                  = wp.hooks;
const { PanelBody, BaseControl, ButtonGroup, Button } = wp.components;

const enableSpacingControlOnBlocks = [
	'core/cover',
	'core/group'
];

const sizeScale = [
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

const contentSizeScale = [
	{
		label: __( 'Auto', 'mai-engine' ),
		value: '',
	},
	...sizeScale
];

const spacingSizeScale = [
	{
		label: __( 'None', 'mai-engine' ),
		value: '',
	},
	...sizeScale
];

/**
 * Add layout control attribute to block.
 *
 * @param {object} settings Current block settings.
 * @param {string} name Name of block.
 *
 * @returns {object} Modified block settings.
 */
const addSpacingControlAttribute = ( settings, name ) => {

	// Do nothing if it's another block than our defined ones.
	if ( ! enableSpacingControlOnBlocks.includes( name ) ) {
		return settings;
	}

	/**
	 * Use Lodash's assign to gracefully handle if attributes are undefined.
	 *
	 * TODO: These should be named verticalSpacingTop not verticalSpacingTop since left/right aren't vertical.
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

	return settings;
};

addFilter( 'blocks.registerBlockType', 'mai-engine/attribute/layout-settings', addSpacingControlAttribute );

/**
 * Create HOC to add contentWidth control to inspector controls of block.
 */
const withLayoutControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {

		// Do nothing if it's another block than our defined ones.
		if ( ! enableSpacingControlOnBlocks.includes( props.name ) ) {
			return (
				<BlockEdit {...props} />
			);
		}

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
						title={__( 'Layout', 'mai-engine' )}
						initialOpen={false}
						className={'mai-layout-settings'}
					>
						<BaseControl
							id="mai-content-width"
							label={__( 'Content Width', 'mai-engine' )}
						>
							<div>
								<ButtonGroup mode="radio" data-chosen={contentWidth}>
									{contentSizeScale.map( sizeInfo => (
										<Button
											onClick={() => {
												props.setAttributes( {
													contentWidth: sizeInfo.value,
												} );
											}}
											data-checked={contentWidth === sizeInfo.value}
											value={sizeInfo.value}
											key={`content-width-${sizeInfo.value}`}
											index={sizeInfo.value}
											isSecondary={contentWidth !== sizeInfo.value}
											isPrimary={contentWidth === sizeInfo.value}
										>
											<small>{sizeInfo.label}</small>
										</Button>
									) )}
								</ButtonGroup>
							</div>
						</BaseControl>
					</PanelBody>
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
								<ButtonGroup mode="radio" data-chosen={verticalSpacingTop}>
									{spacingSizeScale.map( sizeInfo => (
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
							</div>
						</BaseControl>
						<BaseControl
							id="mai-spacing-bottom"
							label={__( 'Bottom', 'mai-engine' )}
						>
							<div>
								<ButtonGroup mode="radio" data-chosen={verticalSpacingBottom}>
									{spacingSizeScale.map( sizeInfo => (
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
							</div>
						</BaseControl>
						<BaseControl
							id="mai-spacing-left"
							label={__( 'Left', 'mai-engine' )}
						>
							<div>
								<ButtonGroup mode="radio" data-chosen={verticalSpacingLeft}>
									{spacingSizeScale.map( sizeInfo => (
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
							</div>
						</BaseControl>
						<BaseControl
							id="mai-spacing-right"
							label={__( 'Right', 'mai-engine' )}
						>
							<div>
								<ButtonGroup mode="radio" data-chosen={verticalSpacingRight}>
									{spacingSizeScale.map( sizeInfo => (
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
							</div>
						</BaseControl>
					</PanelBody>
				</InspectorControls>
			</Fragment>
		);
	};
}, 'withLayoutControls' );

addFilter( 'editor.BlockEdit', 'mai-engine/with-layout-settings', withLayoutControls );

const addLayoutAttributes = createHigherOrderComponent( ( BlockListBlock ) => {
	return ( props ) => {

		// Do nothing if it's another block than our defined ones.
		if ( ! enableSpacingControlOnBlocks.includes( props.name ) ) {
			return (
				<BlockListBlock {...props} />
			);
		}

		return (
			<BlockListBlock {...props} wrapperProps={
				{
					'data-content-width': props.attributes.contentWidth,
					'data-spacing-top': props.attributes.verticalSpacingTop,
					'data-spacing-bottom': props.attributes.verticalSpacingBottom,
					'data-spacing-left': props.attributes.verticalSpacingLeft,
					'data-spacing-right': props.attributes.verticalSpacingRight,
				}
			}/>
		);
	};
}, 'addLayoutAttributes' );

addFilter( 'editor.BlockListBlock', 'mai-engine/addLayoutAttributes', addLayoutAttributes );
