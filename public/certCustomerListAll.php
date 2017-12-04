<?php
//
// Description
// ===========
// This method returns a complete list of customer certifications. This is used by scripts
// to get the list and run updates, no UI components as of initial build.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the cert is attached to.
// 
// Returns
// -------
//
function ciniki_fatt_certCustomerListAll($ciniki) {
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
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.certCustomerListAll'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the time information for tenant and user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    date_default_timezone_set($intl_timezone);

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $mysql_date_format = ciniki_users_dateFormat($ciniki, 'mysql');
    $php_date_format = ciniki_users_dateFormat($ciniki, 'php');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    //
    // Get the cert customer details
    //
    $strsql = "SELECT ciniki_fatt_cert_customers.id AS certcustomer_id, "
        . "ciniki_fatt_cert_customers.cert_id, "
        . "ciniki_fatt_cert_customers.customer_id, "
        . "ciniki_fatt_cert_customers.offering_id, "
        . "DATE_FORMAT(ciniki_fatt_cert_customers.date_received, '" . ciniki_core_dbQuote($ciniki, $mysql_date_format) . "') AS date_received, "
        . "DATE_FORMAT(ciniki_fatt_cert_customers.date_expiry, '" . ciniki_core_dbQuote($ciniki, $mysql_date_format) . "') AS date_expiry, "
        . "ciniki_fatt_cert_customers.flags "
        . "FROM ciniki_fatt_cert_customers "
        . "WHERE ciniki_fatt_cert_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY cert_id, customer_id "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.certs', array(
        array('container'=>'certs', 'fname'=>'cert_id', 'fields'=>array('cert_id')),
        array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array('customer_id', 'cert_id', 'date_received', 'flags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return $rc;
}
?>
