<?php
//
// Description
// -----------
// This method will add a new course for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to add the course to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_fatt_courseAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
        'code'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Code'), 
        'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'), 
        'sequence'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Sequence'), 
        'status'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Status'), 
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Primary Image'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'), 
        'price'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Price'), 
        'taxtype_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Taxes'), 
        'num_days'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Number of Days'), 
        'num_hours'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Number of Hours'), 
        'num_seats_per_instructor'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Number of Seats per Instructor'), 
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Flags'), 
        'cover_letter'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Cover Letter'), 
        'cert_form1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'1st Certification Form'), 
        'cert_form2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'2nd Certification Form'), 
        'welcome_msg'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Welcome Message Details'), 
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Categories'), 
        'bundles'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Bundles'), 
        'certs'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Certifications'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.courseAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Create the permalink
    //
    if( !isset($args['permalink']) || $args['permalink'] == '' ) {  
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
    }

    //
    // Check the permalink doesn't already exist
    //
    $strsql = "SELECT id "
        . "FROM ciniki_fatt_courses "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' " 
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'course');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.82', 'msg'=>'You already have a course with this name, please choose another name.'));
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.fatt');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Add the course to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.fatt.course', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
    }
    $course_id = $rc['id'];

    //
    // Update the categories
    //
    if( isset($args['categories']) && count($args['categories']) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'courseUpdateCategories');
        $rc = ciniki_fatt_courseUpdateCategories($ciniki, $args['tnid'], $course_id, $args['categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
            return $rc;
        }
    }

    //
    // Update the bundles
    //
    if( isset($args['bundles']) && count($args['bundles']) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'courseUpdateBundles');
        $rc = ciniki_fatt_courseUpdateCategories($ciniki, $args['tnid'], $course_id, $args['bundles']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
            return $rc;
        }
    }

    //
    // Update the certs
    //
    if( isset($args['certs']) && count($args['certs']) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'courseUpdateCerts');
        $rc = ciniki_fatt_courseUpdateCerts($ciniki, $args['tnid'], $course_id, $args['certs']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
            return $rc;
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.fatt');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'fatt');

    return array('stat'=>'ok', 'id'=>$course_id);
}
?>
