jQuery(document).ready(function($) {

	if ( 'object' !== typeof acf ) {
		return
	}

	// var instance = new acf.Model({
	// 	events: {
	// 		'change': 'onChange',
	// 		'change #acf-block_5deacc423710e-field_5de9b96fb69b0': 'onChangeTemplate',
	// 	},
	// 	onChange: function(e, $el){
	// 		// e.preventDefault();
	// 		// var val = $el.val();
	// 		// do something
	// 	},
	// 	onChangeTemplate: function(e, $el){
	// 		var val = $el.val();
	// 		console.log( maiGridVars[val] );

	// 		var $gutter = $( '#acf-block_5deacc423710e[field_5c8542d6a67c5]:checked' );
	// 		console.log( $gutter.val() );
	// 	}
	// });

	// var fields = maiGridWPQueryVars.fields;
	var keys = maiGridWPQueryVars.keys;

	acf.addFilter( 'select2_ajax_data', function( data, args, $input, field, instance ) {

		// Bail if not our fields.
		if ( -1 === $.inArray( data.field_key, Object.values( keys ) ) ) {
			return data;
		}

		// Bail if the post_type field.
		if ( keys.post_type === data.field_key ) {
			return data;
		}

		// If Posts/Entries field.
		if ( keys.post__in === data.field_key ) {
			data.post_type = getPostType( $input );
		}

		// Exclude Entries.
		if ( keys.post__not_in === data.field_key ) {
			data.post_type = getPostType( $input );
		}

		// If Taxonomy field.
		if ( keys.taxonomy === data.field_key ) {
			data.post_type = getPostType( $input );
		}

		// If Terms field.
		if ( keys.terms === data.field_key ) {
			data.taxonomy = getTaxonomy( $input );
		}

		// If Parent field
		if ( keys.parent === data.field_key ) {
			data.post_type = getPostType( $input );
		}

		return data;

	});

	function getPostType( $input ) {
		var $wrapper  = $input.parents( '.acf-block-fields' );
		var $postType = $wrapper.find( '.acf-field[data-key="' + keys.post_type + '"] select' );
		return $postType.val();
	}

	function getTaxonomy( $input ) {
		var $wrapper  = $input.parents( '.acf-row' );
		var $taxonomy = $wrapper.find( '.acf-field[data-key="' + keys.taxonomy + '"] select' );
		return $taxonomy.val();
	}

});

