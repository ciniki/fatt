<?php
//
// Description
// -----------
// This method will return the list of certs for a tenant.  
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
function ciniki_fatt_certList($ciniki) {
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.certList');
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
    // Get the list of certs
    //
    $strsql = "SELECT ciniki_fatt_certs.id, "
        . "ciniki_fatt_certs.name, "
        . "ciniki_fatt_certs.grouping, "
        . "ciniki_fatt_certs.status, "
        . "ciniki_fatt_certs.status AS status_text, "
        . "ciniki_fatt_certs.years_valid "
        . "FROM ciniki_fatt_certs "
        . "WHERE ciniki_fatt_certs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY grouping, name "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'certs', 'fname'=>'id', 'name'=>'cert',
            'fields'=>array('id', 'name', 'grouping', 'status', 'status_text', 'years_valid'),
            'maps'=>array('status_text'=>$maps['cert']['status'])),
        ));
    return $rc;
}
?>
