<?php
//
// Description
// ===========
// This function will be a callback when an item is added to ciniki.sapos.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_fatt_sapos_itemLookup($ciniki, $tnid, $args) {

    if( !isset($args['object']) || $args['object'] == ''
        || !isset($args['object_id']) || $args['object_id'] == '' 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.125', 'msg'=>'No item specified.'));
    }

    //
    // An offering was added to an invoice item, get the details and see if we need to 
    // create a registration for this offering
    //
    if( $args['object'] == 'ciniki.fatt.offering' ) {
        $strsql = "SELECT ciniki_fatt_offerings.id, "
            . "ciniki_fatt_offerings.start_date, "
            . "ciniki_fatt_offerings.date_string, "
            . "ciniki_fatt_offerings.location, "
            . "ciniki_fatt_offerings.seats_remaining, "
            . "ciniki_fatt_offerings.price, "
            . "ciniki_fatt_courses.code, "
            . "ciniki_fatt_courses.name, "
            . "ciniki_fatt_courses.taxtype_id "
            . "FROM ciniki_fatt_offerings "
            . "INNER JOIN ciniki_fatt_courses ON ("
                . "ciniki_fatt_offerings.course_id = ciniki_fatt_courses.id "
                . "AND ciniki_fatt_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_fatt_offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_fatt_offerings.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'offering');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['offering']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.126', 'msg'=>'Unable to find course'));
        }
        $offering = $rc['offering'];
        $item = array(
            'status'=>0,
            'object'=>'ciniki.fatt.offering',
            'object_id'=>$offering['id'],
            'code'=>'',
            'description'=>$offering['name'] . ' - ' . $offering['date_string'],
            'price_id'=>0,
            'quantity'=>1,
            'unit_amount'=>$offering['price'],
            'unit_discount_amount'=>0,
            'unit_discount_percentage'=>0,
            'shipped_quantity'=>0,
            'taxtype_id'=>$offering['taxtype_id'], 
            'notes'=>'',
            'registrations_available'=>$offering['seats_remaining'],
            );
        if( $offering['seats_remaining'] < 0 ) {
            $item['available_display'] = abs($offering['seats_remaining']) . ' oversold';
        } elseif( $offering['seats_remaining'] == 0 ) {
            $item['available_display'] = 'SOLD OUT';
        } elseif( $offering['seats_remaining'] > 0 ) {
            $item['available_display'] = $offering['seats_remaining'];
        }
        // Flags: No Quantity, Registration Item
        $item['flags'] = 0x28;

        if( isset($args['registration_status']) && $args['registration_status'] != '' ) {
            $item['registration_status'] = $args['registration_status'];
        }
        if( isset($args['student_id']) && $args['student_id'] != '' ) {
            $item['student_id'] = $args['student_id'];
        }

        return array('stat'=>'ok', 'item'=>$item);
    }

    return array('stat'=>'ok');
}
?>
