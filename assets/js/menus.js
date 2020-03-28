( function() {
	var localizedData    = typeof responsiveMenu === 'undefined' ? {} : responsiveMenu;
	var body             = document.getElementsByTagName( 'body' )[ 0 ];
	var siteHeaderWrap   = document.querySelector( '.site-header > .wrap' );
	var navHeaderLeft    = document.getElementsByClassName( 'nav-header-left' )[ 0 ];
	var navHeaderRight   = document.getElementsByClassName( 'nav-header-right' )[ 0 ];
	var navAfterHeader   = document.getElementsByClassName( 'nav-after-header' )[ 0 ];
	var navMenus         = [ navHeaderLeft, navHeaderRight, navAfterHeader ];
	var mobileMenu       = document.getElementsByClassName( '.mobile-menu' )[ 0 ];
	var mobileMenuWrap   = document.querySelector( '.mobile-menu .wrap' );
	var mobileMenuList   = document.querySelector( '.mobile-menu .menu' );
	var mobileMenuWidget = document.querySelector( '.mobile-menu .widget' );
	var menuToggle       = document.getElementsByClassName( 'menu-toggle' )[ 0 ];
	var subMenus         = document.querySelectorAll( '.sub-menu' );
	var mediaQuery       = window.matchMedia( '(max-width:' + localizedData.breakpoint + 'px)' );
	var timeout          = false;

	var addBodyClasses = function() {
		if ( mediaQuery.matches ) {
			body.classList.add( 'is-mobile' );
			body.classList.remove( 'is-desktop' );
		} else {
			body.classList.remove( 'is-mobile' );
			body.classList.add( 'is-desktop' );
		}
	};

	var createMobileMenu = function() {
		if ( mobileMenu ) {
			return;
		}

		mobileMenu = document.createElement( 'div' );
		mobileMenu.setAttribute( 'class', 'mobile-menu' );
		mobileMenu.setAttribute( 'aria-label', localizedData.ariaLabel );
		mobileMenu.setAttribute( 'itemscope', '' );
		mobileMenu.setAttribute( 'itemtype', 'https://schema.org/SiteNavigationElement' );
		mobileMenuWrap = document.createElement( 'div' );
		mobileMenuWrap.setAttribute( 'class', 'wrap' );
		mobileMenuList = document.createElement( 'ul' );
		mobileMenuList.setAttribute( 'class', 'menu' );
		mobileMenu.appendChild( mobileMenuWrap );
		mobileMenuWrap.appendChild( mobileMenuList );
		siteHeaderWrap.parentNode.insertBefore( mobileMenu, null );
	};

	var createMenuToggle = function() {
		if ( menuToggle ) {
			return;
		}

		menuToggle = document.createElement( 'button' );
		menuToggle.setAttribute( 'class', 'menu-toggle' );
		menuToggle.setAttribute( 'aria-expanded', 'false' );
		menuToggle.setAttribute( 'aria-pressed', 'false' );
		menuToggle.innerHTML = localizedData.menuToggle;
		siteHeaderWrap.appendChild( menuToggle );
	};

	var createSubMenuToggles = function() {
		subMenus.forEach( function( subMenu ) {
			var subMenuToggle = document.createElement( 'button' );
			subMenuToggle.setAttribute( 'class', 'sub-menu-toggle' );
			subMenuToggle.setAttribute( 'aria-expanded', 'false' );
			subMenuToggle.setAttribute( 'aria-pressed', 'false' );
			subMenuToggle.innerHTML = localizedData.subMenuToggle;
			subMenu.parentNode.insertBefore( subMenuToggle, subMenu );
		} );
	};

	var cloneMenuItems = function() {
		if ( mobileMenuWidget ) {
			return;
		}

		navMenus.forEach( function( navMenu ) {
			if ( ! navMenu ) {
				return;
			}

			var navMenuClone = navMenu.querySelector( '.menu' ).cloneNode( true );
			navMenuClone.setAttribute( 'id', navMenu.getAttribute( 'id' ) + '-clone' );

			Array.from( navMenuClone.children ).forEach( function( menuItem ) {
				var lastClass = menuItem.getAttribute( 'class' ).split( ' ' ).pop();

				menuItem.setAttribute( 'id', lastClass );

				if ( null === mobileMenuList.querySelector( '#' + lastClass ) ) {
					mobileMenuList.appendChild( menuItem );
				}
			} );
		} );
	};

	var toggleAriaValues = function( element ) {
		var ariaValue = element.getAttribute( 'aria-expanded' ) === 'false' ? 'true' : 'false';

		element.setAttribute( 'aria-expanded', ariaValue );
		element.setAttribute( 'aria-pressed', ariaValue );

		return element;
	};

	var toggleMobileMenu = function() {
		toggleAriaValues( menuToggle );

		body.classList.toggle( 'mobile-menu-visible' );
	};

	var toggleSubMenu = function( event ) {
		var subMenuToggle = event.target.classList.contains( 'sub-menu-toggle' ) ? event.target : event.target.parentNode;
		subMenuToggle     = subMenuToggle.classList.contains( 'menu-item' ) ? subMenuToggle.getElementsByClassName( 'sub-menu-toggle' )[ 0 ] : subMenuToggle;
		var subMenu       = subMenuToggle.nextSibling;

		toggleAriaValues( subMenuToggle );
		subMenu.parentNode.classList.toggle( 'sub-menu-visible' );
	};

	var onResize = function() {
		if ( timeout ) {
			return;
		}

		timeout = true;

		// Do stuff.
		addBodyClasses();

		setTimeout( function() {
			timeout = false;
		}, 100 );
	};

	var onReady = function() {
		createMobileMenu();
		createMenuToggle();
		cloneMenuItems();
		createSubMenuToggles();
		onResize();

		window.addEventListener( 'resize', onResize, false );
		menuToggle.addEventListener( 'click', toggleMobileMenu, false );
		document.querySelectorAll( '.sub-menu-toggle' ).forEach( function( subMenu ) {
			subMenu.addEventListener( 'click', toggleSubMenu, false );
		} );
	};

	return onReady();
} )();
