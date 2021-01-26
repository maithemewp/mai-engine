( function() {

	/**
	 * Global variables.
	 */
	var root           = document.documentElement;
	var body           = document.getElementsByTagName( 'body' )[ 0 ];
	var skipLink       = document.getElementsByClassName( 'genesis-skip-link' )[ 0 ];
	var beforeHeader   = document.getElementsByClassName( 'before-header' )[ 0 ];
	var header         = document.getElementsByTagName( 'header' )[ 0 ];
	var afterHeader    = document.getElementsByClassName( 'after-header' )[ 0 ];
	var navAfterHeader = document.getElementsByClassName( 'nav-after-header' )[ 0 ];
	var pageHeader     = document.getElementsByClassName( 'page-header' )[ 0 ];
	var breakpointSm   = window.getComputedStyle( document.documentElement ).getPropertyValue( '--breakpoint-sm' );
	var hasSticky      = header && body.classList.contains( 'has-sticky-header' );
	var hasTransparent = header && body.classList.contains( 'has-transparent-header' );
	var hasPageHeader  = pageHeader && body.classList.contains( 'has-page-header' );
	var headerStyles   = header ? getComputedStyle( header ) : false;
	var duration       = header ? parseFloat( headerStyles.getPropertyValue( 'transition-duration' ) ) * 1000 : false;
	var alignFullFirst = false;
	var alignFullEl    = false;
	var firstElement   = false;

	if ( hasPageHeader ) {
		alignFullEl = pageHeader;
	} else {
		if ( body.classList.contains( 'is-single' ) ) {
			firstElement = document.querySelectorAll( '#genesis-content > .entry-single:first-child > .entry-wrap-single:first-child > .entry-content:first-child > :not(:empty):first-of-type' );
		} else if ( body.classList.contains( 'is-archive' ) ) {
			// Not tested much since we don't have blocks on archives yet.
			firstElement = document.querySelectorAll( '#genesis-content > :not(:empty):first-of-type' );
		}
		firstElement   = firstElement && firstElement.length && firstElement[0].classList.contains( 'alignfull' ) ? firstElement[0] : false;
		alignFullFirst = firstElement;
		alignFullEl    =  alignFullFirst ? firstElement : alignFullEl;
	}

	/**
	 * Sticky and transparent header.
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
	}, {
		// rootMargin: root.style.marginTop,
		threshold:[ 0, 1 ],
	});

	var beforeHeaderResize = new ResizeObserver(items => {
		items.forEach(item => {
			root.style.setProperty( '--before-header-height', Math.ceil( item.contentRect.height ) + 'px' );
		});
	});

	var headerWidth     = 0;
	var headerFullSet   = 0;
	var headerShrunkSet = 0;
	var headerResize    = new ResizeObserver(items => {
		items.forEach(item => {
			if ( item.contentRect.width !== headerWidth || ! headerFullSet || ! headerShrunkSet ) {
				if ( ! body.classList.contains( 'header-stuck' ) ) {
					root.style.setProperty( '--header-height-full', Math.ceil( item.contentRect.height ) + 'px' );
					setTimeout( function() {
						root.style.setProperty( '--header-height-full', Math.ceil( item.contentRect.height ) + 'px' );
						headerFullSet = 1;
					}, duration );
				} else {
					root.style.setProperty( '--header-height-shrunk', Math.ceil( item.contentRect.height ) + 'px' );
					setTimeout( function() {
						root.style.setProperty( '--header-height-shrunk', Math.ceil( item.contentRect.height ) + 'px' );
						headerShrunkSet = 1;
					}, duration );
				}
				headerWidth = item.contentRect.width;
			}
			root.style.setProperty( '--header-height', Math.ceil( item.contentRect.height ) + 'px' );
		});
	});

	var afterHeaderResize = new ResizeObserver(items => {
		items.forEach(item => {
			root.style.setProperty( '--after-header-height', Math.ceil( item.contentRect.height ) + 'px' );
		});
	});

	var navAfterHeaderResize = new ResizeObserver(items => {
		items.forEach(item => {
			root.style.setProperty( '--nav-after-header-height', Math.ceil( item.contentRect.height ) + 'px' );
		});
	});

	var onReady = function() {
		// Must be before IntersectionObserver so headerFullResize fires before header-stuck class is added.
		if ( beforeHeader ) {
			beforeHeaderResize.observe( beforeHeader );
		}

		if ( header ) {
			headerResize.observe( header );
		}

		if ( afterHeader ) {
			afterHeaderResize.observe( afterHeader );
		}

		if ( navAfterHeader ) {
			navAfterHeaderResize.observe( navAfterHeader );
		}

		if ( hasSticky ) {
			isTop.observe( beforeHeader ? beforeHeader : skipLink );
		}

		if ( alignFullFirst ) {
			/**
			 * This is only for styling content containers when the first block is full aligned.
			 * This is likely added via PHP, the JS is a fallback.
			 */
			body.classList.add( 'has-alignfull-first' );
		}

		if ( alignFullEl ) {
			// This is added to page-header in PHP.
			alignFullEl.classList.add( 'is-alignfull-first' );
		}

		if ( hasTransparent ) {
			var dark = false;

			if ( pageHeader && body.classList.contains( 'has-page-header' ) ) {
				dark = body.classList.contains( 'has-dark-page-header' );
			} else if ( alignFullEl ) {
				dark = alignFullEl.classList.contains( 'wp-block-cover' ) && ! alignFullEl.classList.contains( 'has-light-background' ) || ( alignFullEl.classList.contains( 'wp-block-group' ) && alignFullEl.classList.contains( 'has-dark-background' ) );
			}

			if ( dark ) {
				body.classList.add( 'has-dark-header' );
			}
		}
	};

	return onReady();

} )();
