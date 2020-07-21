import assign from 'lodash.assign';

const { __ }                             = wp.i18n;
const { createHigherOrderComponent }     = wp.compose;
const { Fragment }                       = wp.element;
const { InspectorControls }              = wp.blockEditor;
const { addFilter }                      = wp.hooks;
const { PanelBody, Button, ButtonGroup } = wp.components;

const enableFontSizeControlOnBlocks = [
	'core/heading',
];

/**
 * Labels purposely don't match values.
 * The label is consistent with the "Title Size" settings in grid/archive,
 * but the values are the actual output property/class value.
 *
 * If this doesn't make sense we can change labels without breaking the actual size used.
 */
const sizeScale = [
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

/**
 * Add font size control attribute to block.
 *
 * @param {object} settings Current block settings.
 * @param {string} name Name of block.
 *
 * @returns {object} Modified block settings.
 */
const addFontSizeControlAttribute = ( settings, name ) => {

	// Do nothing if it's another block than our defined ones.
	if ( ! enableFontSizeControlOnBlocks.includes( name ) ) {
		return settings;
	}

	// Use Lodash's assign to gracefully handle if attributes are undefined.
	settings.attributes = assign( settings.attributes, {
		fontSize: {
			type: 'string',
			default: '',
		},
	} );

	return settings;
};

addFilter( 'blocks.registerBlockType', 'mai-engine/attribute/font-size-settings', addFontSizeControlAttribute );

/**
 * Create HOC to add contentWidth control to inspector controls of block.
 */
const withFontSizeControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {

		// Do nothing if it's another block than our defined ones.
		if ( ! enableFontSizeControlOnBlocks.includes( props.name ) ) {
			return (
				<BlockEdit {...props} />
			);
		}

		const {
				  fontSize,
			  } = props.attributes;

		return (
			<Fragment>
				<BlockEdit {...props} />
				<InspectorControls>
					<PanelBody
						title={__( 'Size settings', 'mai-engine' )}
						initialOpen={false}
						className={'mai-font-size-setting'}
					>
						<ButtonGroup mode="radio" data-chosen={fontSize}>
							<p>{__( 'Size', 'mai-engine' )}</p>
							{sizeScale.map( sizeInfo => (
								<Button
									onClick={() => {
										props.setAttributes( {
											fontSize: sizeInfo.value,
										} );
									}}
									data-checked={fontSize === sizeInfo.value}
									value={sizeInfo.value}
									key={`font-size-${sizeInfo.value}`}
									index={sizeInfo.value}
									isSecondary={fontSize !== sizeInfo.value}
									isPrimary={fontSize === sizeInfo.value}
								>
									{sizeInfo.label}
								</Button>
							) )}
						</ButtonGroup>
						<Button isDestructive isSmall isLink onClick={() => {
							props.setAttributes( {
								fontSize: null,
							} );
						}}>
							{__( 'Clear', 'mai-engine' )}
						</Button>
						<p/>
					</PanelBody>
				</InspectorControls>
			</Fragment>
		);
	};
}, 'withFontSizeControls' );

addFilter( 'editor.BlockEdit', 'mai-engine/with-font-size-settings', withFontSizeControls );

const addFontSizeAttributes = createHigherOrderComponent( ( BlockListBlock ) => {
	return ( props ) => {

		// Do nothing if it's another block than our defined ones.
		if ( ! enableFontSizeControlOnBlocks.includes( props.name ) ) {
			return (
				<BlockListBlock {...props} />
			);
		}

		return (
			<BlockListBlock {...props} wrapperProps={
				{
					'data-font-size': props.attributes.fontSize,
				}
			}/>
		);
	};
}, 'addFontSizeAttributes' );

addFilter( 'editor.BlockListBlock', 'mai-engine/addFontSizeAttributes', addFontSizeAttributes );
