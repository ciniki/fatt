<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_fatt_flags($ciniki, $modules) {
    $flags = array(
        // 0x01
        array('flag'=>array('bit'=>'1', 'name'=>'Courses')),
        array('flag'=>array('bit'=>'2', 'name'=>'Categories')),
        array('flag'=>array('bit'=>'3', 'name'=>'Locations')),
        array('flag'=>array('bit'=>'4', 'name'=>'Course Messages')),
        // 0x10
        array('flag'=>array('bit'=>'5', 'name'=>'Certs')),
        array('flag'=>array('bit'=>'6', 'name'=>'Cert Messages')),
        array('flag'=>array('bit'=>'7', 'name'=>'Bundles')),
//      array('flag'=>array('bit'=>'8', 'name'=>'')),
        // 0x0100
        array('flag'=>array('bit'=>'9', 'name'=>'Devices')),
//      array('flag'=>array('bit'=>'10', 'name'=>'Device Messages')),
//      array('flag'=>array('bit'=>'11', 'name'=>'')),
//      array('flag'=>array('bit'=>'12', 'name'=>'')),
        // 0x1000
//      array('flag'=>array('bit'=>'13', 'name'=>'')),
//      array('flag'=>array('bit'=>'14', 'name'=>'')),
//      array('flag'=>array('bit'=>'15', 'name'=>'')),
//      array('flag'=>array('bit'=>'16', 'name'=>'')),
        );

    return array('stat'=>'ok', 'flags'=>$flags);
}
?>
