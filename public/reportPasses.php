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
// business_id:     The ID of the business to get certs for.
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
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.reportPasses');
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
            . "AND offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_fatt_offering_registrations AS registrations ON ("
            . "offerings.id = registrations.offering_id "
            . "AND registrations.status = 10 "
            . "AND registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE courses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
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
    foreach($rc['courses'] as $cid => $course) {
        $rc['courses'][$cid]['num_passes'] = 0;
        if( isset($course['years']) ) {
            foreach($course['years'] as $year => $y) {
                if( $year == 0 ) {
                    unset($rc['courses'][$cid]['years'][$year]);
                    continue;
                }
                if( !in_array($year, $years) ) {
                    $years[] = $year;
                }
                $rc['courses'][$cid]['years'][$year]['num_passes'] = 0;
                if( isset($y['months']) ) {
                    foreach($y['months'] as $mid => $m) {
                        $rc['courses'][$cid]['years'][$year]['num_passes'] += $m['num_passes'];
                    }
                }
                $rc['courses'][$cid]['num_passes'] += $rc['courses'][$cid]['years'][$year]['num_passes'];
            }
        }
        if( $rc['courses'][$cid]['num_passes'] == 0 ) {
            unset($rc['courses'][$cid]);
        }
    }
    rsort($years);

    return array('stat'=>'ok', 'courses'=>$rc['courses'], 'years'=>$years);
}
?>
