jQuery(document).ready(function($) {

	if ( 'object' !== typeof acf ) {
		return
	}

	// ACF color picker default color palette.
	acf.add_filter( 'color_picker_args', function( args, $field ) {
		args.palettes = maiEditorVars.palette;
		return args;
	});

	var icons = [ "field_5e3f4bcd978f9", "field_5e3f4bcd867e8" ];
	var post  = maiEditorVars.post;
	var term  = maiEditorVars.term;
	var user  = maiEditorVars.user;

	acf.addFilter( 'select2_ajax_data', function( data, args, $input, field, instance ) {

		// If Mai Icon or Icon (Brands) select field.
		if ( icons.includes( data.field_key ) ) {
			data.style = acf.getField( 'field_5e3f49758c633' ).val(); // Style.
		}

		// Mai Post Grid.
		// if ( $.inArray( data.field_key, Object.values( post ) ) >= 0 ) {
		if ( Object.values( post ).includes( data.field_key ) ) {

			// Bail if the post_type field.
			if ( post.post_type === data.field_key ) {
				return data;
			}

			// If Posts/Entries field.
			if ( post.post__in === data.field_key ) {
				data.post_type = getPostType( $input, post );
			}

			// If Exclude Entries field.
			if ( post.post__not_in === data.field_key ) {
				data.post_type = getPostType( $input, post );
			}

			// If Taxonomy field.
			if ( post.taxonomy === data.field_key ) {
				data.post_type = getPostType( $input, post );
			}

			// If Terms field.
			if ( post.terms === data.field_key ) {
				data.taxonomy = getRowTaxonomy( $input, post );
			}

			// If Parent field
			if ( post.post_parent__in === data.field_key ) {
				data.post_type = getPostType( $input, post );
			}

		}

		// Mai Term Grid.
		// if ( $.inArray( data.field_key, Object.values( term ) ) >= 0 ) {
		if ( Object.values( term ).includes( data.field_key ) ) {

			// Bail if the taxonomy field.
			if ( term.taxonomy === data.field_key ) {
				return data;
			}

			// If Terms/Entries field.
			if ( term.include === data.field_key ) {
				data.taxonomy = getTaxonomy( $input, term );
			}

			// If Exclude Entries field.
			if ( term.exclude === data.field_key ) {
				data.taxonomy = getTaxonomy( $input, term );
			}

			// If Parent field.
			if ( term.parent === data.field_key ) {
				data.taxonomy = getTaxonomy( $input, term );
			}

		}

		return data;

	});

	function getPostType( $input, keys ) {
		return acf.getField( keys.post_type ).val();
	}

	function getRowTaxonomy( $input, keys ) {
		return acf.getField( keys.taxonomy ).val();
	}


	function getTaxonomy( $input, keys ) {
		return acf.getField( keys.taxonomy ).val();
	}

});



jQuery(document).ready(function($) {

	if ( 'object' !== typeof acf ) {
		return
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
		$field.find( '.acf-checkbox-list' ).sortable({
			items: '> li',
			handle: '> .mai-acf-sortable-handle',
			// forceHelperSize: true,
			placeholder: 'sortable-checkbox-placeholder',
			forcePlaceholderSize: true,
			scroll: true,
			create: function(event, ui) {
				$(this).find( 'li' ).append( '<span class="mai-acf-sortable-handle"><i class="dashicons dashicons-menu"></i></span>' );
			},
			stop: function(event, ui) {
			},
			update: function(event, ui) {
				$(this).find( 'input[type="checkbox"]' ).trigger( 'change' );
			}
		});

	}

	if ( typeof acf.add_action !== 'undefined' ) {

		/*
		*  ready & append (ACF5)
		*
		*  These two events are called when a field element is ready for initizliation.
		*  - ready: on page load similar to $(document).ready()
		*  - append: on new DOM elements appended via repeater field or other AJAX calls
		*/

		acf.add_action( 'ready_field/key=field_5e441d93d6236', initialize_sortable_field );
		acf.add_action( 'append_field/key=field_5e441d93d6236', initialize_sortable_field );

	}

});

wp.domReady( function() {

	wp.blocks.registerBlockStyle( 'core/heading', {
		name: 'subheading',
		label: 'Subheading'
	});

	wp.blocks.registerBlockStyle( 'core/button', [
		{
			name: 'primary',
			label: 'Primary',
			isDefault: true,
		},
		{
			name: 'secondary',
			label: 'Secondary'
		},
		{
			name: 'tertiary',
			label: 'Tertiary'
		}
	]);

} );
