import assign from 'lodash.assign';

const { createHigherOrderComponent } = wp.compose;
const { Fragment }                   = wp.element;
const { InspectorControls }          = wp.editor;
const { PanelBody, SelectControl }   = wp.components;
const { addFilter }                  = wp.hooks;
const { __ }                         = wp.i18n;

const enableSpacingControlOnBlocks = [
	'core/cover',
	'core/group',
];

const containerWidthOptions = [
	{
		label: __( 'Default' ),
		value: '',
	},
	{
		label: __( 'Extra Small' ),
		value: 'xs',
	},
	{
		label: __( 'Small' ),
		value: 'sm',
	},
	{
		label: __( 'Medium' ),
		value: 'md',
	},
	{
		label: __( 'Large' ),
		value: 'lg',
	},
	{
		label: __( 'Extra Large' ),
		value: 'xl',
	},
];

/**
 * Add spacing control attribute to block.
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

	// Use Lodash's assign to gracefully handle if attributes are undefined
	settings.attributes = assign( settings.attributes, {
		spacing: {
			type: 'string',
			default: containerWidthOptions[ 0 ].value,
		},
	} );

	return settings;
};

addFilter( 'blocks.registerBlockType', 'mai-content-width/attribute/content-width', addSpacingControlAttribute );

/**
 * Create HOC to add spacing control to inspector controls of block.
 */
const withContentWidthControl = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		// Do nothing if it's another block than our defined ones.
		if ( ! enableSpacingControlOnBlocks.includes( props.name ) ) {
			return (
				<BlockEdit {...props} />
			);
		}

		const { spacing } = props.attributes;

		// add has-spacing-xy class to block
		if ( spacing ) {
			props.attributes.className = `has-${ spacing }-content-width`;
		}

		return (
			<Fragment>
				<BlockEdit {...props} />
				<InspectorControls>
					<PanelBody
						title={__( 'Content Width' )}
						initialOpen={true}
					>
						<SelectControl
							label={__( '' )}
							value={spacing}
							options={containerWidthOptions}
							onChange={( selectedSpacingOption ) => {
								props.setAttributes( {
									spacing: selectedSpacingOption,
								} );
							}}
						/>
					</PanelBody>
				</InspectorControls>
			</Fragment>
		);
	};
}, 'withContentWidthControl' );

addFilter( 'editor.BlockEdit', 'mai-content-width/with-content-width', withContentWidthControl );
