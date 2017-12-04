<?php
//
// Description
// ===========
// This method will return all the information about an aed image.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the aed image is attached to.
// aedimage_id:          The ID of the aed image to get the details for.
//
// Returns
// -------
//
function ciniki_fatt_aedImageGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'aedimage_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'AED Image'),
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.aedImageGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $php_date_format = ciniki_users_dateFormat($ciniki, 'php');
    $mysql_date_format = ciniki_users_dateFormat($ciniki, 'mysql');

    //
    // Return default for new AED Image
    //
    if( $args['aedimage_id'] == 0 ) {
        $dt = new DateTime('now', new DateTimeZone($intl_timezone));
        $aedimage = array('id'=>0,
            'aed_id'=>'',
            'image_id'=>'',
            'image_date'=>$dt->format($php_date_format),
            'description'=>'',
        );
    }

    //
    // Get the details for an existing AED Image
    //
    else {
        $strsql = "SELECT ciniki_fatt_aed_images.id, "
            . "ciniki_fatt_aed_images.aed_id, "
            . "ciniki_fatt_aed_images.image_id, "
            . "IFNULL(DATE_FORMAT(ciniki_fatt_aed_images.image_date, '" . ciniki_core_dbQuote($ciniki, $mysql_date_format) . "'), '') AS image_date, "
            . "ciniki_fatt_aed_images.description "
            . "FROM ciniki_fatt_aed_images "
            . "WHERE ciniki_fatt_aed_images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_fatt_aed_images.id = '" . ciniki_core_dbQuote($ciniki, $args['aedimage_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'aedimage');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.50', 'msg'=>'AED Image not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['aedimage']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.51', 'msg'=>'Unable to find AED Image'));
        }
        $aedimage = $rc['aedimage'];
    }

    return array('stat'=>'ok', 'aedimage'=>$aedimage);
}
?>
