( function() {
	var root           = document.documentElement;
	var body           = document.getElementsByTagName( 'body' )[ 0 ];
	var scrollBarWidth = window.innerWidth - document.documentElement.clientWidth;
	var searchToggles  = document.querySelectorAll( '.search-toggle' );

	// Sets scrollbar width on page load.
	// Make sure it's a reasonable size. See https://github.com/maithemewp/mai-engine/issues/516.
	// FB/IG gets wonky and calculates this to something huge causing the window to be squished.
	if ( scrollBarWidth > 0 && scrollBarWidth <= 20 ) {
		root.style.setProperty( '--scrollbar-width', scrollBarWidth + 'px' ); // Needs px value for calculations.
		root.style.setProperty( '--viewport-width', 'calc(100vw - var(--scrollbar-width, 0px))' ); // Needs px value for calculations.
	}

	var toggleSearchFormEvent = function( event ) {
		var element = event.target.closest( '.search-icon-form' );

		if ( ! element ) {
			return;
		}

		toggleSearchForm( element );
	}

	var toggleSearchForm = function( element ) {
		var parent = element.closest( '.search-icon-form' );

		if ( ! parent ) {
			return;
		}

		var form   = parent.querySelector( '.search-form' );
		var toggle = parent.querySelector( '.search-toggle' );

		maiToggleAriaValues( toggle );

		form.classList.toggle( 'search-form-visible' );

		if ( form.classList.contains( 'search-form-visible' ) ) {
			body.addEventListener( 'keydown', maybeHideSearchForm, false );
			body.addEventListener( 'click', maybeHideSearchForm, false );
			form.querySelector( '.search-form-input' ).focus();
		} else {
			body.removeEventListener( 'keydown', maybeHideSearchForm, false );
			body.removeEventListener( 'click', maybeHideSearchForm, false );
		}
	};

	var maybeHideSearchForm = function( event ) {
		if ( ! event.target.closest( '.search-icon-form' ) || [ 'Escape', 'Esc' ].includes( event.key ) ) {
			var visibleSearchToggles = document.querySelectorAll( '.search-form-visible' );

			visibleSearchToggles.forEach( function( searchToggle ) {
				toggleSearchForm( searchToggle );
			});
		}
	};

	// Handles search toggle and form.
	searchToggles.forEach( function( searchToggle ) {
		searchToggle.addEventListener( 'click', toggleSearchFormEvent, false );
	});
} )();

function maiToggleAriaValues( element ) {
	var ariaValue = element.getAttribute( 'aria-expanded' ) === 'false' ? 'true' : 'false';

	element.setAttribute( 'aria-expanded', ariaValue );
	element.setAttribute( 'aria-pressed', ariaValue );

	return element;
};
