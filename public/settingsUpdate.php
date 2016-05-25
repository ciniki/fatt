<?php
//
// Description
// -----------
// This method will update one or more settings for the fatt module.
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_fatt_settingsUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.settingsUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Load timezone
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki, 'php');

	//
	// Grab the settings for the business from the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
	$rc = ciniki_core_dbDetailsQuery($ciniki, 'ciniki_fatt_settings', 'business_id', $args['business_id'], 'ciniki.fatt', 'settings', '');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$settings = $rc['settings'];

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.fatt');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   
    
    //
    // Check for date format
    //
    if( isset($ciniki['request']['args']['aeds-expirations-message-next']) && $ciniki['request']['args']['aeds-expirations-message-next'] != '' ) {
        $dt = new DateTime($ciniki['request']['args']['aeds-expirations-message-next'] . ' 06:00:00', new DateTimeZone($intl_timezone));    
        $dt->setTimezone(new DateTimeZone('UTC'));
        $ciniki['request']['args']['aeds-expirations-message-next'] = $dt->format('Y-m-d H:i:s');
    }

	//
	// The list of allowed fields for updating
	//
	$changelog_fields = array(
		'default-header-image',
		'default-header-contact-position',
		'default-header-business-name',
		'default-header-address',
		'default-header-phone',
		'default-header-cell',
		'default-header-fax',
		'default-header-email',
		'default-header-website',
        'aeds-expirations-message-enabled',
        'aeds-expirations-message-next',
		);
	//
	// Check each valid setting and see if a new value was passed in the arguments for it.
	// Insert or update the entry in the ciniki_fatt_settings table
	//
	foreach($changelog_fields as $field) {
		if( isset($ciniki['request']['args'][$field]) 
			&& (!isset($settings[$field]) || $ciniki['request']['args'][$field] != $settings[$field]) ) {
			$strsql = "INSERT INTO ciniki_fatt_settings (business_id, detail_key, detail_value, date_added, last_updated) "
				. "VALUES ('" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args']['business_id']) . "'"
				. ", '" . ciniki_core_dbQuote($ciniki, $field) . "'"
				. ", '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$field]) . "'"
				. ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
				. "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$field]) . "' "
				. ", last_updated = UTC_TIMESTAMP() "
				. "";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.fatt');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
				return $rc;
			}
			ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.fatt', 'ciniki_fatt_history', $args['business_id'], 
				2, 'ciniki_fatt_settings', $field, 'detail_value', $ciniki['request']['args'][$field]);
			$ciniki['syncqueue'][] = array('push'=>'ciniki.fatt.setting', 
				'args'=>array('id'=>$field));
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.fatt');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'fatt');

	return array('stat'=>'ok');
}
?>
