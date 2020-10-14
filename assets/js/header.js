( function() {

	/**
	 * Global variables.
	 */
	var root           = document.documentElement;
	var body           = document.getElementsByTagName( 'body' )[ 0 ];
	var skipLink       = document.getElementsByClassName( 'genesis-skip-link' )[ 0 ];
	var beforeHeader   = document.getElementsByClassName( 'before-header' )[ 0 ];
	var header         = document.getElementsByClassName( 'site-header' )[ 0 ];
	var afterHeader    = document.getElementsByClassName( 'after-header' )[ 0 ];
	var navAfterHeader = document.getElementsByClassName( 'nav-after-header' )[ 0 ];
	var pageHeader     = document.getElementsByClassName( 'page-header' )[ 0 ];
	var siteInner      = document.getElementsByClassName( 'site-inner' )[ 0 ];
	var breakpointSm   = window.getComputedStyle( document.documentElement ).getPropertyValue( '--breakpoint-sm' );
	var hasSticky      = header && body.classList.contains( 'has-sticky-header' );
	var hasTransparent = header && body.classList.contains( 'has-transparent-header-enabled' );
	var hasPageHeader  = pageHeader && body.classList.contains( 'has-page-header' );
	var hasAlignFull   = 0 !== document.querySelectorAll( '.entry-wrap-single > .entry-content:first-child > .alignfull:first-child' ).length;
	var hasBreadcrumbs = 0 !== document.getElementsByClassName( 'breadcrumb' ).length;
	var firstElement   = hasPageHeader ? pageHeader : hasAlignFull ? document.querySelectorAll( '.entry-wrap-single > .entry-content:first-child > .alignfull:first-child' )[0] : siteInner.firstChild;
	var headerTimeout  = false;
	var shrunkTimeout  = false;
	var innerTimeout   = false;
	var trackerWidth   = 0;

	/**
	 * Sticky and transparent header.
	 */
	var isTop = new IntersectionObserver( function( tracker ) {

		var headerStyles = getComputedStyle( header );
		var duration     = parseFloat( headerStyles.getPropertyValue( 'transition-duration' ) ) * 1000 + 50; // Needed to add time to make sure transition was fully done.

		if ( tracker[ 0 ].isIntersecting ) {
			body.classList.remove( 'header-stuck' );

			if ( trackerWidth !== tracker[ 0 ].rootBounds.width ) {
				setTimeout( function() {
					setHeaderHeight();
					if ( hasTransparent ) {
						siteInnerMargin();
					}
				}, duration );
			}

		} else {
			var viewportWidth = window.innerWidth || document.documentElement.clientWidth;

			if ( viewportWidth > parseInt( breakpointSm, 10 ) ) {
				body.classList.add( 'header-stuck' );

				if ( trackerWidth !== tracker[ 0 ].rootBounds.width ) {
					setTimeout( function() {
						setHeaderShrunkHeight();
					}, duration );
				}
			}
		}

		trackerWidth = tracker[ 0 ].rootBounds.width;

	}, { threshold: [ 0, 1 ] } );

	var	setHeaderHeight = function() {
		if ( headerTimeout ) {
			window.cancelAnimationFrame( headerTimeout );
		}

		headerTimeout = window.requestAnimationFrame( function() {
			root.style.setProperty( '--header-height', ( header ? Math.ceil( header.offsetHeight ) : 0 ) + 'px' );
		});
	};

	var	setHeaderShrunkHeight = function() {
		if ( shrunkTimeout ) {
			window.cancelAnimationFrame( shrunkTimeout );
		}

		shrunkTimeout = window.requestAnimationFrame( function() {
			root.style.setProperty( '--header-shrunk-height', ( header ? Math.ceil( header.offsetHeight ) : 0 ) + 'px' );
		});
	};

	var siteInnerMargin = function() {
		if ( innerTimeout ) {
			window.cancelAnimationFrame( innerTimeout );
		}

		innerTimeout = window.requestAnimationFrame( function() {
			var firstElementStyles = getComputedStyle( firstElement );

			// Clear inline styles before recalculating.
			firstElement.style.removeProperty( 'padding-top' );

			var headerHeight         = parseInt( header ? header.offsetHeight : 0 );
			var afterHeaderHeight    = parseInt( afterHeader ? afterHeader.offsetHeight : 0 );
			var navAfterHeaderHeight = parseInt( navAfterHeader ? navAfterHeader.offsetHeight : 0 );
			var paddingTop           = parseInt( firstElementStyles.getPropertyValue( 'padding-top' ) );

			root.style.setProperty( '--after-header-height', Math.ceil( afterHeaderHeight + navAfterHeaderHeight ) + 'px' );

			firstElement.style.setProperty( 'padding-top', Math.ceil( headerHeight + afterHeaderHeight + navAfterHeaderHeight + paddingTop ) + 'px', 'important' );
		});
	};

	var setHeaderHeightResize = function() {
		setHeaderHeight();
		setTimeout( function() {
			setHeaderHeight();
		}, 250 );
	};

	var setHeaderShrunkHeightResize = function() {
		setHeaderShrunkHeight();
		setTimeout( function() {
			setHeaderShrunkHeight();
		}, 250 );
	};

	var siteInnerMarginResize = function() {
		siteInnerMargin();
		setTimeout( function() {
			siteInnerMargin();
		}, 250 );
	};

	var onReady = function() {
		setHeaderHeight();

		window.addEventListener( 'resize', setHeaderHeightResize, false );
		window.addEventListener( 'resize', setHeaderShrunkHeightResize, false );

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
				dark = firstElement.classList.contains( 'wp-block-cover' ) && ! firstElement.classList.contains( 'has-light-background' ) || ( firstElement.classList.contains( 'wp-block-group' ) && firstElement.classList.contains( 'has-dark-background' ) );
			}

			if ( dark ) {
				body.classList.add( 'has-dark-header' );
			}

			siteInnerMargin();

			window.addEventListener( 'resize', siteInnerMarginResize, false );
		}
	};

	return onReady();
} )();
