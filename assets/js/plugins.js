( function ( document, $, undefined ) {

	$( '.mai-plugin-actions' ).on( 'click', '.button', function(e){
		e.preventDefault();

		var $button = $( e.target );
		var $card   = $button.parents( '.mai-plugin' );

		$card.addClass( 'mai-plugin-loading' );
		$card.append( '<span class="mai-plugin-loader"><span class="mai-plugin-loader-inner"><span class="mai-plugin-loader-circle"></span>' + maiPluginsVars.loadingText + '</span></span>' );

		$.ajax({
			method: 'GET',
			url: maiPluginsVars.ajaxUrl,
			data: {
				'action': 'mai_plugins_action',
				'nonce': maiPluginsVars.ajaxNonce,
				'slug': $(this).attr( 'data-slug' ),
				'trigger': $(this).attr( 'data-action' ),
			},
			success: function( response ) {
				$button.parent( '.mai-plugin-actions' ).html( response.data.html );

				if ( response.data.active ) {
					$card.addClass( 'mai-plugin-is-active' );
				} else {
					$card.removeClass( 'mai-plugin-is-active' );
				}
			},
			fail: function( response ) {
				console.log( 'Mai Plugins', response );
			}
		}).done( function( response ) {
			var $loader = $card.find( '.mai-plugin-loader' );

			if ( response.success ) {
				$loader.find( '.mai-plugin-loader-inner' ).append( '<span class="mai-plugin-loader-circle-done"></span><span class="mai-plugin-loader-checkmark"></span>' );
				$loader.addClass( 'mai-plugin-loader-complete' );

				setTimeout( function() {
					$loader.fadeOut( 400, function() {
						$card.removeClass( 'mai-plugin-loading' );
					});
				}, 1200 );

			} else {
				$card.removeClass( 'mai-plugin-loading' );
				$loader.remove();
			}
		});
	})

})( this, jQuery );
