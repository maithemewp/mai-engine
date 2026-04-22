wp.domReady( () => {
	wp.blocks.unregisterBlockStyle(
		'core/button',
		[ 'outline', 'squared', 'fill' ]
	);

	wp.blocks.registerBlockStyle( 'core/button', {
		name: 'secondary',
		label: maiEditorVars.secondary
	} );

	wp.blocks.registerBlockStyle( 'core/button', {
		name: 'outline',
		label: maiEditorVars.outline
	} );

	wp.blocks.registerBlockStyle( 'core/button', {
		name: 'link',
		label: maiEditorVars.link
	} );
} );

( function( $ ) {
	if ( 'object' !== typeof acf ) {
		return;
	}

	// ACF color picker default color palette.
	acf.add_filter( 'color_picker_args', function( args, $field ) {
		args.palettes = maiEditorVars.palette;
		return args;
	});

	// Allow svgs in icon select fields. ACF < 2.6.8.
	acf.add_filter( 'select2_args', function( args, $select, settings, field, instance ) {
		// Bail if `field.data( 'key' )` is not set or is not `mai_icon_choices` or `mai_icon_brand_choices`.
		if ( ! field.data( 'key' ) || ! [ 'mai_icon_choices', 'mai_icon_brand_choices' ].includes( field.data( 'key' ) ) ) {
			return args;
		}

		// Taken from `acf-input.js`, just removed escaping from `$selection.html(acf.strEscape(selection.text));`.
		args.templateSelection = function( selection ) {
			var $selection = $( '<span class="acf-selection"></span>' );
			$selection.html( selection.text );
			$selection.data( 'element', selection.element );
			return $selection;
		};

		return args;
	});

	// Allow svgs in icon select fields. ACF >= 2.6.8.
	acf.add_filter( 'select2_escape_markup', function( escaped_value, original_value, $select, settings, field, instance ) {
		// Bail if `field.data( 'key' )` is not set or is not `mai_icon_choices` or `mai_icon_brand_choices`.
		if ( ! field.data( 'key' ) || ! [ 'mai_icon_choices', 'mai_icon_brand_choices' ].includes( field.data( 'key' ) ) ) {
			return escaped_value;
		}

		return original_value;
	});

	var icons = [ 'mai_icon_choices', 'mai_icon_brand_choices' ];
	var post  = maiEditorVars.post;
	var term  = maiEditorVars.term;
	// var user  = maiEditorVars.user;

	acf.addFilter( 'select2_ajax_data', function( data, args, $input, field, instance ) {
		// If Mai Icon or Icon (Brands) select field.
		if ( icons.includes( data.field_key ) ) {
			data.style = acf.getField( 'mai_icon_style' ).val(); // Style.
		}

		// Mai Post Grid.
		if ( Object.values( post ).includes( data.field_key ) ) {

			// Bail if the post_type field.
			if ( post.post_type === data.field_key ) {
				return data;
			}

			// If Posts/Entries field.
			if ( post.post__in === data.field_key ) {
				data.post_type = getPostType( $input, post );
			}

			// If Exclude Entries field.
			if ( post.post__not_in === data.field_key ) {
				data.post_type = getPostType( $input, post );
			}

			// If Taxonomy field.
			if ( post.taxonomy === data.field_key ) {
				data.post_type = getPostType( $input, post );
			}

			// If Terms field.
			if ( post.terms === data.field_key ) {
				data.taxonomy = getRowTaxonomy( $input, post );
			}

			// If Parent field
			if ( post.post_parent__in === data.field_key ) {
				data.post_type = getPostType( $input, post );
			}
		}

		// Mai Term Grid.
		if ( Object.values( term ).includes( data.field_key ) ) {

			// Bail if the taxonomy field.
			if ( term.taxonomy === data.field_key ) {
				return data;
			}

			// If Terms/Entries field.
			if ( term.include === data.field_key ) {
				data.taxonomy = getTaxonomy( $input, term );
			}

			// If Exclude Entries field.
			if ( term.exclude === data.field_key ) {
				data.taxonomy = getTaxonomy( $input, term );
			}

			// If Parent field.
			if ( term.parent === data.field_key ) {
				data.taxonomy = getTaxonomy( $input, term );
			}
		}

		return data;
	} );

	function getPostType( $input, keys ) {
		var $field = $input.parents( '.acf-fields' ).find( '.acf-field[data-key="' + keys.post_type + '"]' );
		return acf.getField( $field ).val();
	}

	function getRowTaxonomy( $input, keys ) {
		var $field = $input.parents( '.acf-row' ).find( '.acf-field[data-key="' + keys.taxonomy + '"]' );
		return acf.getField( $field ).val();
	}

	function getTaxonomy( $input, keys ) {
		var $field = $input.parents( '.acf-fields' ).find( '.acf-field[data-key="' + keys.taxonomy + '"]' );
		return acf.getField( $field ).val();
	}

	var colorFieldKeys = [
		'mai_column_background',
		'mai_divider_color',
		'mai_icon_color',
		'mai_icon_background'
	];

	/**
	 * Initialize the sortable field field.
	 */
	function initialize_sortable_field( field ) {

		// Bail if not a sortable field.
		if ( ! field.$el || ! field.$el.hasClass( 'mai-sortable' ) ) {
			return;
		}

		var $list = field.$el.find( '.acf-checkbox-list' );

		// Bail if no list or already initialized.
		if ( ! $list.length || $list.hasClass( 'ui-sortable' ) ) {
			return;
		}

		// Add sortable
		$list.sortable( {
			items: '> li',
			handle: '> .mai-acf-sortable-handle',
			// forceHelperSize: true,
			placeholder: 'sortable-checkbox-placeholder',
			forcePlaceholderSize: true,
			scroll: true,
			create: function( event, ui ) {
				$( this ).find( 'li' ).each( function() {
					var $li = $( this );
					if ( ! $li.children( '.mai-acf-sortable-handle' ).length ) {
						$li.append( '<span class="mai-acf-sortable-handle"><i class="dashicons dashicons-menu"></i></span>' );
					}
				} );
			},
			stop: function( event, ui ) {},
			update: function( event, ui ) {
				$( this ).find( 'input[type="checkbox"]' ).trigger( 'change' );
			}
		} );
	}

	/**
	 * Adds spans to label text.
	 */
	function initialize_color_field( field ) {
		if ( ! field.$el || ! field.get || colorFieldKeys.indexOf( field.get( 'key' ) ) === -1 ) {
			return;
		}

		var $labels = field.$el.find( '.acf-radio-list label' );

		if ( ! $labels.length ) {
			return;
		}

		$.each( $labels, function() {
			var $label = $( this );
			// Bail if already wrapped.
			if ( $label.children( 'span' ).length ) {
				return;
			}
			$label
				.contents()
				.filter( ( i, node ) => node.nodeType === Node.TEXT_NODE && '' !== node.textContent.trim() )
				.wrap( '<span />' );
		} );
	}

	if ( typeof acf.add_action !== 'undefined' ) {

		/**
		 * new_field fires whenever acf.newField() creates a field instance.
		 * This is the reliable hook in the ACF v3 block inspector, where the
		 * legacy `ready_field`/`append_field` tagged chain doesn't consistently
		 * fire. Both handlers self-filter and are idempotent.
		 */
		acf.addAction( 'new_field', initialize_sortable_field );
		acf.addAction( 'new_field', initialize_color_field );

		// Re-init after block re-mount (deselect/reselect in v3 blocks).
		acf.addAction( 'remount', function( $el ) {
			acf.getFields( { parent: $el } ).forEach( function( field ) {
				initialize_sortable_field( field );
				initialize_color_field( field );
			} );
		} );
	}
} )( jQuery );
