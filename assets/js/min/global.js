

/**
 * TODO: debounce scroll with Intersection Observer
 * @link https://caniuse.com/#feat=intersectionobserver
 */

var scrollPosition = window.scrollY,
    siteHeader = document.getElementsByClassName('site-header')[0],
    siteHeaderHeight = siteHeader.offsetHeight;

window.addEventListener('scroll', function () {

    scrollPosition = window.scrollY;

    if (scrollPosition >= siteHeaderHeight) {
        siteHeader.classList.add('sticky');
    } else {
        siteHeader.classList.remove('sticky');
    }

});

if (document.body.classList.contains('has-sticky-header')) {
	console.log(siteHeader);
	console.log(siteHeaderHeight + 'px');
    // siteHeader.style.marginTop(siteHeaderHeight + 'px');
}



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
