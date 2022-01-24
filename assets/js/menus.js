( function() {
	var maiMenuData      = typeof maiMenuVars === 'undefined' ? {} : maiMenuVars;
	var body             = document.getElementsByTagName( 'body' )[ 0 ];
	var siteHeaderWrap   = document.querySelector( '.site-header-wrap' );
	var navHeaderLeft    = document.getElementsByClassName( 'nav-header-left' )[ 0 ];
	var navHeaderRight   = document.getElementsByClassName( 'nav-header-right' )[ 0 ];
	var navAfterHeader   = document.getElementsByClassName( 'nav-after-header' )[ 0 ];
	var navMenus         = [ navHeaderLeft, navHeaderRight, navAfterHeader ];
	var mobileMenu       = document.querySelector( '.mobile-menu' );
	var mobileMenuWrap   = document.querySelector( '.mobile-menu .wrap' );
	var mobileMenuList   = document.querySelector( '.mobile-menu .menu' );
	var mobileMenuWidget = document.querySelector( '.mobile-menu .widget' );
	var menuToggle       = document.getElementsByClassName( 'menu-toggle' )[ 0 ];

	if ( ! siteHeaderWrap ) {
		return;
	}

	var createMobileMenu = function() {
		if ( mobileMenu ) {
			return;
		}

		mobileMenu = document.createElement( 'div' );
		mobileMenu.setAttribute( 'class', 'mobile-menu' );
		mobileMenu.setAttribute( 'aria-label', maiMenuData.ariaLabel );
		mobileMenu.setAttribute( 'itemscope', '' );
		mobileMenu.setAttribute( 'itemtype', 'https://schema.org/SiteNavigationElement' );

		if ( ! mobileMenuWrap ) {
			mobileMenuWrap = document.createElement( 'div' );
			mobileMenuWrap.setAttribute( 'class', 'wrap' );
			mobileMenu.appendChild( mobileMenuWrap );
		}

		if ( ! mobileMenuList ) {
			mobileMenuList = document.createElement( 'ul' );
			mobileMenuList.setAttribute( 'class', 'menu' );
			mobileMenuWrap.appendChild( mobileMenuList );
		}

		siteHeaderWrap.parentNode.insertBefore( mobileMenu, null );
	};

	var cloneMenuItems = function() {
		if ( mobileMenu.classList.contains( 'template-part' ) ) {
			return;
		}

		if ( mobileMenuWidget ) {
			return;
		}

		navMenus.forEach( function( navMenu ) {
			if ( ! navMenu ) {
				return;
			}

			var menu = navMenu.querySelector( '.menu' );
			if ( ! menu ) {
				return;
			}

			var navMenuClone = menu.cloneNode( true );
			navMenuClone.setAttribute( 'id', navMenu.getAttribute( 'id' ) + '-clone' );

			Array.from( navMenuClone.children ).forEach( function( menuItem ) {
				var lastClasses = menuItem.getAttribute( 'class' );

				if ( ! lastClasses ) {
					return;
				}

				var lastClass = lastClasses.split( ' ' ).pop();

				menuItem.setAttribute( 'id', lastClass );

				if ( mobileMenuList && null === mobileMenuList.querySelector( '#' + lastClass ) && ! menuItem.classList.contains( 'search' ) ) {
					menuItem.removeAttribute( 'id' );
					mobileMenuList.appendChild( menuItem );
				}
			} );
		} );
	};

	var addMenuItemClasses = function() {
		var menuItems = document.querySelectorAll( '.mobile-menu .menu-item' );
		menuItems.forEach( function( menuItem ) {
			menuItem.classList.add( 'mobile-menu-item' );
		} );
	};

	var createSubMenuToggles = function() {
		var subMenus = document.querySelectorAll( '.mobile-menu .sub-menu' );
		subMenus.forEach( function( subMenu ) {
			var subMenuToggle = document.createElement( 'button' );
			var toggleText    = maiMenuData.subMenuToggle;
			var parentItem    = subMenu.closest( '.menu-item' );

			if ( parentItem ) {
				var toggleTextName = parentItem.querySelector( 'span[itemprop="name"]' );
				toggleTextName     = toggleTextName ? toggleTextName.innerText.trim() : '';

				if ( toggleTextName ) {
					toggleText = toggleTextName + ' ' + toggleText;
				}
			}

			subMenuToggle.setAttribute( 'class', 'sub-menu-toggle' );
			subMenuToggle.setAttribute( 'aria-expanded', 'false' );
			subMenuToggle.setAttribute( 'aria-pressed', 'false' );
			subMenuToggle.innerHTML = '<span class="sub-menu-toggle-icon"></span><span class="screen-reader-text">' + toggleText + '</span>';
			subMenu.parentNode.insertBefore( subMenuToggle, subMenu );
		} );
	};

	var toggleMobileMenu = function( event ) {
		if ( ! menuToggle ) {
			return;
		}

		maiToggleAriaValues( menuToggle );

		body.classList.toggle( 'mobile-menu-visible' );

		if ( body.classList.contains( 'mobile-menu-visible' ) ) {
			body.addEventListener( 'keydown', maybeCloseMobileMenu, false );
			body.addEventListener( 'click', maybeCloseMobileMenu, false );
		} else {
			body.removeEventListener( 'keydown', maybeCloseMobileMenu, false );
			body.removeEventListener( 'click', maybeCloseMobileMenu, false );
		}
	};

	var maybeCloseMobileMenu = function( event ) {
		if ( ! event.target.closest( '.menu-toggle, .mobile-menu' ) || [ 'Escape', 'Esc' ].includes( event.key ) ) {
			toggleMobileMenu( event );
		}
	};

	var toggleSubMenu = function( event ) {
		var subMenuToggle = event.target.classList.contains( 'sub-menu-toggle' ) ? event.target : event.target.parentNode;
		subMenuToggle     = subMenuToggle.classList.contains( 'menu-item' ) ? subMenuToggle.getElementsByClassName( 'sub-menu-toggle' )[ 0 ] : subMenuToggle;
		var subMenu       = subMenuToggle.nextSibling;

		maiToggleAriaValues( subMenuToggle );

		subMenuToggle.classList.toggle( 'active' );
		subMenu.classList.toggle( 'visible' );
	};

	var onReady = function() {
		createMobileMenu();
		cloneMenuItems();
		addMenuItemClasses();
		createSubMenuToggles();

		if ( menuToggle ) {
			menuToggle.addEventListener( 'click', toggleMobileMenu, false );
		}

		document.addEventListener( 'click', function( event ) {
			if ( event.target.classList.contains( 'sub-menu-toggle' ) || event.target.classList.contains( 'sub-menu-toggle-icon' ) ) {
				toggleSubMenu( event );
			}
		}, false );
	};

	return onReady();
} )();
