//@prepros-prepend vendor/jquery-validation/jquery.validate.min.js


/*
================================================================
FORM VALIDATION
================================================================
*/

( function( $, undefined ) {

	$( '.js-mes-form' ).each( function() {
		var $form = $(this),
			$submitBtn = $form.find( '.js-mes-form-submit' ),
			$validator;

		// Validate locations inputs if custom location input is changed
		$form.find( 'input[name="mes_location_custom"]' ).on( 'change keyup', function( event ) {
			if ( $validator.element ) {
				$validator.element( 'input[name="mes_location[]"]' );
			}
		} );
		
		// Validate subjects inputs if custom subject input is changed
		$form.find( 'input[name="mes_subject_custom"]' ).on( 'change keyup', function( event ) {
			if ( $validator.element ) {
				$validator.element( 'input[name="mes_subject[]"]' );
			}
		} );


		$validator = $form.validate( {
			errorPlacement: function( $errorEl, $parentEl ) {
				if ( $parentEl.parents( '.mes-form-section--disclaimer' ).length ) {
					$parentEl.parents( '.mes-form-section--disclaimer' ).after( $errorEl );
				} else if ( $parentEl.parents( '.mes-collection' ).length ) {
					$parentEl.parents( '.mes-collection' ).after( $errorEl );
				} else {
					$parentEl.after( $errorEl );
				}
			},
			errorElement: "span",
			errorClass: 'mes-error',
			errorContainer: $form.find( '.js-mes-form-submission-errors' ),
			rules: {
				'mes_email': {
					required: true
				},
				'mes_header': {
					required: true
				},
				'mes_story': {
					required: true
				},
				'mes_summary': {
					required: true
				},
				'mes_location[]': {
					required: function() {
						// Only required if custom field not filled
						return $form.find( 'input[name="mes_location_custom"]' ).val() == false;
					}
				},
				'mes_subject[]': {
					required: function() {
						// Only required if custom field not filled
						return $form.find( 'input[name="mes_subject_custom"]' ).val() == false;
					}
				},
				'mes_interpret[]': {
					required: true
				},
				'mes_approve': {
					required: true
				}
			},
			messages: {
				'mes_email': {
					required: mesGlobal.messages.mes_email.required,
					email: mesGlobal.messages.mes_email.email
				},
				'mes_header': {
					required: mesGlobal.messages.mes_header.required
				},
				'mes_story': {
					required: mesGlobal.messages.mes_story.required
				},
				'mes_summary': {
					required: mesGlobal.messages.mes_summary.required
				},
				'mes_location[]': {
					required: mesGlobal.messages.mes_location.required
				},
				'mes_subject[]': {
					required: mesGlobal.messages.mes_subject.required
				},
				'mes_interpret[]': {
					required: mesGlobal.messages.mes_interpret.required
				},
				'mes_approve': {
					required: mesGlobal.messages.mes_approve.required
				}
			},
			submitHandler: function() {
				$submitBtn.attr( 'disabled', 'disabled' ).find( 'span' ).text( mesGlobal.btnProcesString );
				$form.addClass( 'loading' );

				var data = $form.serializeArray();

				data.push( { name: "action", value: 'mes_submit_story' } );

				var request = $.ajax( {
					method: "POST",
					url: mesGlobal.ajaxUrl,
					data: $.param( data )
				} );

				request.done( function( response ) {
					response = JSON.parse( response );

					if ( response.success ) {
						window.location.replace( mesGlobal.completetionUrl );
					} else {
						$submitBtn.removeAttr( 'disabled' ).find( 'span' ).text( mesGlobal.btnDefaultString );
						$form.removeClass( 'loading' );

						var errors = {},
						$field;

						$.each( response.errors, function( index, value ) {

							$field = $( '[name="' + index + '"]' );

							if ( $field.length ) {
								errors[index] = value;
							}
							
						} );

						$validator.showErrors( errors );
					}
				} );
			}
		} );
	} );

} )( jQuery );



/*
================================================================
TOGGLE FORM DESCRIPTION TEXTS
================================================================
*/

( function( $, undefined ) {

	$( '.js-mes-form-desc' ).each( function() {
		var $this = $(this),
			$toggle = $( '<a href="#" class="mes-toggle-explanation">' + mesGlobal.showDescString + '</a>' );

		$toggle.on( 'click', function( event ) {
			event.preventDefault();

			if ( $this.is( ':visible' ) ) {
				$this.hide();
				$toggle.text( mesGlobal.showDescString );
			} else {
				$this.show();
				$toggle.text( mesGlobal.hideDescString );
			}
		} );

		$this.hide().before( $toggle );
	} );

} )( jQuery );