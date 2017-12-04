<?php
//
// Description
// -----------
// This function will return the list of courses.
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
function ciniki_fatt_web_courses(&$ciniki, $settings, $tnid, $args) {
    
    if( !isset($ciniki['tenant']['modules']['ciniki.fatt']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.fatt.133', 'msg'=>"I'm sorry, the file you requested does not exist."));
    }

    if( isset($args['category_id']) && $args['category_id'] > 0 ) {
        $strsql = "SELECT ciniki_fatt_courses.id, "
            . "ciniki_fatt_courses.name AS title, "
            . "ciniki_fatt_courses.code, "
            . "ciniki_fatt_courses.permalink, "
            . "ciniki_fatt_courses.sequence, "
            . "ciniki_fatt_courses.status, "
            . "ciniki_fatt_courses.primary_image_id AS image_id, "
            . "ciniki_fatt_courses.synopsis, "
            . "ciniki_fatt_courses.description, "
            . "'yes' AS is_details "
            . "FROM ciniki_fatt_course_categories, ciniki_fatt_courses "
            . "WHERE ciniki_fatt_course_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_fatt_course_categories.category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "AND ciniki_fatt_course_categories.course_id = ciniki_fatt_courses.id "
            . "AND ciniki_fatt_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (ciniki_fatt_courses.flags&0x01) = 0x01 "
            . "ORDER BY ciniki_fatt_courses.sequence, ciniki_fatt_courses.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'courses', 'fname'=>'id',
                'fields'=>array('id', 'title', 'permalink', 'image_id', 'synopsis', 'is_details')),
            ));
        return $rc;
    } 

    if( ($ciniki['tenant']['modules']['ciniki.fatt']['flags']&0x02) == 0x02 ) {
        $strsql = "SELECT ciniki_fatt_courses.id, "
            . "ciniki_fatt_courses.name AS title, "
            . "ciniki_fatt_courses.code, "
            . "CONCAT_WS('/', ciniki_fatt_categories.permalink, ciniki_fatt_courses.permalink) AS permalink, "
            . "ciniki_fatt_courses.status, "
            . "ciniki_fatt_courses.primary_image_id AS image_id, "
            . "ciniki_fatt_courses.synopsis, "
            . "ciniki_fatt_courses.description, "
            . "ciniki_fatt_categories.id AS category_id, "
            . "ciniki_fatt_categories.name AS category_name, "
            . "'yes' AS is_details "
            . "FROM ciniki_fatt_courses "
            . "LEFT JOIN ciniki_fatt_course_categories ON ("
                . "ciniki_fatt_courses.id = ciniki_fatt_course_categories.course_id "
                . "AND ciniki_fatt_course_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_fatt_categories ON ("
                . "ciniki_fatt_course_categories.category_id = ciniki_fatt_categories.id "
                . "AND ciniki_fatt_course_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_fatt_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (ciniki_fatt_courses.flags&0x01) = 0x01 "
            . "ORDER BY ciniki_fatt_course_categories.id, ciniki_fatt_courses.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'categories', 'fname'=>'category_id',
                'fields'=>array('name'=>'category_name')),
            array('container'=>'list', 'fname'=>'id',
                'fields'=>array('id', 'title', 'permalink', 'image_id', 'synopsis', 'is_details')),
            ));
        return $rc;
    }

    $strsql = "SELECT ciniki_fatt_courses.id, "
        . "ciniki_fatt_courses.name AS title, "
        . "ciniki_fatt_courses.code, "
        . "ciniki_fatt_courses.permalink, "
        . "ciniki_fatt_courses.status, "
        . "ciniki_fatt_courses.primary_image_id AS image_id, "
        . "ciniki_fatt_courses.synopsis, "
        . "ciniki_fatt_courses.description, "
        . "'yes' AS is_details "
        . "FROM ciniki_fatt_courses "
        . "WHERE ciniki_fatt_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY ciniki_fatt_courses.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'courses', 'fname'=>'id',
            'fields'=>array('id', 'title', 'permalink', 'image_id', 'synopsis', 'is_details')),
        ));
    return $rc;
}
?>
