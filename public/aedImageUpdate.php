<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_fatt_aedImageUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'aedimage_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'AED Image'),
        'aed_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'AED'),
        'image_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Image'),
        'image_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Date'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.aedImageUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
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
    // Update the AED Image in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.fatt.aedimage', $args['aedimage_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.fatt');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'fatt');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['business_id'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.fatt.aedImage', 'object_id'=>$args['aedimage_id']));

    return array('stat'=>'ok');
}
?>
