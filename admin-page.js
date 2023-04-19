
(function( $, wp, elves ){
	var elf_card = wp.template( 'elf-card' ),
		elf_row = wp.template( 'elf-row' ),
		$table = $('#elves-table'),
		$tbody = $table.find('tbody');

	var render_elves = function( elves ) {
		var receiving_from;

		$tbody.html('');
		if ( _.size( elves ) ) {
			_.each( elves, function ( elf ) {
				receiving_from = _.find(elves, function ( searchelf ) {
					return searchelf.shipping_to === elf.user_login;
				});
				$tbody.append( elf_row({
					elf_card: elf_card( elf ),
					shipping_to_card: ( elf.shipping_to && elves[ elf.shipping_to ] ) ? elf_card( elves[ elf.shipping_to ] ) : '',
					receiving_from_card: receiving_from ? elf_card( receiving_from ) : ''
				}));
			});
		}
	};

	var assign_elves = function( elves ) {
		if ( _.size( elves ) < 3 ) {
			alert( 'Too few elves!' );
			return elves;
		}

		// @todo: Split out elves to those with recipients assigned already and not, to enable batches of assignments, instead of all at once.

		var shuffled = _.shuffle( elves );

		_.each( shuffled, function( elf, index ) {
			if ( 0 === index ) {
				return;
			}
			shuffled[ index ].shipping_to = shuffled[ index - 1 ].user_login;
		} );

		shuffled[ 0 ].shipping_to = shuffled[ _.size( shuffled ) - 1 ].user_login;

		return shuffled;
	};

	render_elves( elves );

	$('.assign-elves').on('click', function(){
		elves = assign_elves( elves );
		elves = _.indexBy( elves, 'user_login' );
		render_elves( elves );
		$('.assign-elves').text( 'Accept assignments and save' ).off( 'click' ).on( 'click', function(){
			var data = {
				action : 'save_elf_assignees',
				_elfnonce : secretSanta.nonces.save_elf_assignees,
				elf : {}
			};

			_.each( elves, function( elf ) {
				data.elf[ elf.ID ] = elf.shipping_to;
			});

			$.post( ajaxurl, data, function( response ) {
				alert( response.data );
			});
		});
	})


}( jQuery, wp, secretSanta.elves ));
