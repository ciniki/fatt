<?php
//
// Description
// ===========
// This method will return all the information about an aed.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the aed is attached to.
// aed_id:          The ID of the aed to get the details for.
//
// Returns
// -------
//
function ciniki_fatt_aedGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'aed_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'AED'),
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.aedGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'mysql');

    //
    // Return default for new AED
    //
    if( $args['aed_id'] == 0 ) {
        $aed = array('id'=>0,
            'customer_id'=>(isset($args['customer_id'])?$args['customer_id']:0),
            'location'=>'',
            'status'=>'10',
            'flags'=>0x10,
            'make'=>'',
            'model'=>'',
            'serial'=>'',
            'device_expiration'=>'',
            'primary_battery_expiration'=>'',
            'secondary_battery_expiration'=>'',
            'primary_adult_pads_expiration'=>'',
            'secondary_adult_pads_expiration'=>'',
            'primary_child_pads_expiration'=>'',
            'secondary_child_pads_expiration'=>'',
            'primary_image_id'=>0,
            'notes'=>'',
        );
    }

    //
    // Get the details for an existing AED
    //
    else {
        $strsql = "SELECT ciniki_fatt_aeds.id, "
            . "ciniki_fatt_aeds.customer_id, "
            . "ciniki_fatt_aeds.location, "
            . "ciniki_fatt_aeds.status, "
            . "ciniki_fatt_aeds.flags, "
            . "ciniki_fatt_aeds.make, "
            . "ciniki_fatt_aeds.model, "
            . "ciniki_fatt_aeds.serial, "
            . "DATE_FORMAT(ciniki_fatt_aeds.device_expiration, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS device_expiration, "
            . "DATE_FORMAT(ciniki_fatt_aeds.primary_battery_expiration, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS primary_battery_expiration, "
            . "DATE_FORMAT(ciniki_fatt_aeds.secondary_battery_expiration, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS secondary_battery_expiration, "
            . "DATE_FORMAT(ciniki_fatt_aeds.primary_adult_pads_expiration, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS primary_adult_pads_expiration, "
            . "DATE_FORMAT(ciniki_fatt_aeds.secondary_adult_pads_expiration, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS secondary_adult_pads_expiration, "
            . "DATE_FORMAT(ciniki_fatt_aeds.primary_child_pads_expiration, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS primary_child_pads_expiration, "
            . "DATE_FORMAT(ciniki_fatt_aeds.secondary_child_pads_expiration, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS secondary_child_pads_expiration, "
            . "ciniki_fatt_aeds.primary_image_id, "
            . "ciniki_fatt_aeds.notes "
            . "FROM ciniki_fatt_aeds "
            . "WHERE ciniki_fatt_aeds.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_fatt_aeds.id = '" . ciniki_core_dbQuote($ciniki, $args['aed_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'aed');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3263', 'msg'=>'AED not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['aed']) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3264', 'msg'=>'Unable to find AED'));
        }
        $aed = $rc['aed'];
    }

    if( $aed['customer_id'] > 0 ) {
        //
        // Get the customer details
        //
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
        $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['business_id'], array('customer_id'=>$aed['customer_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$aed['customer'] = $rc['customer'];
		$aed['customer_details'] = $rc['details'];
    } else {
        $aed['customer'] = array();
        $aed['customer_details'] = array();
    }

    return array('stat'=>'ok', 'aed'=>$aed);
}
?>
