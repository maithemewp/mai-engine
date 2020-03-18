jQuery(document).ready(function($) {

	if ( 'object' !== typeof acf ) {
		return
	}
	// var fields = maiGridQueryVars.fields;
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

