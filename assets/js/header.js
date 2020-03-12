
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
