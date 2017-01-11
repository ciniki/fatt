<?php
//
// Description
// -----------
// This method returns the detail of attendance.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business to get certs for.
//
// Returns
// -------
//
function ciniki_fatt_attendanceReport($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.attendanceReport');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Load fatt maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'maps');
    $rc = ciniki_fatt_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the registrations by month
    //
    $strsql = "SELECT DATE_FORMAT(ciniki_fatt_offerings.start_date, '%m') AS month, "
        . "DATE_FORMAT(ciniki_fatt_offerings.start_date, '%b') AS month_text, "
        . "DATE_FORMAT(ciniki_fatt_offerings.start_date, '%Y') AS year, "
        . "ciniki_fatt_offering_registrations.status, "
        . "COUNT(ciniki_fatt_offering_registrations.id) AS num "
        . "FROM ciniki_fatt_offerings, ciniki_fatt_offering_registrations "
        . "WHERE ciniki_fatt_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_fatt_offerings.id = ciniki_fatt_offering_registrations.offering_id "
        . "AND ciniki_fatt_offering_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "GROUP BY year, month "
        . "ORDER BY year DESC, month DESC "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'item');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
        return array('stat'=>'ok', 'months'=>array());
    }
    $rows = $rc['rows'];
    $months = [];
    foreach($rows as $row) {
        $mon = $row['year'] . '-' . $row['month'];
        if( !isset($months[$mon]) ) {
            $months[$mon] = array(
                'month_text'=>$row['month_text'] . ' ' . $row['year'],
                'sort_month'=>$row['year'] . $row['month'],
                'num_incomplete'=>0,
                'num_pass'=>0,
                'num_cancel'=>0,
                'num_noshow'=>0,
                'num_total'=>0,
                );
        }
        if( $row['status'] == 0 ) {
            $months[$mon]['num_incomplete'] += $row['num'];
            $months[$mon]['num_total'] += $row['num'];
        } elseif( $row['status'] == 10 ) {
            $months[$mon]['num_pass'] += $row['num'];
            $months[$mon]['num_total'] += $row['num'];
        } elseif( $row['status'] == 30 ) {
            $months[$mon]['num_cancel'] += $row['num'];
            $months[$mon]['num_total'] += $row['num'];
        } elseif( $row['status'] == 40 ) {
            $months[$mon]['num_noshow'] += $row['num'];
            $months[$mon]['num_total'] += $row['num'];
        }
    }

    return array('stat'=>'ok', 'months'=>$months);
}
?>
