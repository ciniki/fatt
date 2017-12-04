<?php
//
// Description
// ===========
// This function will update the list of instructors to which a offering is assigned.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the offering is attached to.
// 
function ciniki_fatt_offeringUpdateInstructors($ciniki, $tnid, $offering_id, $ninstructors) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');

    //
    // Get the list of existing/old instructos for the offering
    //
    $strsql = "SELECT instructor_id, id "
        . "FROM ciniki_fatt_offering_instructors "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
        . "";
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.fatt', 'instructors');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $oinstructors = $rc['instructors'];
    
    // 
    // Check if new instructors need to be added
    //
    foreach($ninstructors as $cid) {
        if( !isset($oinstructors[$cid]) ) {
            // Add instructor link
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.fatt.offeringinstructor', 
                array('instructor_id'=>$cid, 'offering_id'=>$offering_id), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Check of old instructors need to be removed
    //
    foreach($oinstructors as $cid => $object_id) {
        if( !in_array($cid, $ninstructors) ) {
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.fatt.offeringinstructor', $object_id, null, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
