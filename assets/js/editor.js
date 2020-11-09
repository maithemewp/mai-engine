const { __ } = wp.i18n;

wp.domReady( () => {
	wp.blocks.unregisterBlockStyle( 'core/button', 'fill' );
	wp.blocks.unregisterBlockStyle( 'core/button', 'outline' );

	wp.blocks.registerBlockStyle( 'core/button', {
		name: 'primary',
		label: __( 'Primary' )
	} );
	wp.blocks.registerBlockStyle( 'core/button', {
		name: 'secondary',
		label: __( 'Secondary' )
	} );
	wp.blocks.registerBlockStyle( 'core/button', {
		name: 'outline',
		label: __( 'Outline' )
	} );
	wp.blocks.registerBlockStyle( 'core/button', {
		name: 'link',
		label: __( 'Link' )
	} );
} );

( function( $ ) {

	if ( 'object' !== typeof acf ) {
		return;
	}

	// ACF color picker default color palette.
	acf.add_filter( 'color_picker_args', function( args, $field ) {
		args.palettes = maiEditorVars.palette;
		return args;
	} );

	var maiIcons = [ 'mai_icon_choices', 'mai_icon_brand_choices' ];
	var maiPost  = maiEditorVars.post;
	var maiTerm  = maiEditorVars.term;
	// var maiUser  = maiEditorVars.user;

	acf.addFilter( 'select2_ajax_data', function( data, args, $input, field, instance ) {

		// If Mai Icon or Icon (Brands) select field.
		if ( maiIcons.includes( data.field_key ) ) {
			data.style = acf.getField( 'mai_icon_style' ).val(); // Style.
		}

		// Mai Post Grid.
		if ( Object.values( maiPost ).includes( data.field_key ) ) {

			// Bail if the post_type field.
			if ( maiPost.post_type === data.field_key ) {
				return data;
			}

			// If Posts/Entries field.
			if ( maiPost.post__in === data.field_key ) {
				data.post_type = getPostType( $input, maiPost );
			}

			// If Exclude Entries field.
			if ( maiPost.post__not_in === data.field_key ) {
				data.post_type = getPostType( $input, maiPost );
			}

			// If Taxonomy field.
			if ( maiPost.taxonomy === data.field_key ) {
				data.post_type = getPostType( $input, maiPost );
			}

			// If Terms field.
			if ( maiPost.terms === data.field_key ) {
				data.taxonomy = getRowTaxonomy( $input, maiPost );
			}

			// If Parent field
			if ( maiPost.post_parent__in === data.field_key ) {
				data.post_type = getPostType( $input, maiPost );
			}

		}

		// Mai Term Grid.
		if ( Object.values( maiTerm ).includes( data.field_key ) ) {

			// Bail if the taxonomy field.
			if ( maiTerm.taxonomy === data.field_key ) {
				return data;
			}

			// If Terms/Entries field.
			if ( maiTerm.include === data.field_key ) {
				data.taxonomy = getTaxonomy( $input, maiTerm );
			}

			// If Exclude Entries field.
			if ( maiTerm.exclude === data.field_key ) {
				data.taxonomy = getTaxonomy( $input, maiTerm );
			}

			// If Parent field.
			if ( maiTerm.parent === data.field_key ) {
				data.taxonomy = getTaxonomy( $input, maiTerm );
			}

		}

		return data;

	} );

	function getPostType( $input, keys ) {
		return acf.getField( keys.post_type ).val();
	}

	function getRowTaxonomy( $input, keys ) {
		var $field = $input.parents( '.acf-field' ).prev( '.acf-field' );
		return acf.getField( $field ).val();
	}

	function getTaxonomy( $input, keys ) {
		return acf.getField( keys.taxonomy ).val();
	}

	/**
	 *  Initialize the field.
	 *
	 *  This function will initialize the $field.
	 */
	function initialize_sortable_field( $field ) {

		// Bail if not a sortable field.
		if ( ! $field.hasClass( 'mai-sortable' ) ) {
			return;
		}

		// Add sortable
		$field.find( '.acf-checkbox-list' ).sortable( {
			items: '> li',
			handle: '> .mai-acf-sortable-handle',
			// forceHelperSize: true,
			placeholder: 'sortable-checkbox-placeholder',
			forcePlaceholderSize: true,
			scroll: true,
			create: function( event, ui ) {
				$( this ).find( 'li' ).append( '<span class="mai-acf-sortable-handle"><i class="dashicons dashicons-menu"></i></span>' );
			},
			stop: function( event, ui ) {},
			update: function( event, ui ) {
				$( this ).find( 'input[type="checkbox"]' ).trigger( 'change' );
			}
		} );

	}

	if ( typeof acf.add_action !== 'undefined' ) {

		/*
		*  ready & append (ACF5)
		*
		*  These two events are called when a field element is ready for initizliation.
		*  - ready: on page load similar to $(document).ready()
		*  - append: on new DOM elements appended via repeater field or other AJAX calls
		*/

		acf.add_action( 'ready_field/key=mai_grid_block_show', initialize_sortable_field );
		acf.add_action( 'append_field/key=mai_grid_block_show', initialize_sortable_field );

	}

} )( jQuery );
