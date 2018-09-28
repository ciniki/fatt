<?php
//
// Description
// -----------
// This method will return the list of tenants and their certification statistics.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get certs for.
//
// Returns
// -------
//
function ciniki_fatt_certBusinessList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.certBusinessList');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Load fatt maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'maps');
    $rc = ciniki_fatt_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the list of tenants
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerList');
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x8000) ) {
        $rc = ciniki_customers_hooks_customerList($ciniki, $args['tnid'], array('type'=>30));
    } else {
        $rc = ciniki_customers_hooks_customerList($ciniki, $args['tnid'], array('type'=>2));
    }
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $customers = $rc['customers'];

    return array('stat'=>'ok', 'customers'=>$customers);
}
?>
