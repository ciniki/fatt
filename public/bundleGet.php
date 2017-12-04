<?php
//
// Description
// ===========
// This method will return all the information about a bundle.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the bundle is attached to.
// bundle_id:       The ID of the bundle to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_bundleGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'bundle_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Course'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.bundleGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    if( $args['bundle_id'] == 0 ) {
        return array('stat'=>'ok', 'bundle'=>array(
            'name'=>'',
            ));
    }

    //
    // Get the bundle details
    //
    $strsql = "SELECT ciniki_fatt_bundles.id, "
        . "ciniki_fatt_bundles.name "
        . "FROM ciniki_fatt_bundles "
        . "WHERE ciniki_fatt_bundles.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_fatt_bundles.id = '" . ciniki_core_dbQuote($ciniki, $args['bundle_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'bundles', 'fname'=>'id', 'name'=>'bundle',
            'fields'=>array('id', 'name')),
    ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['bundles']) || !isset($rc['bundles'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.63', 'msg'=>'Unable to find bundle'));
    }
    $rsp = array('stat'=>'ok', 'bundle'=>$rc['bundles'][0]['bundle']);

    //
    // Get the list of courses for the bundle
    //
    $rsp['bundle']['courses'] = '';
    $strsql = "SELECT ciniki_fatt_courses.id, "
        . "ciniki_fatt_courses.name, "
        . "IFNULL(ciniki_fatt_course_bundles.id, 0) AS link_id "
        . "FROM ciniki_fatt_courses "
        . "LEFT JOIN ciniki_fatt_course_bundles ON ("
            . "ciniki_fatt_courses.id = ciniki_fatt_course_bundles.course_id "
            . "AND ciniki_fatt_course_bundles.bundle_id = '" . ciniki_core_dbQuote($ciniki, $args['bundle_id']) . "' "
            . "AND ciniki_fatt_course_bundles.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_fatt_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY ciniki_fatt_courses.name "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'courses', 'fname'=>'id', 'name'=>'item',
            'fields'=>array('id', 'name', 'link_id')),
    ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['courses'] = array();
    if( isset($rc['courses']) ) {
        $rsp['courses'] = $rc['courses'];
        foreach($rsp['courses'] as $cid => $item) {
            if( $item['item']['link_id'] > 0 ) {
                $rsp['bundle']['courses'] .= ($rsp['bundle']['courses']!=''?',':'') . $item['item']['id'];
            }
            unset($rsp['courses'][$cid]['item']['link_id']);
        }
    }

    return $rsp;
}
?>
