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
	var hasPageHeader  = body.classList.contains( 'has-page-header' );
	var hasAlignFull   = body.classList.contains( 'has-alignfull-first' );
	var breakpointSm   = window.getComputedStyle( document.documentElement ).getPropertyValue( '--breakpoint-sm' );
	var firstElement   = hasAlignFull ? document.querySelectorAll( '.entry-content > .alignfull' )[0] : siteInner.firstChild;
	var timeout        = false;

	/**
	 * Sticky header.
	 */
	var isTop = new IntersectionObserver( function( tracker ) {
		if ( tracker[ 0 ].isIntersecting ) {
			body.classList.remove( 'header-stuck' );

		} else {
			var viewportWidth = window.innerWidth || document.documentElement.clientWidth;

			if ( viewportWidth > parseInt( breakpointSm, 10 ) ) {
				body.classList.add( 'header-stuck' );
			}
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

		// Clear inline styles before recalculating.
		firstElement.style.removeProperty( 'padding-top' );

		var paddingTop   = firstElementStyles.getPropertyValue( 'padding-top' );
		var headerHeight = siteHeader.offsetHeight;

		headerHeight += navAfterHeader ? navAfterHeader.offsetHeight : 0;

		if ( hasSticky || hasAlignFull || hasPageHeader ) {
			siteInner.style.marginTop = '-' + headerHeight + 'px';
		}

		firstElement.setAttribute( 'style', 'padding-top: ' + ( parseInt( headerHeight ) + parseInt( paddingTop ) ) + 'px !important' );

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
