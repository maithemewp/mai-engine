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

	// ACF args to allow svgs in icon select fields.
	acf.add_filter( 'select2_args', function( args, $select, settings, field, instance ) {
		// Bail if `settings.field.data.key` is not set or is not `mai_icon_choices` or `mai_icon_brand_choices`.
		if ( ! settings.field.data.key || ! [ 'mai_icon_choices', 'mai_icon_brand_choices' ].includes( settings.field.data.key ) ) {
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

	/**
	 * Initialize the sortable field field.
	 */
	function initialize_sortable_field( field ) {

		// Bail if not a sortable field.
		if ( ! field.$el.hasClass( 'mai-sortable' ) ) {
			return;
		}

		// Add sortable
		field.$el.find( '.acf-checkbox-list' ).sortable( {
			items: '> li',
			handle: '> .mai-acf-sortable-handle',
			// forceHelperSize: true,
			placeholder: 'sortable-checkbox-placeholder',
			forcePlaceholderSize: true,
			scroll: true,
			create: function( event, ui ) {
				$( this ).find( 'li' ).append( '<span class="mai-acf-sortable-handle"><i class="dashicons dashicons-menu"></i></span>' );
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
		var $labels = field.$el.find( '.acf-radio-list label' );

		if ( $labels.length ) {
			$.each( $labels, function( index, value ) {
				$(this)
				.contents()
				.filter((i, node) => node.nodeType === Node.TEXT_NODE && '' !== node.textContent.trim())
				.wrap( '<span />' );
			});
		}
	}

	if ( typeof acf.add_action !== 'undefined' ) {

		/**
		 * ready & append (ACF5)
		 *
		 * These events are called when a field element is ready for initialization.
		 * - ready: on page load similar to $(document).ready()
		 * - append: on new DOM elements appended via repeater field or other AJAX calls
		 */
		acf.addAction( 'ready_field/key=mai_grid_block_show', initialize_sortable_field );
		acf.addAction( 'append_field/key=mai_grid_block_show', initialize_sortable_field );

		acf.addAction( 'ready_field/key=mai_column_background', initialize_color_field );
		acf.addAction( 'ready_field/key=mai_divider_color', initialize_color_field );
		acf.addAction( 'ready_field/key=mai_icon_color', initialize_color_field );
		acf.addAction( 'ready_field/key=mai_icon_background', initialize_color_field );

		acf.addAction( 'append_field/key=mai_column_background', initialize_color_field );
		acf.addAction( 'append_field/key=mai_divider_color', initialize_color_field );
		acf.addAction( 'append_field/key=mai_icon_color', initialize_color_field );
		acf.addAction( 'append_field/key=mai_icon_background', initialize_color_field );
	}

} )( jQuery );
