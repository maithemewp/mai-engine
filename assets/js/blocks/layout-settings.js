import assign from 'lodash.assign';

const { __ }                             = wp.i18n;
const { createHigherOrderComponent }     = wp.compose;
const { Fragment }                       = wp.element;
const { InspectorControls }              = wp.editor;
const { addFilter }                      = wp.hooks;
const { PanelBody, Button, ButtonGroup } = wp.components;

const enableSpacingControlOnBlocks = [
	'core/cover',
	'core/group',
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

	// Use Lodash's assign to gracefully handle if attributes are undefined
	settings.attributes = assign( settings.attributes, {
		contentWidth: {
			type: 'string',
			default: '',
		},
	} );

	settings.attributes = assign( settings.attributes, {
		verticalSpacingTop: {
			type: 'string',
			default: 'md',
		},
	} );

	settings.attributes = assign( settings.attributes, {
		verticalSpacingBottom: {
			type: 'string',
			default: 'md',
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

		if ( ! props.attributes.className ) {
			props.attributes.className = '';
		}

		// Clear all of our classes so we're not compiling various class names.
		sizeScale.map( sizeInfo => {
			props.attributes.className.replace( `has-${sizeInfo.value}-content-width`, '' );
			props.attributes.className.replace( `has-${sizeInfo.value}-padding-top`, '' );
			props.attributes.className.replace( `has-${sizeInfo.value}-padding-bottom`, '' );
		} );

		const { contentWidth, verticalSpacingTop, verticalSpacingBottom } = props.attributes;

		// Start new class string.
		let newClasses = '';

		if ( contentWidth ) {
			newClasses += ` has-${contentWidth}-content-width`;
		}

		if ( verticalSpacingTop ) {
			newClasses += ` has-${verticalSpacingTop}-padding-top`;
		}

		if ( verticalSpacingBottom ) {
			newClasses += ` has-${verticalSpacingBottom}-padding-bottom`;
		}

		// Set our new classes.
		props.attributes.className = newClasses.trim();

		return (
			<Fragment>
				<BlockEdit {...props} />
				<InspectorControls>
					<PanelBody
						title={__( 'Layout', 'mai-engine' )}
						initialOpen={true}
						className={'mai-layout-settings'}
					>
						<ButtonGroup mode="radio" data-chosen={contentWidth}>
							<p>{__( 'Content Width', 'mai-engine' )}</p>
							{sizeScale.map( sizeInfo => (
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
									{sizeInfo.label}
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
						<p>&nbsp;</p>
						<ButtonGroup mode="radio" data-chosen={verticalSpacingTop}>
							<p>{__( 'Top Spacing', 'mai-engine' )}</p>
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
									{sizeInfo.label}
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
						<p>&nbsp;</p>
						<ButtonGroup mode="radio" data-chosen={verticalSpacingBottom}>
							<p>{__( 'Bottom Spacing', 'mai-engine' )}</p>
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
									{sizeInfo.label}
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
					</PanelBody>
				</InspectorControls>
			</Fragment>
		);
	};
}, 'withLayoutControls' );

addFilter( 'editor.BlockEdit', 'mai-engine/with-layout-settings', withLayoutControls );
