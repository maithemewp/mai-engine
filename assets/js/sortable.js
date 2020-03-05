jQuery(document).ready(function($) {

	// console.log( $( '.acf-checkbox-list.acf-bl' ) )

	// // $( '.acf-field-checkbox.acf-field-5e42fcfc13a26 .acf-checkbox-list' ).sortable();
	// $( '.acf-checkbox-list.acf-bl' ).sortable();

	if ( 'object' !== typeof acf ) {
		return
	}

	/**
	*  initialize_sortable_field
	*
	*  This function will initialize the $field.
	*
	*  @date	30/11/17
	*  @since	5.6.5
	*
	*  @param	n/a
	*  @return	n/a
	*/
	function initialize_sortable_field( $field ) {

		if ( ! $field.hasClass( 'mai-sortable' ) ) {
			return;
		}

		// Get sortable checkboxes.
		// $field_wrap = $( '.mai-sortable' );

		// // Bail if no sortable checkboxes.
		// if ( ! $field_wrap.length ) {
		// 	return;
		// }

		// add sortable
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
				// self.render();
			},
			update: function(event, ui) {
				// console.log( $(this).prev( 'input[type="hidden"]' ) );
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
		*
		*  @param	n/a
		*  @return	n/a
		*/

		acf.add_action( 'ready_field/key=field_5e441d93d6236', initialize_sortable_field );
		acf.add_action( 'append_field/key=field_5e441d93d6236', initialize_sortable_field );

	}

	return;

	/**
	 * TODO:
	 * On change of Template, change some Show, image size, etc. value defaults.
	 */


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

	acf.addFilter( 'select2_ajax_data', function( data, args, $input, field, instance ) {

		// Bail if not our fields.
		if ( -1 === $.inArray( data.field_key, [
			'field_5de9b96fb69b0', // Template.
			'field_5bd3ea2224a92',
			'field_5bd3ef320bc3a',
			'field_5bd51d7e47d05',
			'field_5c85592ee744b',
		] ) ) {
			return data;
		}

		var $block          = $input.parents( '.wp-block[data-type="acf/mai-grid-entries"]' );
		var $postType       = $block.find( '.acf-field[data-key="field_5bd3ea2224a92"] select' );
		var $taxonomy       = $block.find( '.acf-field[data-key="field_5bd51d7e47d05"] select' );
		var currentPostType = $postType.val();
		var currentTaxonomy = $taxonomy.val();

		if ( 'field_5de9b96fb69b0' === data.field_key ) {
			console.log( 'Template!' );
		}

		// If Post Type/Content field.
		if ( 'field_5bd3ea2224a92' === data.field_key ) {
			// currentPostType = currentPostType;
		}

		// If Posts/Entries field.
		if ( 'field_5bd3ef320bc3a' === data.field_key ) {
			data.post_type = currentPostType;
		}

		// If Taxonomy field.
		if ( 'field_5bd51d7e47d05' === data.field_key ) {
			data.post_type = currentPostType;
		}

		// If Terms field.
		if ( 'field_5bd524aa95dbe' === data.field_key ) {
			data.taxonomy = currentTaxonomy;
		}

		// If Parent field
		if ( 'field_5c85592ee744b' === data.field_key ) {
			data.post_type = currentPostType;
		}

		return data;
	});

});

