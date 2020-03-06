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

