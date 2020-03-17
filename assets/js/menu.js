var menuButtons = document.querySelectorAll( '.menu-item.button' );

[].forEach.call( menuButtons, function( menuButton ) {
	menuButton.childNodes.forEach( function( childElement ) {
		if ( 'menu-item-link' === childElement.className ) {
			childElement.classList.add( 'button' );
		}
	} );

	menuButton.classList.remove( 'button' );
} );
