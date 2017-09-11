<?php
//
// Description
// -----------
// This method will delete a offering from the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the offering is attached to.
// offering_id:         The ID of the offering to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_fatt_offeringRemove(&$ciniki, $business_id, $offering_id) {

    //
    // Get the uuid of the offering to be deleted
    //
    $strsql = "SELECT uuid "
        . "FROM ciniki_fatt_offerings "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'offering');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['offering']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.104', 'msg'=>'The offering does not exist'));
    }
    $offering_uuid = $rc['offering']['uuid'];

    //
    // Check if there is any customers still attached to the offering
    //
    $strsql = "SELECT 'items', COUNT(*) "
        . "FROM ciniki_fatt_offering_registrations "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.fatt', 'num');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
        $count = $rc['num']['items'];
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.105', 'msg'=>'There ' . ($count==1?'is':'are') . ' still ' . $count . ' registration' . ($count==1?'':'s') . ' assigned to that course offering.'));
    }

    //
    // Remove the offering dates
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_fatt_offering_dates "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'item');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $items = $rc['rows'];
        foreach($items as $fid => $item) {
            $rc = ciniki_core_objectDelete($ciniki, $business_id, 'ciniki.fatt.offeringdate', 
                $item['id'], $item['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.106', 'msg'=>'Unable to remove offering date', 'err'=>$rc['err']));
            }
        }
    }

    //
    // Remove the offering instructors
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_fatt_offering_instructors "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'item');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $items = $rc['rows'];
        foreach($items as $fid => $item) {
            $rc = ciniki_core_objectDelete($ciniki, $business_id, 'ciniki.fatt.offeringinstructor', 
                $item['id'], $item['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.107', 'msg'=>'Unable to remove offering instructor', 'err'=>$rc['err']));
            }
        }
    }

    //
    // Remove the offering
    //
    $rc = ciniki_core_objectDelete($ciniki, $business_id, 'ciniki.fatt.offering', $offering_id, $offering_uuid, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.108', 'msg'=>'Unable to remove offering', 'err'=>$rc['err']));
    }

    return array('stat'=>'ok');
}
?>
