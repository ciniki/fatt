<?php
//
// Description
// -----------
// This function will return the menu items for the main menu.
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
function ciniki_fatt_web_menuItems(&$ciniki, $settings, $tnid, $args) {
    
    if( !isset($ciniki['tenant']['modules']['ciniki.fatt']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.fatt.134', 'msg'=>"I'm sorry, the file you requested does not exist."));
    }

    //
    // The menu 
    //
    $menu = array();
    if( isset($settings['page-fatt-menu-categories']) && $settings['page-fatt-menu-categories'] == 'yes' ) {
        $strsql = "SELECT id, name, permalink, primary_image_id, synopsis, description "
            . "FROM ciniki_fatt_categories "
            . "WHERE ciniki_fatt_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY sequence ";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'category');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $categories = $rc['rows'];
        foreach($rc['rows'] as $category) {
            $menu[$category['permalink']] = array('title'=>$category['name'], 'permalink'=>'firstaid/' . $category['permalink']);
            if( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'firstaid' 
                && isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] == $category['permalink']
                ) {
                $menu[$category['permalink']]['selected'] = 'yes';
            }
        }
    } else {
        if( isset($settings['page-fatt-name']) && $settings['page-fatt-name'] != '' ) {
            $menu['courses'] = array('title'=>$settings['page-fatt-name'], 'permalink'=>'firstaid');
        } else {
            $menu['courses'] = array('title'=>'Courses', 'permalink'=>'firstaid');
        }
        if( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'firstaid' ) {
            $menu['courses']['selected'] = 'yes';
        }
    }

    return array('stat'=>'ok', 'menu'=>$menu);
}
?>
