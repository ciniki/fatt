<?php
//
// Description
// -----------
// This method will return the list of courses for a tenant.  
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get courses for.
//
// Returns
// -------
//
function ciniki_fatt_courseList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'), 
        'bundles'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Bundles'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.courseList');
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
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    numfmt_set_attribute($intl_currency_fmt, NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Load fatt maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'maps');
    $rc = ciniki_fatt_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the list of courses
    //
    $strsql = "SELECT ciniki_fatt_courses.id, "
        . "ciniki_fatt_courses.name, "
        . "ciniki_fatt_courses.code, "
        . "ciniki_fatt_courses.sequence, "
        . "ciniki_fatt_courses.price, "
        . "ciniki_fatt_courses.num_days, "
        . "ciniki_fatt_courses.num_hours, "
        . "ciniki_fatt_courses.num_seats_per_instructor, "
        . "ciniki_fatt_courses.status AS status_text, "
        . "IF((ciniki_fatt_courses.flags&0x01)=1,'Visible','Hidden') AS visible, "
        . "ciniki_tax_types.name AS taxtype_name "
        . "FROM ciniki_fatt_courses "
        . "LEFT JOIN ciniki_tax_types ON ("
            . "ciniki_fatt_courses.taxtype_id = ciniki_tax_types.id "
            . "AND ciniki_tax_types.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_fatt_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY ciniki_fatt_courses.sequence, ciniki_fatt_courses.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'courses', 'fname'=>'id', 'name'=>'course',
            'fields'=>array('id', 'name', 'code', 'price', 'num_days', 'num_hours', 'num_seats_per_instructor', 'status_text', 'taxtype_name', 'visible'),
            'maps'=>array('status_text'=>$maps['course']['status'])),
        ));
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $rsp = array('stat'=>'ok', 'courses'=>array());
    if( isset($rc['courses']) ) {
        $rsp['courses'] = $rc['courses'];
        foreach($rsp['courses'] as $cid => $course) {
            $rsp['courses'][$cid]['course']['price'] = numfmt_format_currency($intl_currency_fmt, $course['course']['price'], $intl_currency);
            $rsp['courses'][$cid]['course']['num_hours'] = (float)$course['course']['num_hours'];
        }
    }

    //
    // Check if we should return the categories as well
    //
    if( isset($args['categories']) && $args['categories'] == 'yes' 
        && isset($ciniki['tenant']['modules']['ciniki.fatt']['flags'])
        && ($ciniki['tenant']['modules']['ciniki.fatt']['flags']&0x02) > 0 
        ) {
        $strsql = "SELECT ciniki_fatt_categories.id, "
            . "ciniki_fatt_categories.name, "
            . "ciniki_fatt_categories.permalink "
            . "FROM ciniki_fatt_categories "
            . "WHERE ciniki_fatt_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'categories', 'fname'=>'id', 'name'=>'category',
                'fields'=>array('id', 'name', 'permalink')),
            ));
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( isset($rc['categories']) ) {
            $rsp['categories'] = $rc['categories'];
        }
    }

    //
    // Check if we should return the bundles as well
    //
    if( isset($args['bundles']) && $args['bundles'] == 'yes' 
        && isset($ciniki['tenant']['modules']['ciniki.fatt']['flags'])
        && ($ciniki['tenant']['modules']['ciniki.fatt']['flags']&0x02) > 0 
        ) {
        $strsql = "SELECT ciniki_fatt_bundles.id, "
            . "ciniki_fatt_bundles.name "
            . "FROM ciniki_fatt_bundles "
            . "WHERE ciniki_fatt_bundles.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'bundles', 'fname'=>'id', 'name'=>'bundle',
                'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( isset($rc['bundles']) ) {
            $rsp['bundles'] = $rc['bundles'];
        }
    }

    return $rsp;
}
?>
