<?php
//
// Description
// -----------
// This function will return the form information, including questions and how it maps to the form.
//
// Arguments
// ---------
// ciniki:
//
// Returns
// -------
//
function ciniki_fatt_forms_list($ciniki, $business_id, $args) {
    
    $forms = array(
        'CA-ON-LSS-CPR-A'=>array('id'=>'CA-ON-LSS-CPR-A', 'name'=>'Ontario LSS - CPR-A', 
            'processor'=>'CAONLSSCPR', 
            'options'=>array('level'=>'A'),
            ),
        'CA-ON-LSS-CPR-B'=>array('id'=>'CA-ON-LSS-CPR-B', 'name'=>'Ontario LSS - CPR-B', 
            'processor'=>'CAONLSSCPR', 
            'options'=>array('level'=>'B'),
            ),
        'CA-ON-LSS-CPR-C'=>array('id'=>'CA-ON-LSS-CPR-C', 'name'=>'Ontario LSS - CPR-C', 
            'processor'=>'CAONLSSCPR', 
            'options'=>array('level'=>'C'),
            ),
        'CA-ON-LSS-EFA'=>array('id'=>'CA-ON-LSS-EFA', 'name'=>'Ontario LSS - EFA', 
            'processor'=>'CAONLSSEFA', 
            'options'=>array('recert'=>''),
            ),
        'CA-ON-LSS-EFA-R'=>array('id'=>'CA-ON-LSS-EFA-R', 'name'=>'Ontario LSS - EFA - Renewal', 
            'processor'=>'CAONLSSEFA', 
            'options'=>array('recert'=>'yes'),
            ),
        'CA-ON-LSS-SFA'=>array('id'=>'CA-ON-LSS-SFA', 'name'=>'Ontario LSS - SFA', 
            'processor'=>'CAONLSSSFA', 
            'options'=>array('recert'=>''),
            ),
        'CA-ON-LSS-SFA-R'=>array('id'=>'CA-ON-LSS-SFA-R', 'name'=>'Ontario LSS - SFA - Renewal', 
            'processor'=>'CAONLSSSFA', 
            'options'=>array('recert'=>'yes'),
            ), 
        'CA-ON-LSS-HCP'=>array('id'=>'CA-ON-LSS-HCP', 'name'=>'Ontario LSS - HCP', 
            'processor'=>'CAONLSSHCPAED', 
            'options'=>array('recert'=>'no', 'exam'=>'hcp'),
            ), 
        'CA-ON-LSS-HCP-R'=>array('id'=>'CA-ON-LSS-HCP-R', 'name'=>'Ontario LSS - HCP - Renewal', 
            'processor'=>'CAONLSSHCPAED', 
            'options'=>array('recert'=>'yes', 'exam'=>'hcp'),
            ), 
        );
    
    return array('stat'=>'ok', 'forms'=>$forms);
}
?>
