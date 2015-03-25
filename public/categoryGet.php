<?php
//
// Description
// ===========
// This method will return all the information about a category.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the category is attached to.
// category_id:		The ID of the category to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_categoryGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'category_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Course'), 
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.categoryGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	if( $args['category_id'] == 0 ) {
		$strsql = "SELECT MAX(sequence) AS max_sequence "
			. "FROM ciniki_fatt_categories "
			. "WHERE ciniki_fatt_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'max');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['max']['max_sequence']) ) {
			$seq = $rc['max']['max_sequence'] + 1;
		} else {
			$seq = 1;
		}
		return array('stat'=>'ok', 'category'=>array(
			'name'=>'',
			'sequence'=>$seq,
			'primary_image_id'=>'0',
			));
	}

	//
	// Get the category details
	//
	$strsql = "SELECT ciniki_fatt_categories.id, "
		. "ciniki_fatt_categories.name, "
		. "ciniki_fatt_categories.permalink, "
		. "ciniki_fatt_categories.sequence, "
		. "ciniki_fatt_categories.primary_image_id, "
		. "ciniki_fatt_categories.synopsis, "
		. "ciniki_fatt_categories.description "
		. "FROM ciniki_fatt_categories "
		. "WHERE ciniki_fatt_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_fatt_categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
		array('container'=>'categories', 'fname'=>'id', 'name'=>'category',
			'fields'=>array('id', 'name', 'permalink', 'sequence', 'primary_image_id', 'synopsis', 'description')),
	));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['categories']) || !isset($rc['categories'][0]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2293', 'msg'=>'Unable to find category'));
	}
	$category = $rc['categories'][0]['category'];

	return array('stat'=>'ok', 'category'=>$category);
}
?>
