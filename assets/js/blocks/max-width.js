import assign from 'lodash.assign';

const { __ }                         = wp.i18n;
const { createHigherOrderComponent } = wp.compose;
const { Fragment }                   = wp.element;
const { InspectorControls }          = wp.blockEditor;
const { addFilter }                  = wp.hooks;
const { PanelBody, BaseControl, ButtonGroup, Button } = wp.components;

const enableMaxWidthControlOnBlocks = [
	'core/paragraph',
	'core/heading'
];

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

/**
 * Add layout control attribute to block.
 *
 * @param {object} settings Current block settings.
 * @param {string} name Name of block.
 *
 * @returns {object} Modified block settings.
 */
const addMaxWidthControlAttribute = ( settings, name ) => {

	// Do nothing if it's another block than our defined ones.
	if ( ! enableMaxWidthControlOnBlocks.includes( name ) ) {
		return settings;
	}

	/**
	 * Use Lodash's assign to gracefully handle if attributes are undefined.
	 */
	settings.attributes = assign( settings.attributes, {
		maxWidth: {
			type: 'string',
			default: '',
		},
	} );

	return settings;
};

addFilter( 'blocks.registerBlockType', 'mai-engine/attribute/max-width-settings', addMaxWidthControlAttribute );

/**
 * Create HOC to add maxWidth control to inspector controls of block.
 */
const withMaxWidthControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {

		// Do nothing if it's another block than our defined ones.
		if ( ! enableMaxWidthControlOnBlocks.includes( props.name ) ) {
			return (
				<BlockEdit {...props} />
			);
		}

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
						className={'mai-max-width-settings'}
					>
						<BaseControl
							id="max-max-width"
							label={__( 'Max Width', 'mai-engine' )}
						>
							<div>
								<ButtonGroup mode="radio" data-chosen={maxWidth}>
									{maxWidthSizeScale.map( sizeInfo => (
										<Button
											onClick={() => {
												props.setAttributes( {
													maxWidth: sizeInfo.value,
												} );
											}}
											data-checked={maxWidth === sizeInfo.value}
											value={sizeInfo.value}
											key={`max-width-${sizeInfo.value}`}
											index={sizeInfo.value}
											isSecondary={maxWidth !== sizeInfo.value}
											isPrimary={maxWidth === sizeInfo.value}
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
}, 'withMaxWidthControls' );

addFilter( 'editor.BlockEdit', 'mai-engine/with-max-width-settings', withMaxWidthControls );

const addMaxWidthAttributes = createHigherOrderComponent( ( BlockListBlock ) => {
	return ( props ) => {

		// Do nothing if it's another block than our defined ones.
		if ( ! enableMaxWidthControlOnBlocks.includes( props.name ) ) {
			return (
				<BlockListBlock {...props} />
			);
		}

		return (
			<BlockListBlock {...props} wrapperProps={
				{
					'data-max-width': props.attributes.maxWidth,
				}
			}/>
		);
	};
}, 'addMaxWidthAttributes' );

addFilter( 'editor.BlockListBlock', 'mai-engine/addMaxWidthAttributes', addMaxWidthAttributes );
