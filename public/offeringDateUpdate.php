<?php
//
// Description
// ===========
// This method will update a offering date in the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business the offering date is attached to.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_fatt_offeringDateUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'date_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Date'), 
        'start_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'datetimetoutc', 'name'=>'Start Date'), 
        'num_hours'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Hours'), 
        'day_number'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Day'), 
        'location_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Location'), 
        'address1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Address'), 
        'address2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Address'), 
        'city'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'City'), 
        'province'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Province'), 
        'postal'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Postal'), 
        'latitude'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Latitude'), 
        'longitude'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Longitude'), 
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.offeringDateUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the existing offering date details
    //
    $strsql = "SELECT uuid, offering_id "
        . "FROM ciniki_fatt_offering_dates "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'date');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['date']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2346', 'msg'=>'Offering Date not found'));
    }
    $offeringdate = $rc['date'];

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
    // Update the offering date in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.fatt.offeringdate', $args['date_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
    }

    //
    // Update the offering the seats incase something has changed in location size
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringUpdateDatesSeats');
    $rc = ciniki_fatt_offeringUpdateDatesSeats($ciniki, $args['business_id'], $offeringdate['offering_id'], 'yes');
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

    $rsp = array('stat'=>'ok');

    //
    // Load the offering and return
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringLoad');
    $rc = ciniki_fatt_offeringLoad($ciniki, $args['business_id'], $offeringdate['offering_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['offering']) ) {
        $rsp['offering'] = $rc['offering'];
    }

    return $rsp;
}
?>
