( function( $ ) {

	'use strict';

	$( window ).on( 'popstate', function() {
		location.reload( true );
	} );

	var getUrlParameter = function( param ) {
		var pageURL       = window.location.search.substring( 1 );
		var parameters    = pageURL.split( '&' );
		var parameterName = false;

		for ( var i = 0; i < parameters.length; i ++ ) {
			parameterName = parameters[ i ].split( '=' );

			if ( parameterName[ 0 ] === param ) {
				return parameterName[ 1 ] === undefined ? 'welcome' : decodeURIComponent( parameterName[ 1 ] );
			}
		}
	};

	var data  = typeof setupWizardData === 'undefined' ? [] : setupWizardData;
	var steps = $( '.setup-wizard .step' );
	var page  = getUrlParameter( 'page' );

	var hideOtherSteps = function( currentStepID, steps, speed = 300 ) {
		steps.each( function() {
			var step = $( this );
			if ( step.attr( 'id' ) !== currentStepID ) {
				step.fadeOut( speed );
			} else {
				setTimeout( function() {
					step.fadeIn( speed );
				}, speed );
			}
		} );
	};

	hideOtherSteps( data.currentStep, steps, 0 );

	steps.each( function() {
		var step = {
			object: $( this ),
			id: $( this ).attr( 'id' ),
			submit: $( this ).find( '#submit' ),
			skip: $( this ).find( '#skip' ),
			previous: $( this ).find( '#previous' ),
			next: $( this ).next( steps ),
			prev: $( this ).prev( steps ),
		};

		step.submit.click( function() {
			var fields      = $( '#' + step.id + ' input:enabled' );
			var fields_type = fields.attr( 'type' ) ? fields.attr( 'type' ) : 'text';
			var counter     = 0;

			if ( 'checkbox' === fields_type || 'radio' === fields_type ) {
				fields = $( '#' + step.id + ' input:enabled:checked' );
			}

			step.submit.text( step.submit.attr( 'data-loading' ) );

			$( '[data-status]' ).removeAttr( 'data-status' );
			$( fields[ counter ] ).closest( 'li' ).attr( 'data-status', 'running' );
			postAjax( step, fields, counter );
		} );

		step.skip.click( function() {
			$( '[data-status]' ).removeAttr( 'data-status' );
			changeStep( step.next.attr( 'id' ) );
		} );

		step.previous.click( function() {
			$( '[data-status]' ).removeAttr( 'data-status' );
			changeStep( step.prev.attr( 'id' ) );
		} );
	} );

	var postAjax = function( step, fields, counter ) {
		$( fields[ counter ] ).closest( 'li' ).attr( 'data-status', 'running' );
		$( fields[ counter - 1 ] ).closest( 'li' ).attr( 'data-status', 'complete' );

		if ( counter >= fields.length ) {
			setTimeout( function() {
				changeStep( step.next.attr( 'id' ) );
				step.submit.text( step.submit.attr( 'data-default' ) );
			}, 1000 );

			return;
		}

		var field_element = $( fields[ counter ] );
		var attributes    = field_element[ 0 ].attributes;
		var field         = {};

		if ( 'object' === typeof attributes ) {
			$.each( attributes, function() {
				field[ this.name ] = this.value;
			} );
		}

		field.value = field_element.val();

		$.ajax( {
			type: 'post',
			dataType: 'json',
			url: data.ajaxUrl,
			timeout: 30000,
			data: {
				action: 'mai_setup_wizard_' + step.id,
				counter: counter,
				field: field,
			},
			success: function( response ) {
				setTimeout( function() {
					handleResponse( response, step, fields, counter, 'success' );
				}, 1000 );
			},
			error: function( response ) {
				setTimeout( function() {
					handleResponse( response, step, fields, counter, 'error' );
				}, 1000 );
			}
		} );
	};

	var handleResponse = function( response, step, fields, counter, type ) {
		var success = response.hasOwnProperty( 'success' ) && response.success && 'success' === type;

		if ( response.hasOwnProperty( 'status' ) && 'newAJAX' === response.status ) {
			postAjax( step, fields, counter );

			return;
		}

		if ( ! success && 'error' !== type ) {
			if ( response.hasOwnProperty( 'data' ) ) {
				$( '#' + step.id + ' .error' ).show().text( response.data );
			}

			step.submit.text( step.submit.attr( 'data-default' ) );

			$( fields[ counter ] ).closest( 'li' ).removeAttr( 'data-status' );
			console.log( response );
			return;
		}

		console.log( response );

		counter ++;

		postAjax( step, fields, counter );
	};

	var changeStep = function( next_or_prev ) {
		var newurl  = window.location.protocol + '//' + window.location.host + window.location.pathname + '?page=' + page + '&step=' + next_or_prev;
		var current = $( '#' + next_or_prev );
		var next    = current.next( steps );
		var prev    = next.prev( steps );

		if ( current.hasClass( 'step' ) && prev.hasClass( 'step' ) ) {
			window.history.pushState( { path: newurl }, '', newurl );
			hideOtherSteps( next_or_prev, steps );
		}
	};

	var toggleChosenDemo = function( chosenDemo ) {
		var elements = $( '[name="plugins"], [name="content"], [name="widgets"], [name="customizer"]' );

		elements.each( function() {
			var element  = $( this );
			var demoAttr = element.attr( 'data-demo' );
			var checked  = chosenDemo === demoAttr;
			var disabled = chosenDemo !== demoAttr;
			var hidden   = chosenDemo !== demoAttr;

			element.attr( 'checked', checked );
			element.prop( 'disabled', disabled );
			element.closest( 'li' ).prop( 'hidden', hidden );
		} );
	};

	var onClick = function() {
		$( '[name="demo"]' ).click( function() {
			toggleChosenDemo( $( this ).val() );
		} );
	};

	var onReady = function() {
		toggleChosenDemo( data.chosenDemo );
		$( 'p.error, p.success' ).hide();

		onClick();
	};

	return onReady();
} )( jQuery );

