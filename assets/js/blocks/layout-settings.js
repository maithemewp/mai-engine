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

// Filter out spacing css classes to preserve other additional classes
const removeFromClassName = ( className, classArray ) => {
	return ( className || '' ).split( ' ' )
		.filter( classString => ! classArray.includes( classString ) )
		.join( ' ' )
		.replace( /\s+/g, ' ' ) // Remove superfluous whitespace
		.trim();
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

		const {
				  contentWidth,
				  verticalSpacingTop,
				  verticalSpacingBottom,
				  verticalSpacingLeft,
				  verticalSpacingRight,
			  } = props.attributes;

		const layoutSettings = [
			{
				name: 'content-width',
				value: contentWidth,
				classes: [
					'has-xs-content-width',
					'has-sm-content-width',
					'has-md-content-width',
					'has-lg-content-width',
					'has-xl-content-width',
				]
			},
			{
				name: 'padding-top',
				value: verticalSpacingTop,
				classes: [
					'has-xs-padding-top',
					'has-sm-padding-top',
					'has-md-padding-top',
					'has-lg-padding-top',
					'has-xl-padding-top',
				]
			},
			{
				name: 'padding-bottom',
				value: verticalSpacingBottom,
				classes: [
					'has-xs-padding-bottom',
					'has-sm-padding-bottom',
					'has-md-padding-bottom',
					'has-lg-padding-bottom',
					'has-xl-padding-bottom',
				]
			},
			{
				name: 'padding-left',
				value: verticalSpacingLeft,
				classes: [
					'has-xs-padding-left',
					'has-sm-padding-left',
					'has-md-padding-left',
					'has-lg-padding-left',
					'has-xl-padding-left',
				]
			},
			{
				name: 'padding-right',
				value: verticalSpacingRight,
				classes: [
					'has-xs-padding-right',
					'has-sm-padding-right',
					'has-md-padding-right',
					'has-lg-padding-right',
					'has-xl-padding-right',
				]
			},
		];

		layoutSettings.map( setting => {
			const existingClasses = removeFromClassName( props.attributes.className, setting.classes );

			props.attributes.className = setting.value ?
				`has-${ setting.value }-${ setting.name } ${ existingClasses }` :
				existingClasses;
		} );

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
						<p>&nbsp;</p>
						<ButtonGroup mode="radio" data-chosen={verticalSpacingLeft}>
							<p>{__( 'Left Spacing', 'mai-engine' )}</p>
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
									{sizeInfo.label}
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
						<p>&nbsp;</p>
						<ButtonGroup mode="radio" data-chosen={verticalSpacingRight}>
							<p>{__( 'Right Spacing', 'mai-engine' )}</p>
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
									{sizeInfo.label}
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
					</PanelBody>
				</InspectorControls>
			</Fragment>
		);
	};
}, 'withLayoutControls' );

addFilter( 'editor.BlockEdit', 'mai-engine/with-layout-settings', withLayoutControls );
