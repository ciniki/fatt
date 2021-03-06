<?php
//
// Description
// -----------
// This method will add a new offering date for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to add the offering date to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_fatt_offeringDateAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'offering_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Offering'), 
        'start_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'datetimetoutc', 'name'=>'Date'), 
        'num_hours'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Hours'), 
        'day_number'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Day'), 
        'location_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Location'), 
        'address1'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Address'), 
        'address2'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Address'), 
        'city'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'City'), 
        'province'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Province'), 
        'postal'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Postal'), 
        'latitude'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Latitude'), 
        'longitude'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Longitude'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.offeringDateAdd');
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
    // Add the offering date to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.fatt.offeringdate', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
    }
    $offeringdate_id = $rc['id'];

    //
    // Update the offering the seats incase something has changed in location size
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringUpdateDatesSeats');
    $rc = ciniki_fatt_offeringUpdateDatesSeats($ciniki, $args['tnid'], $args['offering_id'], 'yes');
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
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'fatt');

    $rsp = array('stat'=>'ok', 'id'=>$offeringdate_id);

    //
    // Load the offering and return
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringLoad');
    $rc = ciniki_fatt_offeringLoad($ciniki, $args['tnid'], $args['offering_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['offering']) ) {
        $rsp['offering'] = $rc['offering'];
    }

    return $rsp;
}
?>
