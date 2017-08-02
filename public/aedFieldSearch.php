<?php
//
// Description
// -----------
// This method searchs for a AEDs for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get AED for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_fatt_aedFieldSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'field'=>array('required'=>'yes', 'blank'=>'no', 'validlist'=>array('make', 'model'), 'name'=>'Field'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Search String'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.aedFieldSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of aeds
    //
    if( $args['field'] == 'make' ) {
        $strsql = "SELECT DISTINCT "
            . "ciniki_fatt_aeds.make, "
            . "ciniki_fatt_aeds.model "
            . "FROM ciniki_fatt_aeds "
            . "WHERE ciniki_fatt_aeds.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ("
                . "make LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR make LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
            . "";
    } elseif( $args['field'] == 'model' ) {
        $strsql = "SELECT DISTINCT "
            . "'' AS make, "
            . "ciniki_fatt_aeds.model "
            . "FROM ciniki_fatt_aeds "
            . "WHERE ciniki_fatt_aeds.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ("
                . "model LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR model LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
            . "";
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.150', 'msg'=>'No search field specified'));
    }
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'result');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $aeds = $rc['rows'];
    } else {
        $aeds = array();
    }

    return array('stat'=>'ok', 'results'=>$aeds);
}
?>
