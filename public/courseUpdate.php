<?php
//
// Description
// ===========
// This method will update a course in the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the course is attached to.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_fatt_courseUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'course_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Course'), 
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'), 
        'code'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Code'), 
        'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'), 
        'sequence'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Sequence'), 
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'), 
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Primary Image'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'), 
        'price'=>array('required'=>'no', 'blank'=>'no', 'type'=>'currency', 'name'=>'Price'), 
        'taxtype_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Taxes'), 
        'num_days'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Number of Days'), 
        'num_hours'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Number of Hours'), 
        'num_seats_per_instructor'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Number of seats per instructor'), 
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Flags'), 
        'cover_letter'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Cover Letter'), 
        'cert_form1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'1st Certification Form'), 
        'cert_form2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'2nd Certification Form'), 
        'welcome_msg'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Welcome Message Details'), 
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Categories'), 
        'bundles'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Bundles'), 
        'certs'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Certifications'), 
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.courseUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Check for new permalink
    //
    if( isset($args['name']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, name, permalink "
            . "FROM ciniki_fatt_courses "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'course');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.86', 'msg'=>'You already have a course with this name, please choose another name'));
        }
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.fatt');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Update the course in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.fatt.course', $args['course_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
    }

    //
    // Update the categories
    //
    if( isset($args['categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'courseUpdateCategories');
        $rc = ciniki_fatt_courseUpdateCategories($ciniki, $args['tnid'], $args['course_id'], $args['categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
            return $rc;
        }
    }

    //
    // Update the bundles
    //
    if( isset($args['bundles']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'courseUpdateBundles');
        $rc = ciniki_fatt_courseUpdateBundles($ciniki, $args['tnid'], $args['course_id'], $args['bundles']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
            return $rc;
        }
    }

    //
    // Update the certs
    //
    if( isset($args['certs']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'courseUpdateCerts');
        $rc = ciniki_fatt_courseUpdateCerts($ciniki, $args['tnid'], $args['course_id'], $args['certs']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
            return $rc;
        }
    }
    
    //
    // Update the future courses is
    //
/*    if( isset($args['price']) ) {
        $strsql = "SELECT id, price "
            . "FROM ciniki_fatt_offerings "
            . "WHERE course_id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
            . "AND start_date > UTC_TIMESTAMP() "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'offering');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
            return $rc;
        }
        if( isset($rc['rows']) ) {
            $offerings = $rc['rows'];
            foreach($offerings as $offering) {
                if( $offering['price'] != $args['price'] ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.fatt.offering', $offering['id'], array('price'=>$args['price']), 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                        return $rc;
                    }
                }
            }
        }
    } */

    //
    // Update the length of the course offerings
    //
    if( isset($args['num_hours']) ) {
        $strsql = "SELECT dates.id, dates.num_hours, dates.day_number "
            . "FROM ciniki_fatt_offerings AS offerings, ciniki_fatt_offering_dates AS dates "
            . "WHERE offerings.course_id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
            . "AND offerings.start_date > UTC_TIMESTAMP() "
            . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND offerings.id = dates.offering_id "
            . "AND dates.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'offering');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
            return $rc;
        }
        if( isset($rc['rows']) ) {
            $dates = $rc['rows'];
            foreach($dates as $odate) {
                if( $args['num_hours'] <= 8 ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.fatt.offeringdate', $odate['id'], array('num_hours'=>$args['num_hours']), 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                        return $rc;
                    }

                } elseif( $args['num_hours'] > 8 ) {
                    $day1_hours = min($args['num_hours'], 7);
                    $day2_hours = $args['num_hours'] - 7;
                    if( $odate['day_number'] == 1 && $odate['num_hours'] != $day1_hours ) {
                        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.fatt.offeringdate', $odate['id'], array('num_hours'=>$day1_hours), 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                            return $rc;
                        }
                    } elseif( $odate['day_number'] == 2 && $odate['num_hours'] != $day2_hours ) {
                        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.fatt.offeringdate', $odate['id'], array('num_hours'=>$day2_hours), 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                            return $rc;
                        }
                    }
                }
            }
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.fatt');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'fatt');

    return array('stat'=>'ok');
}
?>
