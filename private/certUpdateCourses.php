<?php
//
// Description
// ===========
// This function will update the list of certs to which a course is assigned.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the course is attached to.
// 
function ciniki_fatt_certUpdateCourses($ciniki, $tnid, $cert_id, $ncourses) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');

    //
    // Get the list of existing/old courses for the cert
    //
    $strsql = "SELECT course_id, id "
        . "FROM ciniki_fatt_course_certs "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND cert_id = '" . ciniki_core_dbQuote($ciniki, $cert_id) . "' "
        . "";
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.fatt', 'courses');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $ocourses = $rc['courses'];
    
    // 
    // Check if new certs need to be added
    //
    foreach($ncourses as $cid) {
        if( !isset($ocourses[$cid]) ) {
            // Add cert link
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.fatt.course_cert', 
                array('course_id'=>$cid, 'cert_id'=>$cert_id), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Check of old certs need to be removed
    //
    foreach($ocourses as $cid => $object_id) {
        if( !in_array($cid, $ncourses) ) {
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.fatt.course_cert', $object_id, null, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
