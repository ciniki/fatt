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
function ciniki_fatt_forms_processCAONLSSCPR($ciniki, $tnid, &$pdf, $form) {

    $reg_number = 0;
    while($reg_number < count($form['registrations']) ) {
        $pdf->AddPage();
        $pdf->SetCellPaddings(1, 0, 1, 0);
        $pdf->Image($ciniki['config']['core']['modules_dir'] . '/fatt/forms/bgCAONLSSCPR.png', 0, 0, 216, 279, '', '', '', false, 300, '', false, false, 0);
        $pdf->setFont('', '', 22);
        if( $form['options']['level'] == 'A' ) {
            $pdf->Text(26.25, 41.4, "•");
        } elseif( $form['options']['level'] == 'B' ) {
            $pdf->Text(43, 41.4, "•");
        } elseif( $form['options']['level'] == 'C' ) {
            $pdf->Text(59.25, 41.4, "•");
        }
        $pdf->setFont('', '', 12);

        //
        // Setup the registration information
        //
        $y = 62.5;
        $num_page_reg = 0;
        while($reg_number < count($form['registrations']) && $num_page_reg < 15) {
            $reg = $form['registrations'][$reg_number];
            $pdf->Text(15, $y, $reg['display_name']);
            $pdf->Text(86, $y, $reg['age']);

            //
            // Add checkmarks
            //

            if( $form['options']['level'] == 'A' ) {
                $pdf->setFont('zapfdingbats');
                $pdf->Text(96.5, $y, "4");
                $pdf->Text(105.75, $y, "4");
                $pdf->Text(114.5, $y, "4");
                $pdf->setFont('helvetica');
            }
            elseif( $form['options']['level'] == 'B' ) {
                $pdf->setFont('zapfdingbats');
                $pdf->Text(123, $y, "4");
                $pdf->Text(132, $y, "4");
                $pdf->Text(140.5, $y, "4");
                $pdf->Text(149.5, $y, "4");
                $pdf->setFont('helvetica');
            }
            elseif( $form['options']['level'] == 'C' ) {
                $pdf->setFont('zapfdingbats');
                $pdf->Text(158.5, $y, "4");
                $pdf->Text(167.25, $y, "4");
                $pdf->Text(176, $y, "4");
                $pdf->Text(184.75, $y, "4");
                $pdf->Text(193.5, $y, "4");
                $pdf->setFont('helvetica');
            }

            $pdf->Text(203, $y, 'P');

            $y += 9.5;
            $reg_number++;
            $num_page_reg++;
        }

        $pdf->Text(174, 206, $num_page_reg);
        // Exam Date
        $pdf->Text(135.5, 218, $form['exam_date']->format('Y'));
        $pdf->Text(149, 218, $form['exam_date']->format('m'));
        $pdf->Text(159.5, 218, $form['exam_date']->format('d'));
        // Host Information
        $pdf->Text(8, 226, $form['host_name']);
        $pdf->Text(73, 226, $form['host_area_code']);
        $pdf->Text(85, 226, $form['host_phone']);
        $pdf->Text(8, 235, $form['host_street']);
        $pdf->Text(8, 245, $form['host_city']);
        $pdf->Text(58, 245, $form['host_province']);
        $pdf->Text(85, 245, $form['host_postal']);
        // Instructor Information
        $pdf->Text(108.5, 244, $form['instructor_name']);
        $pdf->Text(180, 244, $form['instructor_id']);
        $pdf->Text(108.5, 253, $form['instructor_email']);
        $pdf->Text(111, 263, $form['instructor_area_code']);
        $pdf->Text(122, 263, $form['instructor_phone']);
    }

    return array('stat'=>'ok');
}
?>
