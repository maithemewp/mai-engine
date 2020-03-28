( function() {

	/**
	 * Global variables.
	 */
	var body           = document.getElementsByTagName( 'body' )[ 0 ];
	var skipLink       = document.getElementsByClassName( 'genesis-skip-link' )[ 0 ];
	var beforeHeader   = document.getElementsByClassName( 'before-header' )[ 0 ];
	var siteHeader     = document.getElementsByClassName( 'site-header' )[ 0 ];
	var navAfterHeader = document.getElementsByClassName( 'nav-after-header' )[ 0 ];
	var siteInner      = document.getElementsByClassName( 'site-inner' )[ 0 ];
	var hasSticky      = body.classList.contains( 'has-sticky-header' );
	var hasTransparent = body.classList.contains( 'has-transparent-header' );
	var firstElement   = siteInner.firstChild;
	var timeout        = false;

	/**
	 * Sticky header.
	 */
	var isTop = new IntersectionObserver( function( tracker ) {
		if ( tracker[ 0 ].isIntersecting ) {
			siteHeader.classList.remove( 'is-stuck' );
		} else {
			siteHeader.classList.add( 'is-stuck' );
		}
	}, { threshold: [ 0, 1 ] } );

	/**
	 * Transparent header.
	 */
	var siteInnerMargin = function() {
		if ( timeout ) {
			return;
		}

		timeout = true;

		var firstElementStyles = getComputedStyle( firstElement );
		var paddingBottom      = firstElementStyles.getPropertyValue( 'padding-bottom' );
		var headerHeight       = siteHeader.offsetHeight;

		headerHeight += beforeHeader ? beforeHeader.offsetHeight : 0;
		headerHeight += navAfterHeader ? navAfterHeader.offsetHeight : 0;

		if ( hasSticky ) {
			siteInner.style.marginTop = '-' + headerHeight + 'px';
		}

		firstElement.style.paddingTop = parseInt( headerHeight ) + parseInt( paddingBottom ) + 'px';

		setTimeout( function() {
			timeout = false;
		}, 100 );
	};

	var onReady = function() {
		if ( hasSticky ) {
			isTop.observe( beforeHeader ? beforeHeader : skipLink );
		}

		if ( hasTransparent ) {
			window.addEventListener( 'resize', siteInnerMargin, false );
			siteInnerMargin();
		}
	};

	return onReady();
} )();
