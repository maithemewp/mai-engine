( function() {
	var root             = document.documentElement;
	var scrollBarWidth   = window.innerWidth - document.documentElement.clientWidth;
	var searchToggles    = document.querySelectorAll( '.search-toggle' );
	var globalResize     = new ResizeObserver(items => {
		items.forEach(item => {
			scrollBarWidth = window.innerWidth - document.documentElement.clientWidth;
			root.style.setProperty( '--scrollbar-width', scrollBarWidth + 'px' );
		});
	});
	var toggleSearchForm = function( event ) {
		var parent = this.closest( '.search-icon-form' );

		if ( ! parent ) {
			return;
		}

		var target = event.target;
		var form   = parent.querySelector( '.search-form' );
		var toggle = parent.querySelector( '.search-toggle' );
		var input  = parent.querySelector( '.search-form-input' );

		input.setAttribute( 'required', '' );

		if ( ! ( target.classList.contains( 'search-form-input' ) || target.classList.contains( 'search-form-submit' ) || target.classList.contains( 'search-form' ) ) ) {
			form.classList.toggle( 'search-form-visible' );
			maiToggleAriaValues( toggle );
		}

		if ( form.classList.contains( 'search-form-visible' ) ) {
			input.focus();

			document.addEventListener( 'mouseup', function( event ) {
				if ( ! event.target.closest( '.search-icon-form' ) ) {
					form.classList.remove( 'search-form-visible' );
					maiToggleAriaValues( toggle );
				}
			} );
		}
	};

	globalResize.observe( root );

	searchToggles.forEach( function( searchToggle ) {
		searchToggle.addEventListener( 'click', toggleSearchForm, false );
	} );

} )();

function maiToggleAriaValues( element ) {
	var ariaValue = element.getAttribute( 'aria-expanded' ) === 'false' ? 'true' : 'false';

	element.setAttribute( 'aria-expanded', ariaValue );
	element.setAttribute( 'aria-pressed', ariaValue );

	return element;
};

function hide( element ) {
	var el = document.getElementsByClassName( element )[ 0 ];

	el.style.opacity = 1;

	( function fade() {
		if ( ( el.style.opacity -= 0.1 ) < 0 ) {
			el.style.display = 'none';
		} else {
			requestAnimationFrame( fade );
		}
	} )();
}

function show( element, display ) {
	var el = document.getElementsByClassName( element )[ 0 ];

	el.style.opacity = 0;
	el.style.display = display || 'inline-flex';

	( function fade() {
		var val = parseFloat( el.style.opacity );

		if ( ! ( ( val += 0.1 ) > 1 ) ) {
			el.style.opacity = val;
			requestAnimationFrame( fade );
		}
	} )();
}

function toggle( element ) {
	var el = document.getElementsByClassName( element )[ 0 ];

	if ( el.style.opacity !== '1' ) {
		show( element );
		return;
	}

	if ( el.style.opacity === '1' ) {
		hide( element );
	}
}

// Hide modal on click outside.
document.addEventListener( 'click', function( event ) {
	var modal = document.getElementsByClassName( 'modal' )[ 0 ];

	if ( ! modal || event.target.closest( '.modal' ) ) {
		return;
	}

	if ( '1' === modal.style.opacity ) {
		hide( 'modal' );
	}
} );

