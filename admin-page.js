
(function( $, wp, elves ){
    var elf_card = wp.template( 'elf-card' ),
        elf_row = wp.template( 'elf-row' ),
        $table = $('#elves-table'),
        $tbody = $table.find('tbody');

    if ( _.size( elves ) ) {
      $tbody.html('');
      _.each( elves, function( elf ) {
        $tbody.append( elf_row({
          elf_card:            elf_card( elf ),
          shipping_to_card:    '',
          receiving_from_card: ''
        }));
      });
    }


}( jQuery, wp, secretSanta.elves ));
