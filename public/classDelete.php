<?php
//
// Description
// ===========
// This method returns all the information for a class (a group of offerings at the same time location)
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business the offering is attached to.
// offering_id:     The ID of the offering to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_classDelete($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'class_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Class'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.classDelete'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    //
    // Load the class details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'classLoad');
    $rc = ciniki_fatt_classLoad($ciniki, $args['business_id'], $args);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['class']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.151', 'msg'=>'That class does not exist'));
    } 
    $class = $rc['class'];

    if( isset($class['registrations']) && count($class['registrations']) > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.152', 'msg'=>'The class still has registrations.'));
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.fatt');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Remove the offerings for this class
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringRemove');
    foreach($class['offerings'] as $offering) {
        //
        // Remove the offering
        //
        $rc = ciniki_fatt_offeringRemove($ciniki, $args['business_id'], $offering['offering']['id']);
        if( $rc['stat'] != 'ok' ) {
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

    return array('stat'=>'ok');
}
?>
