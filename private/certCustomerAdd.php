<?php
//
// Description
// -----------
// This method will add a new cert for the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business to add the cert to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_fatt__certCustomerAdd(&$ciniki, $business_id, $args) {
    //
    // Get the time information for business and user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    date_default_timezone_set($intl_timezone);

    if( !isset($args['date_expiry']) || $args['date_expiry'] == '' ) {
        //
        // Load the cert information
        //
        $strsql = "SELECT id, name, years_valid "
            . "FROM ciniki_fatt_certs "
            . "WHERE ciniki_fatt_certs.id = '" . ciniki_core_dbQuote($ciniki, $args['cert_id']) . "' "
            . "AND ciniki_fatt_certs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'cert');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['cert']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.10', 'msg'=>'The certification does not exist'));
        }
        $cert = $rc['cert'];

        //
        // Setup the expiry date, based on date received and years_valid from cert 
        //
        if( $cert['years_valid'] > 0 ) {
            $dt = new DateTime($args['date_received'], new DateTimeZone($intl_timezone));
            $dt->add(new DateInterval('P' . $cert['years_valid'] . 'Y'));
            $args['date_expiry'] = $dt->format('Y-m-d');
        } else {
            $args['date_expiry'] = '';
        }
    }

    //
    // Add the cert to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.fatt.certcustomer', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
    }
    $certcustomer_id = $rc['id'];

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $business_id, 'ciniki', 'fatt');

    return array('stat'=>'ok', 'id'=>$certcustomer_id);
}
?>
