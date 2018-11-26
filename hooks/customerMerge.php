<?php
//
// Description
// -----------
// Merge the mail from secondary_customer_id into primary_customer_id
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_fatt_hooks_customerMerge($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

    if( !isset($args['primary_customer_id']) || $args['primary_customer_id'] == '' 
        || !isset($args['secondary_customer_id']) || $args['secondary_customer_id'] == '' ) {
        return array('stat'=>'ok');
    }

    //
    // Keep track of how many items we've updated
    //
    $updated = 0;

    //
    // Get the list of cert customers to update
    //
    $strsql = "SELECT id "
        . "FROM ciniki_fatt_cert_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'items');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.165', 'msg'=>'Unable to find first aid certifications', 'err'=>$rc['err']));
    }
    $items = $rc['rows'];
    foreach($items as $i => $row) {
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.fatt.certcustomer', $row['id'], array('customer_id'=>$args['primary_customer_id']), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.172', 'msg'=>'Unable to update first aid certification.', 'err'=>$rc['err']));
        }
        $updated++;
    }

    //
    // Get the list of offering registrations to update
    //
    $strsql = "SELECT id, customer_id, student_id "
        . "FROM ciniki_fatt_offering_registrations "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
            . "OR student_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
            . ") "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'items');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.173', 'msg'=>'Unable to find first aid registrations', 'err'=>$rc['err']));
    }
    $items = $rc['rows'];
    foreach($items as $i => $row) {
        $update_args = array();
        if( $row['customer_id'] == $args['secondary_customer_id'] ) {
            $update_args['customer_id'] = $args['primary_customer_id'];
        }
        if( $row['student_id'] == $args['secondary_customer_id'] ) {
            $update_args['student_id'] = $args['primary_customer_id'];
        }
        if( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.fatt.offeringregistration', $row['id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.174', 'msg'=>'Unable to update first aid registration.', 'err'=>$rc['err']));
            }
            $updated++;
        }
    }

    //
    // Get the list of cert customers to update
    //
    $strsql = "SELECT id "
        . "FROM ciniki_fatt_aeds "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'items');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.175', 'msg'=>'Unable to find first aid certifications', 'err'=>$rc['err']));
    }
    $items = $rc['rows'];
    foreach($items as $i => $row) {
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.fatt.aed', $row['id'], array('customer_id'=>$args['primary_customer_id']), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.176', 'msg'=>'Unable to update first aid certification.', 'err'=>$rc['err']));
        }
        $updated++;
    }

    if( $updated > 0 ) {
        //
        // Update the last_change date in the tenant modules
        // Ignore the result, as we don't want to stop user updates if this fails.
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
        ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, 'ciniki', 'customers');
    }

    return array('stat'=>'ok', 'updated'=>$updated);
}
?>
