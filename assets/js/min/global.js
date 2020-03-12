

var isTop = new IntersectionObserver( function( tracker ) {
	var tracker = tracker[0];
	var header  = document.querySelector( '.site-header' );
	// var body    = document.getElementsByTagName( 'body' )[0];
	// tracker.intersectionRatio
	if ( tracker.isIntersecting ) {
		header.classList.remove( 'is-stuck' );
		// body.classList.remove( 'header-stuck' );
	} else {
		header.classList.add( 'is-stuck' );
		// body.classList.add( 'header-stuck' );
	}

}, { threshold: [0,1] });

isTop.observe( document.getElementById( 'header-tracker' ) );



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
