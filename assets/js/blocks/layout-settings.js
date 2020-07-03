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
	'xs',
	'sm',
	'md',
	'lg',
	'xl',
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
			default: sizeScale[ 2 ].value,
		},
	} );

	settings.attributes = assign( settings.attributes, {
		verticalSpacingTop: {
			type: 'string',
			default: sizeScale[ 2 ].value,
		},
	} );

	settings.attributes = assign( settings.attributes, {
		verticalSpacingBottom: {
			type: 'string',
			default: sizeScale[ 2 ].value,
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

		const { contentWidth, verticalSpacingTop, verticalSpacingBottom } = props.attributes;

		// Here's where we actually add the classes.
		if ( contentWidth ) {
			sizeScale.map( size => {
				props.attributes.className.replace( `has-${size}-content-width`, '' );
			} );

			props.attributes.className = ` has-${contentWidth}-content-width`;
		} else {
			sizeScale.map( size => {
				props.attributes.className.replace( `has-${size}-content-width`, '' );
			} );
		}

		if ( verticalSpacingTop ) {
			sizeScale.map( size => {
				props.attributes.className.replace( `has-${size}-vertical-spacing-top`, '' );
			} );

			props.attributes.className += ` has-${verticalSpacingTop}-padding-top`;
		} else {
			sizeScale.map( size => {
				props.attributes.className.replace( `has-${size}-vertical-spacing-top`, '' );
			} );
		}

		if ( verticalSpacingBottom ) {
			sizeScale.map( size => {
				props.attributes.className.replace( `has-${size}-vertical-spacing-bottom`, '' );
			} );

			props.attributes.className += ` has-${verticalSpacingBottom}-padding-bottom`;
		} else {
			sizeScale.map( size => {
				props.attributes.className.replace( `has-${size}-vertical-spacing-bottom`, '' );
			} );
		}

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
							{sizeScale.map( item => (
								<Button
									onClick={() => {
										props.setAttributes( {
											contentWidth: item,
										} );
									}}
									data-checked={contentWidth === item}
									value={item}
									key={item}
									isSecondary={contentWidth !== item}
									isPrimary={contentWidth === item}
								>
									{item}
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
							{sizeScale.map( item => (
								<Button
									onClick={() => {
										props.setAttributes( {
											verticalSpacingTop: item,
										} );
									}}
									data-checked={verticalSpacingTop === item}
									value={item}
									key={item}
									isSecondary={verticalSpacingTop !== item}
									isPrimary={verticalSpacingTop === item}
								>
									{item}
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
							{sizeScale.map( item => (
								<Button
									onClick={() => {
										props.setAttributes( {
											verticalSpacingBottom: item,
										} );
									}}
									data-checked={verticalSpacingBottom === item}
									value={item}
									key={item}
									isSecondary={verticalSpacingBottom !== item}
									isPrimary={verticalSpacingBottom === item}
								>
									{item}
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
