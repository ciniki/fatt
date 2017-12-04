<?php
//
// Description
// ===========
// This function will update the list of bundles to which a course is assigned.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the course is attached to.
// 
function ciniki_fatt_courseUpdateBundles($ciniki, $tnid, $course_id, $nbundles) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');

    //
    // Get the list of existing/old bundles for the course
    //
    $strsql = "SELECT bundle_id, id "
        . "FROM ciniki_fatt_course_bundles "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND course_id = '" . ciniki_core_dbQuote($ciniki, $course_id) . "' "
        . "";
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.fatt', 'bundles');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $obundles = $rc['bundles'];
    
    // 
    // Check if new bundles need to be added
    //
    foreach($nbundles as $cid) {
        if( !isset($obundles[$cid]) ) {
            // Add bundle link
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.fatt.course_bundle', 
                array('bundle_id'=>$cid, 'course_id'=>$course_id), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Check of old bundles need to be removed
    //
    foreach($obundles as $cid => $object_id) {
        if( !in_array($cid, $nbundles) ) {
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.fatt.course_bundle', $object_id, null, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
