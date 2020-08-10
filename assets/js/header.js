( function() {

	/**
	 * Global variables.
	 */
	var root           = document.documentElement;
	var body           = document.getElementsByTagName( 'body' )[ 0 ];
	var skipLink       = document.getElementsByClassName( 'genesis-skip-link' )[ 0 ];
	var beforeHeader   = document.getElementsByClassName( 'before-header' )[ 0 ];
	var header         = document.getElementsByClassName( 'site-header' )[ 0 ];
	var headerHeight   = header ? header.offsetHeight: 0;
	var pageHeader     = document.getElementsByClassName( 'page-header' )[ 0 ];
	var navAfterHeader = document.getElementsByClassName( 'nav-after-header' )[ 0 ];
	var siteInner      = document.getElementsByClassName( 'site-inner' )[ 0 ];
	var breakpointSm   = window.getComputedStyle( document.documentElement ).getPropertyValue( '--breakpoint-sm' );
	var hasSticky      = header && body.classList.contains( 'has-sticky-header' );
	var hasTransparent = header && body.classList.contains( 'has-transparent-header-enabled' );
	var hasPageHeader  = pageHeader && body.classList.contains( 'has-page-header' );
	var hasAlignFull   = 0 !== document.querySelectorAll( '.entry-wrap-single > .entry-content:first-child > .alignfull:first-child' ).length;
	var hasBreadcrumbs = 0 !== document.getElementsByClassName( 'breadcrumb' ).length;
	var firstElement   = hasPageHeader ? pageHeader : hasAlignFull ? document.querySelectorAll( '.entry-wrap-single > .entry-content:first-child > .alignfull:first-child' )[0] : siteInner.firstChild;
	var timeout        = false;

	/**
	 * Sticky header.
	 */
	var isTop = new IntersectionObserver( function( tracker ) {
		if ( tracker[ 0 ].isIntersecting ) {
			body.classList.remove( 'header-stuck' );

			setTimeout( function() {
				root.style.setProperty( '--header-height', ( header ? header.offsetHeight : 0 ) + 'px' );
			}, 300 ); // 100ms longer than transition duration. TODO: Use config value localized?

		} else {
			var viewportWidth = window.innerWidth || document.documentElement.clientWidth;

			if ( viewportWidth > parseInt( breakpointSm, 10 ) ) {
				body.classList.add( 'header-stuck' );

				setTimeout( function() {
					root.style.setProperty( '--header-shrunk-height', ( header ? header.offsetHeight : 0 ) + 'px' );
				}, 300 ); // 100ms longer than transition duration. TODO: Use config value localized?
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
		var headerHeight = header ? header.offsetHeight : 0;

		headerHeight += navAfterHeader ? navAfterHeader.offsetHeight : 0;

		firstElement.style.setProperty( 'padding-top', parseInt( headerHeight ) + parseInt( paddingTop ) + 'px', 'important' );

		setTimeout( function() {
			timeout = false;
		}, 100 );
	};

	var	setHeaderHeight = function() {
		root.style.setProperty( '--header-height', ( header ? header.offsetHeight : 0 ) + 'px' );
	};

	var onReady = function() {
		if ( hasSticky ) {
			isTop.observe( beforeHeader ? beforeHeader : skipLink );
		}

		if ( hasTransparent ) {
			if ( ! ( hasPageHeader || hasAlignFull ) || ! hasPageHeader && ( hasAlignFull && hasBreadcrumbs ) ) {
				return;
			}

			body.classList.add( 'has-transparent-header' );

			var dark = false;
			if ( hasPageHeader ) {
				dark = body.classList.contains( 'has-dark-page-header' );
			} else if ( hasAlignFull ) {
				dark = firstElement.classList.contains( 'wp-block-cover' ) || ( firstElement.classList.contains( 'wp-block-group' ) && firstElement.classList.contains( 'has-dark-background' ) );
			}

			if ( dark ) {
				body.classList.add( 'has-dark-header' );
			}

			window.addEventListener( 'resize', siteInnerMargin, false );
			siteInnerMargin();
		}

		window.addEventListener( 'resize', setHeaderHeight, false );
		setHeaderHeight();
	};

	return onReady();
} )();
