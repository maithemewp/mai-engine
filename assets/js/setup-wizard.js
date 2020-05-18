( function( $ ) {
	$( window ).on( 'popstate', function() {
		location.reload( true );
	} );

	var getUrlParameter = function getUrlParameter( sParam ) {
		var sPageURL      = window.location.search.substring( 1 ),
			sURLVariables = sPageURL.split( '&' ),
			sParameterName,
			i;

		for ( i = 0; i < sURLVariables.length; i ++ ) {
			sParameterName = sURLVariables[ i ].split( '=' );

			if ( sParameterName[ 0 ] === sParam ) {
				return sParameterName[ 1 ] === undefined ? true : decodeURIComponent( sParameterName[ 1 ] );
			}
		}
	};

	var continue_button = $( '.mai-continue' );
	var skip_button     = $( '.mai-skip' );
	var step_param      = getUrlParameter( 'step' );
	var current_step    = step_param ? $( '.mai-step-' + step_param ) : $( '.mai-step-welcome' );

	current_step.addClass( 'current-step' );
	current_step.fadeIn();

	skip_button.click( function() {
		var current_step = $( '.current-step' );

		current_step.removeClass( 'is-error is-success' );

		var next_step = current_step.next( '.mai-step' );

		if ( ! current_step.next().hasClass( 'mai-step' ) ) {
			next_step = $( '.mai-step-welcome' );
		}

		current_step.removeClass( 'current-step' );
		next_step.addClass( 'current-step' );
		current_step.delay( 500 ).fadeOut( 200 );
		next_step.delay( 700 ).fadeIn( 200 );

		var next_step_class = next_step.attr( 'class' ).replace( 'mai-step', '' ).replace( 'current-step', '' ).replace( ' ', '' ).replace( 'mai-step-', '' );

		if ( $( '.mai-step-done' ).hasClass( 'current-step' ) ) {
			continue_button.fadeOut();
			skip_button.fadeOut();
		}

		var newurl = window.location.protocol + '//' + window.location.host + window.location.pathname + '?page=mai-setup-wizard&step=' + next_step_class;
		window.history.pushState( { path: newurl }, '', newurl );
	} );

	continue_button.click( function() {
		$( this ).addClass( 'loading' );

		var current_step = $( '.current-step' );

		current_step.removeClass( 'is-error is-success' );

		var next_step = current_step.next( '.mai-step' );

		if ( ! current_step.next().hasClass( 'mai-step' ) ) {
			next_step = $( '.mai-step-welcome' );
		}

		var current_step_class = current_step.attr( 'class' ).replace( 'mai-step', '' ).replace( 'current-step', '' ).replace( ' ', '' ).replace( 'mai-step-', '' );
		var next_step_class    = next_step.attr( 'class' ).replace( 'mai-step', '' ).replace( 'current-step', '' ).replace( ' ', '' ).replace( 'mai-step-', '' );

		var current_nav = $( '.current-nav' );
		var next_nav    = current_nav.next( '.mai-nav' );

		var email_address = $( '.mai-email-address' ).val();
		var site_style    = $( 'input[name="mai-step-style"]:checked' ).val();
		var plugins       = [];
		var content       = [];

		$( 'input[name="mai-step-plugins[]"]:checked' ).each( function( plugin ) {
			plugins[ plugin ] = $( this ).val();
		} );

		$( 'input[name="mai-step-content[]"]:checked' ).each( function( type ) {
			content[ type ] = $( this ).val();
		} );

		var data = {
			action: 'mai_setup_wizard',
			current_step: current_step_class,
			email_address: email_address,
			site_style: site_style,
			plugins: plugins,
			content: content,
		};

		$.post( ajaxurl, data, function( response ) {

			// Show error.
			if ( response.hasOwnProperty( 'error' ) ) {
				continue_button.removeClass( 'loading' );
				current_step.addClass( 'is-error' );
				console.log( 'Mai Setup Wizard: ' + response.error );
				return;
			}

			// Update plugin list.
			if ( response.hasOwnProperty( 'plugin_list' ) ) {
				$( '.mai-step-plugins .mai-step-fields' ).empty().append( response.plugin_list );
			}

			current_step.addClass( 'is-success' );

			current_step.removeClass( 'current-step' );
			next_step.addClass( 'current-step' );
			current_step.delay( 1000 ).fadeOut( 200 );
			next_step.delay( 1400 ).fadeIn( 200 );

			current_nav.removeClass( 'current-nav' );
			next_nav.addClass( 'current-nav' );

			var newurl = window.location.protocol + '//' + window.location.host + window.location.pathname + '?page=mai-setup-wizard&step=' + next_step_class;
			window.history.pushState( { path: newurl }, '', newurl );

			continue_button.delay(4000).removeClass( 'loading' );
		} );
	} );

} )( jQuery );
