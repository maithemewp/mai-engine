var body           = document.getElementsByTagName( 'body' )[ 0 ];
var siteHeaderWrap = document.getElementsByClassName( 'site-header-wrap' )[ 0 ];
var headerTracker  = document.createElement( 'span' );
var hasSticky      = body.classList.contains( 'has-sticky-header' );

headerTracker.setAttribute( 'id', 'header-tracker' );
siteHeaderWrap.parentNode.insertBefore( headerTracker, siteHeaderWrap );

var isTop = new IntersectionObserver( function( tracker ) {
	if ( tracker.isIntersecting ) {
		siteHeaderWrap.classList.remove( 'is-stuck' );
	} else {
		siteHeaderWrap.classList.add( 'is-stuck' );
	}
}, { threshold: [ 0, 1 ] } );

isTop.observe( headerTracker );
