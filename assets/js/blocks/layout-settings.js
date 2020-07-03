import assign from 'lodash.assign';
import classnames from 'classnames';

const { __ }                             = wp.i18n;
const { createHigherOrderComponent }     = wp.compose;
const { Fragment, useState }             = wp.element;
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
		verticalSpacing: {
			type: 'string',
			default: sizeScale[ 2 ].value,
		},
	} );

	return settings;
};

addFilter( 'blocks.registerBlockType', 'mai-engine/attribute/content-width', addSpacingControlAttribute );

const LayoutButtonGroup = ( { label, defaultValue, last } ) => {
	const [ checked, setChecked ] = useState( defaultValue );

	return (
		<ButtonGroup mode="radio" data-chosen={checked} label={__( 'Content Width' )}>
			<p>{label}</p>
			{sizeScale.map( item => (
				<Button
					onClick={() => setChecked( item )}
					data-checked={checked === item}
					value={item}
					key={item}
					isSecondary={checked !== item}
					isPrimary={checked === item}
				>
					{item}
				</Button>
			) )}
			{last === true ? null : ( <p>&nbsp;</p> )}
		</ButtonGroup>
	);
};

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

		const { contentWidth, verticalSpacing } = props.attributes;

		// Here's where we actually add the classes.
		if ( contentWidth ) {
			sizeScale.map( size => {
				props.attributes.className.replace(`has-${size}-content-width`, '')
			} );

			props.attributes.className = ` has-${contentWidth}-content-width`;
		}

		if ( verticalSpacing ) {
			sizeScale.map( size => {
				props.attributes.className.replace(`has-${size}-vertical-spacing`, '')
			} );

			props.attributes.className += ` has-${verticalSpacing}-padding`;
		}

		return (
			<Fragment>
				<BlockEdit {...props} />
				<InspectorControls>
					<PanelBody
						title={__( 'Layout' )}
						initialOpen={true}
					>
						<ButtonGroup mode="radio" data-chosen={contentWidth}>
							<p>{__( 'Content Width' )}</p>
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
						<p>&nbsp;</p>
						<ButtonGroup mode="radio" data-chosen={verticalSpacing}>
							<p>{__( 'Vertical Spacing' )}</p>
							{sizeScale.map( item => (
								<Button
									onClick={() => {
										props.setAttributes( {
											verticalSpacing: item,
										} );
									}}
									data-checked={verticalSpacing === item}
									value={item}
									key={item}
									isSecondary={verticalSpacing !== item}
									isPrimary={verticalSpacing === item}
								>
									{item}
								</Button>
							) )}
						</ButtonGroup>
					</PanelBody>
				</InspectorControls>
			</Fragment>
		);
	};
}, 'withLayoutControls' );

addFilter( 'editor.BlockEdit', 'mai-engine/with-content-width', withLayoutControls );
