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
// business_id:     The ID of the business the course is attached to.
// 
function ciniki_fatt_categoryUpdateCourses($ciniki, $business_id, $category_id, $ncoursess) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');

    //
    // Get the list of existing/old categories for the course
    //
    $strsql = "SELECT course_id, id "
        . "FROM ciniki_fatt_course_categories "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
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
            $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.fatt.course_category', 
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
            $rc = ciniki_core_objectDelete($ciniki, $business_id, 'ciniki.fatt.course_category', $object_id, null, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
