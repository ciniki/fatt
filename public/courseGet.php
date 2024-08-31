<?php
//
// Description
// ===========
// This method will return all the information about a course.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the course is attached to.
// course_id:       The ID of the course to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_courseGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'course_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Course'), 
        'messages'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Messages'), 
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.courseGet'); 
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

    if( $args['course_id'] == 0 ) {
        $strsql = "SELECT MAX(sequence) AS sequence "
            . "FROM ciniki_fatt_courses "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'max');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['max']['sequence']) ) {
            $sequence = $rc['max']['sequence'] + 1;
        } else {
            $sequence = 1;
        }
        $rsp = array('stat'=>'ok', 'course'=>array(
            'name'=>'',
            'status'=>'10',
            'primary_image_id'=>'0',
            'flags'=>'0',
            'sequence'=>$sequence,
            'cover_letter'=>'',
            'cert_form1'=>'',
            'cert_form2'=>'',
            'welcome_msg'=>'',
            ));
    } else {
        //
        // Get the course details
        //
        $strsql = "SELECT ciniki_fatt_courses.id, "
            . "ciniki_fatt_courses.name, "
            . "ciniki_fatt_courses.code, "
            . "ciniki_fatt_courses.permalink, "
            . "ciniki_fatt_courses.sequence, "
            . "ciniki_fatt_courses.status, "
            . "ciniki_fatt_courses.primary_image_id, "
            . "ciniki_fatt_courses.synopsis, "
            . "ciniki_fatt_courses.description, "
            . "ciniki_fatt_courses.price, "
            . "ciniki_fatt_courses.taxtype_id, "
            . "ciniki_fatt_courses.num_days, "
            . "ciniki_fatt_courses.num_hours, "
            . "ciniki_fatt_courses.num_seats_per_instructor, "
            . "ciniki_fatt_courses.flags, "
            . "ciniki_fatt_courses.cover_letter, "
            . "ciniki_fatt_courses.cert_form1, "
            . "ciniki_fatt_courses.cert_form2, "
            . "ciniki_fatt_courses.welcome_msg "
            . "FROM ciniki_fatt_courses "
            . "WHERE ciniki_fatt_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_fatt_courses.id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'courses', 'fname'=>'id', 'name'=>'course',
                'fields'=>array('id', 'name', 'code', 'permalink', 'sequence', 'status', 'primary_image_id', 'synopsis', 'description', 
                    'price', 'taxtype_id', 'num_days', 'num_hours', 'num_seats_per_instructor', 'flags', 
                    'cover_letter', 'cert_form1', 'cert_form2', 'welcome_msg')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['courses']) || !isset($rc['courses'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.85', 'msg'=>'Unable to find course'));
        }
        $rsp = array('stat'=>'ok', 'course'=>$rc['courses'][0]['course']);
        $rsp['course']['price'] = numfmt_format_currency($intl_currency_fmt, $rsp['course']['price'], $intl_currency);
        $rsp['course']['num_hours'] = (float)$rsp['course']['num_hours'];
    }

    //
    // Get the categories for the course and the tenant
    //
    if( ($ciniki['tenant']['modules']['ciniki.fatt']['flags']&0x02) > 0 ) {
        $rsp['course']['categories'] = '';
        $strsql = "SELECT ciniki_fatt_categories.id, "
            . "ciniki_fatt_categories.name, "
            . "ciniki_fatt_categories.sequence, "
            . "IFNULL(ciniki_fatt_course_categories.id, 0) AS link_id "
            . "FROM ciniki_fatt_categories "
            . "LEFT JOIN ciniki_fatt_course_categories ON ("
                . "ciniki_fatt_categories.id = ciniki_fatt_course_categories.category_id "
                . "AND ciniki_fatt_course_categories.course_id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
                . "AND ciniki_fatt_course_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_fatt_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY ciniki_fatt_categories.sequence, ciniki_fatt_categories.name "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'categories', 'fname'=>'id', 'name'=>'item',
                'fields'=>array('id', 'name', 'sequence', 'link_id')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['categories'] = array();
        if( isset($rc['categories']) ) {
            $rsp['categories'] = $rc['categories'];
            foreach($rsp['categories'] as $cid => $item) {
                if( $item['item']['link_id'] > 0 ) {
                    $rsp['course']['categories'] .= ($rsp['course']['categories']!=''?',':'') . $item['item']['id'];
                }
                unset($rsp['categories'][$cid]['item']['link_id']);
            }
        }
    }

    //
    // Get the bundles for the course and the tenant
    //
    if( ($ciniki['tenant']['modules']['ciniki.fatt']['flags']&0x02) > 0 ) {
        $rsp['course']['bundles'] = '';
        $strsql = "SELECT ciniki_fatt_bundles.id, "
            . "ciniki_fatt_bundles.name, "
            . "IFNULL(ciniki_fatt_course_bundles.id, 0) AS link_id "
            . "FROM ciniki_fatt_bundles "
            . "LEFT JOIN ciniki_fatt_course_bundles ON ("
                . "ciniki_fatt_bundles.id = ciniki_fatt_course_bundles.bundle_id "
                . "AND ciniki_fatt_course_bundles.course_id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
                . "AND ciniki_fatt_course_bundles.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_fatt_bundles.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY ciniki_fatt_bundles.name "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'bundles', 'fname'=>'id', 'name'=>'item',
                'fields'=>array('id', 'name', 'link_id')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['bundles'] = array();
        if( isset($rc['bundles']) ) {
            $rsp['bundles'] = $rc['bundles'];
            foreach($rsp['bundles'] as $cid => $item) {
                if( $item['item']['link_id'] > 0 ) {
                    $rsp['course']['bundles'] .= ($rsp['course']['bundles']!=''?',':'') . $item['item']['id'];
                }
                unset($rsp['bundles'][$cid]['item']['link_id']);
            }
        }
    }

    //
    // Get the certs for the course and the tenant
    //
    if( ($ciniki['tenant']['modules']['ciniki.fatt']['flags']&0x10) > 0 ) {
        $rsp['course']['certs'] = '';
        $strsql = "SELECT ciniki_fatt_certs.id, "
            . "ciniki_fatt_certs.name, "
            . "IFNULL(ciniki_fatt_course_certs.id, 0) AS link_id "
            . "FROM ciniki_fatt_certs "
            . "LEFT JOIN ciniki_fatt_course_certs ON ("
                . "ciniki_fatt_certs.id = ciniki_fatt_course_certs.cert_id "
                . "AND ciniki_fatt_course_certs.course_id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
                . "AND ciniki_fatt_course_certs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_fatt_certs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY ciniki_fatt_certs.name "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'certs', 'fname'=>'id', 'name'=>'item',
                'fields'=>array('id', 'name', 'link_id')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['certs'] = array();
        if( isset($rc['certs']) ) {
            $rsp['certs'] = $rc['certs'];
            foreach($rsp['certs'] as $cid => $item) {
                if( $item['item']['link_id'] > 0 ) {
                    $rsp['course']['certs'] .= ($rsp['course']['certs']!=''?',':'') . $item['item']['id'];
                }
                unset($rsp['certs'][$cid]['item']['link_id']);
            }
        }
    }

    //
    // Get any messages about the course
    //
    if( isset($args['messages']) && $args['messages'] == 'yes' 
        && ($ciniki['tenant']['modules']['ciniki.fatt']['flags']&0x08) > 0 
        ) {
        $strsql = "SELECT id, days, subject, message "
            . "FROM ciniki_fatt_messages "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND object = 'ciniki.fatt.course' "
            . "AND object_id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
            . "ORDER BY days "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'messages', 'fname'=>'id', 'name'=>'message',
                'fields'=>array('id', 'days', 'subject', 'message')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['messages']) ) {
            $rsp['course']['messages'] = $rc['messages'];
        } else {
            $rsp['course']['messages'] = array();
        }
    }

    //
    // Get the list of available forms
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'forms', 'list');
    $rc = ciniki_fatt_forms_list($ciniki, $args['tnid'], array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['forms'] = array_values($rc['forms']);
    $rsp['cover_letters'] = array_values($rc['cover_letters']);

    return $rsp;
}
?>
