jQuery(document).ready(function($) {

	// Get the sortable elements.
	var $sortables = $( '.kirki-sortable-item[data-value^="genesis_"]' );

	// Bail if no sortables.
	if ( ! $sortables.length ) {
		return;
	}

	// Get the wrapper element.
	var $wrapper = $sortables.parents( '.customize-control-kirki-sortable' );

	// Bail if no wrapper.
	if ( ! $wrapper.length ) {
		return;
	}

	// Get the description element.
	var $description = $wrapper.find( '.customize-control-description' );

	// Bail if no description.
	if ( ! $description.length ) {
		return;
	}

	// Add button. Using `<button>` messes with jquery-ui-sortable so I'm just using a link.
	// TODO: localize text.
	$description.after( '<a href="#" role="button" class="mai-toggle-hooks">Toggle Hooks</a>' );

	// Button click event.
	$wrapper.on( 'click', '.mai-toggle-hooks', function(e) {
		e.preventDefault();
		$sortables.toggleClass( 'mai-sortable-expanded' );
	});

});
