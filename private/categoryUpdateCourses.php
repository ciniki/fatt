<?php
//
// Description
// ===========
// This function will update the list of categories to which a course is assigned.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the course is attached to.
// 
function ciniki_fatt_categoryUpdateCourses($ciniki, $tnid, $category_id, $ncoursess) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');

    //
    // Get the list of existing/old categories for the course
    //
    $strsql = "SELECT course_id, id "
        . "FROM ciniki_fatt_course_categories "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND category_id = '" . ciniki_core_dbQuote($ciniki, $category_id) . "' "
        . "";
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.fatt', 'categories');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $ocourses = $rc['categories'];
    
    // 
    // Check if new categories need to be added
    //
    foreach($ncoursess as $cid) {
        if( !isset($ocourses[$cid]) ) {
            // Add category link
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.fatt.course_category', 
                array('course_id'=>$cid, 'category_id'=>$category_id), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Check of old categories need to be removed
    //
    foreach($ocourses as $cid => $object_id) {
        if( !in_array($cid, $ncoursess) ) {
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.fatt.course_category', $object_id, null, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
