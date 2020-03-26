var localizedData  = typeof responsiveMenu === 'undefined' ? {} : responsiveMenu;
var body           = document.getElementsByTagName( 'body' )[ 0 ];
var siteHeaderWrap = document.querySelector( '.site-header > .wrap' );
var navHeaderLeft  = document.getElementsByClassName( 'nav-header-left' )[ 0 ];
var navHeaderRight = document.getElementsByClassName( 'nav-header-right' )[ 0 ];
var navAfterHeader = document.getElementsByClassName( 'nav-after-header' )[ 0 ];
var navMenus       = [ navHeaderLeft, navHeaderRight, navAfterHeader ];
var mobileMenu     = document.querySelector( '.mobile-menu' );
var mobileMenuWrap = document.querySelector( '.mobile-menu .wrap' );
var mobileMenuList = document.querySelector( '.mobile-menu .menu' );
var menuToggle     = document.getElementsByClassName( 'menu-toggle' )[ 0 ];
var mediaQuery     = window.matchMedia( '(max-width:' + localizedData.breakpoint + 'px)' );
var timeout        = false;

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
	menuToggle.setAttribute( 'class', 'menu-toggle' );
	menuToggle.innerHTML = localizedData.menuToggle;
	siteHeaderWrap.appendChild( menuToggle );
};

var cloneMenuItems = function() {
	if ( mobileMenu ) {
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

var hideMenus = function() {
	navMenus.forEach( function( navMenu ) {
		if ( navMenu ) {
			navMenu.style.display = mediaQuery.matches ? 'none' : 'flex';
		}
	} );
};

var toggleMobileMenu = function() {
	var ariaValue = menuToggle.getAttribute( 'aria-expanded' ) === 'false' ? 'true' : 'false';

	menuToggle.setAttribute( 'aria-expanded', ariaValue );
	menuToggle.setAttribute( 'aria-pressed', ariaValue );

	body.classList.toggle( 'mobile-menu-visible' );
};

var doResponsiveMenus = function() {
	if ( timeout ) {
		return;
	}

	timeout = true;

	// Do stuff.
	hideMenus();

	setTimeout( function() {
		timeout = false;
	}, 100 );
};

createMobileMenu();
createMenuToggle();
cloneMenuItems();
doResponsiveMenus();

window.addEventListener( 'resize', doResponsiveMenus, false );
menuToggle.addEventListener( 'click', toggleMobileMenu, false );
