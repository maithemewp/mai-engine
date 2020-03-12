/**
 * @todo Check if
 */
var isTop = new IntersectionObserver( function( tracker ) {
	var tracker   = tracker[0];
	var body      = document.getElementsByTagName( 'body' )[0];
	var header    = document.querySelector( '.site-header' );
	var hasSticky = body.classList.contains( 'has-sticky-header' );
	// tracker.intersectionRatio
	if ( tracker.isIntersecting ) {
		if ( hasSticky ) {
			header.classList.remove( 'is-stuck' );
		}
		header.classList.remove( 'is-top' );
	} else {
		if ( hasSticky ) {
			header.classList.add( 'is-stuck' );
		}
		header.classList.add( 'is-top' );
	}

}, { threshold: [0,1] });

isTop.observe( document.getElementById( 'header-tracker' ) );
