( function ( document, $, undefined ) {

	$( '.mai-addon-actions' ).on( 'click', '.button', function(e){
		e.preventDefault();

		var $button = $( e.target );
		var $card   = $button.parents( '.mai-addon' );

		$card.addClass( 'mai-addon-loading' );
		$card.append( '<span class="mai-addon-loader"><span class="mai-addon-loader-inner"><span class="mai-addon-loader-circle"></span>' + maiAddonsVars.loadingText + '</span></span>' );

		$.ajax({
			method: 'GET',
			url: maiAddonsVars.ajaxUrl,
			data: {
				'action': 'mai_addons_action',
				'nonce': maiAddonsVars.ajaxNonce,
				'slug': $(this).attr( 'data-slug' ),
				'trigger': $(this).attr( 'data-action' ),
			},
			success: function( response ) {
				$button.parent( '.mai-addon-actions' ).html( response.data.html );

				if ( response.data.active ) {
					$card.addClass( 'mai-addon-is-active' );
				} else {
					$card.removeClass( 'mai-addon-is-active' );
				}
			},
			fail: function( response ) {
				console.log( 'Mai Add-ons', response );
			}
		}).done( function( response ) {
			var $loader = $card.find( '.mai-addon-loader' );

			if ( response.success ) {
				$loader.find( '.mai-addon-loader-inner' ).append( '<span class="mai-addon-loader-circle-done"></span><span class="mai-addon-loader-checkmark"></span>' );
				$loader.addClass( 'mai-addon-loader-complete' );

				setTimeout( function() {
					$loader.fadeOut( 400, function() {
						$card.removeClass( 'mai-addon-loading' );
					});
				}, 1200 );

			} else {
				$card.removeClass( 'mai-addon-loading' );
				$loader.remove();
			}

		});
	})

})( this, jQuery );
