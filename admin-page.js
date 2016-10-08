
(function( $, wp, elves ){
    var elf_card = wp.template( 'elf-card' ),
        elf_row = wp.template( 'elf-row' ),
        elf_restriction = wp.template( 'elf-restriction' ),
        $table = $('#elves-table'),
        $tbody = $table.find('tbody');

    $tbody.html( elf_row({
        elf_card:            elf_card( elves.bobsmith ),
        restrictions:        elf_restriction( { restriction : 'Cannot ship to XXX' } ),
        shipping_to_card:    elf_card( elves.bobsmith ),
        receiving_from_card: elf_card( elves.bobsmith )
    }) );

}( jQuery, wp, secretSanta.elves ));
