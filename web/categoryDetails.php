<?php
//
// Description
// -----------
// This function will return the details for a course category
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get events for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_fatt_web_categoryDetails(&$ciniki, $settings, $tnid, $permalink) {
    
    if( !isset($ciniki['tenant']['modules']['ciniki.fatt']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.fatt.128', 'msg'=>"I'm sorry, the file you requested does not exist."));
    }

    //
    // Load the category
    //
    $strsql = "SELECT id, name, permalink, sequence, primary_image_id AS image_id, "
        . "synopsis, description "
        . "FROM ciniki_fatt_categories "
        . "WHERE ciniki_fatt_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_fatt_categories.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'category');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.fatt.129', 'msg'=>"I'm sorry, the file you requested does not exist.", 'err'=>$rc['err']));
    }
    if( !isset($rc['category']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.fatt.130', 'msg'=>"I'm sorry, the file you requested does not exist."));
    }

    return array('stat'=>'ok', 'category'=>$rc['category']);
}
?>
