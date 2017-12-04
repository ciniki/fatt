<?php
//
// Description
// -----------
// This method returns the detail of attendance.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get certs for.
//
// Returns
// -------
//
function ciniki_fatt_reportPasses($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.reportPasses');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the passes by month by course
    //
    $strsql = "SELECT courses.id AS course_id, "
        . "courses.code, "
        . "courses.name, "
        . "YEAR(offerings.start_date) AS year, "
        . "MONTH(offerings.start_date) AS month, "
        . "IFNULL(COUNT(registrations.id), 0) AS num_passes "
        . "FROM ciniki_fatt_courses AS courses "
        . "LEFT JOIN ciniki_fatt_offerings AS offerings ON ("
            . "courses.id = offerings.course_id "
            . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_fatt_offering_registrations AS registrations ON ("
            . "offerings.id = registrations.offering_id "
            . "AND registrations.status = 10 "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "GROUP BY courses.id, year, month "
        . "ORDER BY courses.name, year, month ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'courses', 'fname'=>'course_id', 'fields'=>array('id'=>'course_id', 'code', 'name')),
        array('container'=>'years', 'fname'=>'year', 'fields'=>array()),
        array('container'=>'months', 'fname'=>'month', 'fields'=>array('num_passes')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $years = array();
    $num_passes = 0;
    $courses = array();
    foreach($rc['courses'] as $cid => $course) {
        $course['num_passes'] = 0;
        if( isset($course['years']) ) {
            foreach($course['years'] as $year => $y) {
                if( $year == 0 ) {
                    unset($rc['courses'][$cid]['years'][$year]);
                    continue;
                }
                if( !isset($years[$year]) ) {
                    $years[$year] = array('year'=>$year,
                        'months'=>array(
                            '1'=>0,
                            '2'=>0,
                            '3'=>0,
                            '4'=>0,
                            '5'=>0,
                            '6'=>0,
                            '7'=>0,
                            '8'=>0,
                            '9'=>0,
                            '10'=>0,
                            '11'=>0,
                            '12'=>0,
                            ),
                        'num_passes'=>0,
                        );
                }
                $course['years'][$year]['num_passes'] = 0;
                if( isset($y['months']) ) {
                    foreach($y['months'] as $mid => $m) {
                        $course['years'][$year]['num_passes'] += $m['num_passes'];
                        $years[$year]['months'][$mid] += $m['num_passes'];
                    }
                }
                $years[$year]['num_passes'] += $course['years'][$year]['num_passes'];
                $course['num_passes'] += $course['years'][$year]['num_passes'];
                $num_passes += $course['years'][$year]['num_passes'];
            }
        }
        if( $course['num_passes'] == 0 ) {
            continue;
        }
        $courses[] = $course;
    }

    return array('stat'=>'ok', 'courses'=>$courses, 'years'=>$years, 'num_passes'=>$num_passes);
}
?>
