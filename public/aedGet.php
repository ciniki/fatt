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
// tnid:         The ID of the tenant the aed is attached to.
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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'aed_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'AED'),
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.aedGet');
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
            'flags'=>0x11,
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
            . "WHERE ciniki_fatt_aeds.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_fatt_aeds.id = '" . ciniki_core_dbQuote($ciniki, $args['aed_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'aed');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.47', 'msg'=>'AED not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['aed']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.48', 'msg'=>'Unable to find AED'));
        }
        $aed = $rc['aed'];

        //
        // Get the images
        //
        $strsql = "SELECT id, image_id, "
            . "IFNULL(DATE_FORMAT(image_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS name, "
            . "description "
            . "FROM ciniki_fatt_aed_images "
            . "WHERE aed_id = '" . ciniki_core_dbQuote($ciniki, $args['aed_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY image_date DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'images', 'fname'=>'id', 'fields'=>array('id', 'image_id', 'name', 'description')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['images']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
            $aed['images'] = $rc['images'];
            foreach($aed['images'] as $img_id => $img) {
                if( isset($img['image_id']) && $img['image_id'] > 0 ) {
                    $rc = ciniki_images_loadCacheThumbnail($ciniki, $args['tnid'], $img['image_id'], 75);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $aed['images'][$img_id]['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                }
            }
        } else {
            $aed['images'] = array();
        }

        //
        // Get the list of notes
        //
        $strsql = "SELECT ciniki_fatt_aed_notes.id, "
            . "ciniki_fatt_aed_notes.aed_id, "
            . "DATE_FORMAT(ciniki_fatt_aed_notes.note_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') as note_date, "
            . "ciniki_fatt_aed_notes.content "
            . "FROM ciniki_fatt_aed_notes "
            . "WHERE aed_id = '" . ciniki_core_dbQuote($ciniki, $args['aed_id']) . "' "
            . "AND ciniki_fatt_aed_notes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY ciniki_fatt_aed_notes.note_date DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'notes', 'fname'=>'id', 'fields'=>array('id', 'aed_id', 'note_date', 'content')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['notes']) ) {
            $aed['notes'] = $rc['notes'];
        } else {
            $aed['notes'] = array();
        }
    }

    if( $aed['customer_id'] > 0 ) {
        //
        // Get the customer details
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
        $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['tnid'], array('customer_id'=>$aed['customer_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
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
