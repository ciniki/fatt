<?php
//
// Description
// -----------
// This method will add a new message for the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business to add the message to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_fatt_messageAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'object'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Object'), 
        'object_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Object ID'), 
        'status'=>array('required'=>'yes', 'blank'=>'no', 'validlist'=>array('0', '10', '20'), 'name'=>'Status'), 
        'days'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Days'), 
        'subject'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subject'), 
        'message'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Message'), 
        'parent_subject'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Parent Subject'), 
        'parent_message'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Parent Message'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.messageAdd');
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
    // Add the message to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.fatt.message', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
    }
    $message_id = $rc['id'];

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

    return array('stat'=>'ok', 'id'=>$message_id);
}
?>
