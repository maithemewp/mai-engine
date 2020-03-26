jQuery(document).ready(function($) {

	if ( 'object' !== typeof acf ) {
		return
	}

	acf.addFilter( 'select2_ajax_data', function( data, args, $input, field, instance ) {

		// If not Mai Icon select field.
		if ( 'field_5e3f4bcd867e8' !== data.field_key ) {
			return data;
		}

		var $wrapper = $input.parents( '.acf-block-fields' );
		var $style   = $wrapper.find( 'input[name="acf-block_5e7cf8dc21367[field_5e3f49758c633]"]:checked' );
		data.style   = $style.val();

		return data;
	});

	var post = maiGridQueryVars.post;
	var term = maiGridQueryVars.term;
	var user = maiGridQueryVars.user;

	acf.addFilter( 'select2_ajax_data', function( data, args, $input, field, instance ) {

		// Mai Post Grid.
		if ( $.inArray( data.field_key, Object.values( post ) ) >= 0 ) {

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
		if ( $.inArray( data.field_key, Object.values( term ) ) >= 0 ) {

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
		var $wrapper  = $input.parents( '.acf-block-fields' );
		var $postType = $wrapper.find( '.acf-field[data-key="' + keys.post_type + '"] select' );
		return $postType.val();
	}

	function getRowTaxonomy( $input, keys ) {
		var $wrapper  = $input.parents( '.acf-row' );
		var $taxonomy = $wrapper.find( '.acf-field[data-key="' + keys.taxonomy + '"] select' );
		return $taxonomy.val();
	}


	function getTaxonomy( $input, keys ) {
		var $wrapper  = $input.parents( '.acf-block-fields' );
		var $taxonomy = $wrapper.find( '.acf-field[data-key="' + keys.taxonomy + '"] select' );
		return $taxonomy.val();
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

