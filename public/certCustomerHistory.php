<?php
//
// Description
// -----------
// This method will return the list of actions that were applied to an element of an cert. 
// This method is typically used by the UI to display a list of changes that have occured 
// on an element through time. This information can be used to revert elements to a previous value.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the details for.
// cert_id:         The ID of the cert to get the history for.
// field:               The field to get the history for. This can be any of the elements 
//                      returned by the ciniki.fatt.get method.
//
// Returns
// -------
// <history>
// <action user_id="2" date="May 12, 2012 10:54 PM" value="Certification Name" age="2 months" user_display_name="Andrew" />
// ...
// </history>
//
function ciniki_fatt_certCustomerHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'certcustomer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Certification'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'field'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.certCustomerHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( $args['field'] == 'date_received' || $args['field'] == 'date_expiry' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
        return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.fatt', 'ciniki_fatt_history',
            $args['tnid'], 'ciniki_fatt_cert_customers', $args['certcustomer_id'], $args['field'], 'date');
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.fatt', 'ciniki_fatt_history', $args['tnid'], 'ciniki_fatt_cert_customers', $args['certcustomer_id'], $args['field']);
}
?>
