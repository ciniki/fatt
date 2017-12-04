<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_fatt_hooks_uiSettings($ciniki, $tnid, $args) {

    $settings = array();

    //
    // Get the time information for tenant and user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Load the courses and instructors for the tenant
    //
    if( ($ciniki['tenant']['modules']['ciniki.fatt']['flags']&0x01) > 0 ) {
        $strsql = "SELECT id, name, price, num_days "
            . "FROM ciniki_fatt_courses "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND status = 10 "
            . "ORDER BY name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'courses', 'fname'=>'id', 'fields'=>array('id', 'name', 'price', 'num_days')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['courses']) ) {
            $settings['courses'] = $rc['courses'];
            foreach($settings['courses'] as $cid => $course) {
                $settings['courses'][$cid]['price'] = numfmt_format_currency($intl_currency_fmt, 
                    $settings['courses'][$cid]['price'], $intl_currency);
//              $settings['courses'][$cid]['course']['price'] = numfmt_format_currency($intl_currency_fmt, 
//                  $settings['courses'][$cid]['course']['price'], $intl_currency);
            }
        }
        if( ($ciniki['tenant']['modules']['ciniki.fatt']['flags']&0x40) > 0 ) {
            $strsql = "SELECT ciniki_fatt_bundles.id, ciniki_fatt_bundles.name, MAX(num_days) as num_days "
                . "FROM ciniki_fatt_bundles, ciniki_fatt_course_bundles, ciniki_fatt_courses "
                . "WHERE ciniki_fatt_bundles.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND ciniki_fatt_bundles.id = ciniki_fatt_course_bundles.bundle_id "
                . "AND ciniki_fatt_course_bundles.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND ciniki_fatt_course_bundles.course_id = ciniki_fatt_courses.id "
                . "AND ciniki_fatt_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND ciniki_fatt_courses.status = 10 "
                . "GROUP BY ciniki_fatt_bundles.id "
                . "ORDER BY ciniki_fatt_bundles.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
            $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
                array('container'=>'bundles', 'fname'=>'id', 'name'=>'bundle',
                    'fields'=>array('id', 'name', 'num_days')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['bundles']) ) {
                $settings['bundles'] = $rc['bundles'];
            }
        }


        $strsql = "SELECT id, name "
            . "FROM ciniki_fatt_instructors "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND status = 10 "
            . "ORDER BY name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'instructors', 'fname'=>'id', 'name'=>'instructor',
                'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['instructors']) ) {
            $settings['instructors'] = $rc['instructors'];
        }
    }

    //
    // Load the fatt locations for the tenant
    //
    if( ($ciniki['tenant']['modules']['ciniki.fatt']['flags']&0x04) > 0 ) {
        $strsql = "SELECT id, name, flags, colour "
            . "FROM ciniki_fatt_locations "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND status = 10 "
            . "ORDER BY name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'locations', 'fname'=>'id', 'name'=>'location',
                'fields'=>array('id', 'name', 'flags', 'colour')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['locations']) ) {
            $settings['locations'] = $rc['locations'];
        }
    }

    //
    // Load the certs for the tenant
    //
    if( ($ciniki['tenant']['modules']['ciniki.fatt']['flags']&0x10) > 0 ) {
        $strsql = "SELECT id, name "
            . "FROM ciniki_fatt_certs "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND status = 10 "
            . "ORDER BY name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'certs', 'fname'=>'id', 'name'=>'cert',
                'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['certs']) ) {
            $settings['certs'] = $rc['certs'];
        }
    }

    $rsp = array('stat'=>'ok', 'settings'=>$settings, 'menu_items'=>array(), 'settings_menu_items'=>array());  

    //
    // Certifications menu item
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.fatt', 0x10)
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>5503,
            'label'=>'Reports', 
            'edit'=>array('app'=>'ciniki.fatt.reports'),
            );
        $rsp['menu_items'][] = $menu_item;
    } 

    //
    // Course Offerings
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.fatt', 0x01)
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>5502,
            'label'=>'Courses', 
            'edit'=>array('app'=>'ciniki.fatt.offerings'),
            );
        $rsp['menu_items'][] = $menu_item;
    } 

    //
    // AEDs
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.fatt', 0x0100)
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>5501,
            'label'=>'AEDs', 
            'edit'=>array('app'=>'ciniki.fatt.aeds'),
            );
        $rsp['menu_items'][] = $menu_item;
    } 

    if( isset($ciniki['tenant']['modules']['ciniki.fatt']) 
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $rsp['settings_menu_items'][] = array('priority'=>5500, 'label'=>'First Aid', 'edit'=>array('app'=>'ciniki.fatt.settings'));
    }

    return $rsp;
}
?>
