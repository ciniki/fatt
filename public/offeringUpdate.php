<?php
//
// Description
// ===========
// This method will update a offering in the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business the offering is attached to.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_fatt_offeringUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'offering_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Course'), 
        'course_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Course'), 
        'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'), 
        'price'=>array('required'=>'no', 'blank'=>'no', 'type'=>'currency', 'name'=>'Price'), 
        'flags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Options'), 
        'instructors'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Instructors'), 
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.offeringUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Check for new permalink
    //
    if( isset($args['permalink']) ) {
        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, name, permalink "
            . "FROM ciniki_fatt_offerings "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'offering');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2335', 'msg'=>'You already have a offering with this name, please choose another name'));
        }
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
    // Update the offering in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.fatt.offering', $args['offering_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
    }

    //
    // Update the instructors
    //
    if( isset($args['instructors']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringUpdateInstructors');
        $rc = ciniki_fatt_offeringUpdateInstructors($ciniki, $args['business_id'], $args['offering_id'], $args['instructors']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
            return $rc;
        }
    }

    //
    // Update the seats
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringUpdateDatesSeats');
    $rc = ciniki_fatt_offeringUpdateDatesSeats($ciniki, $args['business_id'], $args['offering_id'], 'yes');
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

    return array('stat'=>'ok');
}
?>
