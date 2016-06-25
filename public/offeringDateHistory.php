<?php
//
// Description
// -----------
// This method will return the list of actions that were applied to an element of an offering date. 
// This method is typically used by the UI to display a list of changes that have occured 
// on an element through time. This information can be used to revert elements to a previous value.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to get the details for.
// date_id:             The ID of the offering date to get the history for.
// field:               The field to get the history for. This can be any of the elements 
//                      returned by the ciniki.fatt.get method.
//
// Returns
// -------
// <history>
// <action user_id="2" date="May 12, 2012 10:54 PM" value="Date" age="2 months" user_display_name="Andrew" />
// ...
// </history>
//
function ciniki_fatt_offeringDateHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'date_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Date'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'field'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.offeringDateHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( field == 'start_date' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
        return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.fatt', 'ciniki_fatt_history', $args['business_id'], 'ciniki_fatt_offering_dates', $args['date_id'], $args['field'], 'utcdatetime');
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.fatt', 'ciniki_fatt_history', $args['business_id'], 'ciniki_fatt_offering_dates', $args['date_id'], $args['field']);
}
?>
