( function() {
	var root           = document.documentElement;
	var scrollBarWidth = window.innerWidth - document.documentElement.clientWidth;
	var searchToggles  = document.querySelectorAll( '.search-toggle' );

	var toggleSearchForm = function( event ) {
		var parent = this.closest( '.search-icon-form' );

		if ( ! parent ) {
			return;
		}

		var target = event.target;
		var form   = parent.querySelector( '.search-form' );
		var toggle = parent.querySelector( '.search-toggle' );
		var input  = parent.querySelector( '.search-form-input' );

		var hideSearchForm = function( event ) {
			if ( ! event.target.closest( '.search-icon-form' ) ) {
				form.classList.remove( 'search-form-visible' );
				maiAriaClose( toggle );
				document.removeEventListener( 'mouseup', hideSearchForm, true );
			}
		};

		input.setAttribute( 'required', '' );

		if ( ! ( target.classList.contains( 'search-form-input' ) || target.classList.contains( 'search-form-submit' ) || target.classList.contains( 'search-form' ) ) ) {
			if ( form.classList.contains( 'search-form-visible' ) ) {
				maiAriaClose( toggle );
				form.classList.remove( 'search-form-visible' );
				document.removeEventListener( 'mouseup', hideSearchForm, true );
			} else {
				maiAriaOpen( toggle );
				form.classList.add( 'search-form-visible' );
				input.focus();
				document.addEventListener( 'mouseup', hideSearchForm, true );
			}
		}
	};

	// Sets scrollbar width on page load.
	root.style.setProperty( '--scrollbar-width', scrollBarWidth + 'px' );

	// Handles search toggle and form.
	searchToggles.forEach( function( searchToggle ) {
		searchToggle.addEventListener( 'click', toggleSearchForm, false );
	} );
} )();

function maiAriaOpen( element ) {
	element.setAttribute( 'aria-expanded', 'true' );
	element.setAttribute( 'aria-pressed', 'true' );

	return element;
};

function maiAriaClose( element ) {
	element.setAttribute( 'aria-expanded', 'false' );
	element.setAttribute( 'aria-pressed', 'false' );

	return element;
};

function maiToggleAriaValues( element ) {
	var ariaValue = element.getAttribute( 'aria-expanded' ) === 'false' ? 'true' : 'false';

	element.setAttribute( 'aria-expanded', ariaValue );
	element.setAttribute( 'aria-pressed', ariaValue );

	return element;
};
