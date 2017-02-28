<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_fatt_aedFormatExpirationAge($ciniki, $age) {
    $text = '';

    if( $age > 1 ) {
        $text = $age . ' days';
    } elseif( $age == 1 ) {
        $text = 'tomorrow';
    } elseif( $age == 0 ) {
        $text = 'today';
    } elseif( $age < 0 ) {
        $text = abs($age) . ' days ago';
    }

    return $text;
}
?>
