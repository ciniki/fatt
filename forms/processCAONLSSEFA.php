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
function ciniki_fatt_forms_processCAONLSSEFA($ciniki, $business_id, &$pdf, $form) {

    $reg_number = 0;
    $page_num = 0;
    $total_pages = ceil(count($form['registrations']) / 5);
    while($reg_number < count($form['registrations']) ) {
        $pdf->AddPage();
        $pdf->SetCellPaddings(1, 0, 1, 0);
        if( ($page_num%2) == 0 ) {
            $pdf->Image($ciniki['config']['core']['modules_dir'] . '/fatt/forms/bgCAONLSSEFA1.png', 0, 0, 216, 279, '', '', '', false, 300, '', false, false, 0);
        } else {
            $pdf->Image($ciniki['config']['core']['modules_dir'] . '/fatt/forms/bgCAONLSSEFA2.png', 0, 0, 216, 279, '', '', '', false, 300, '', false, false, 0);
        }

        //
        // Setup the registration information
        //
        $y = 60.5;
        $num_page_reg = 0;
        while($reg_number < count($form['registrations']) && $num_page_reg < 5) {
            $reg = $form['registrations'][$reg_number];
            $pdf->setFont('', '', '11');
            $pdf->Text(13, $y, $reg['display_name']);
            $pdf->Text(13, $y+6, $reg['address']);
            $pdf->Text(57, $y+6, $reg['apt']);
            $pdf->Text(13, $y+12, $reg['city']);
            $pdf->Text(63, $y+12, $reg['postal']);
            $pdf->Text(13, $y+17.5, $reg['email']);
            $pdf->Text(13, $y+23.5, $reg['phone']);

            $pdf->Text(82, $y, $reg['birthyear']);
            $pdf->Text(82, $y+9, $reg['birthmonth']);
            $pdf->Text(82, $y+17.5, $reg['birthday']);

            //
            // Add checkmarks
            //
            $pdf->setFont('zapfdingbats', '', 16);
            $pdf->Text(91, $y+4, "4");
            $pdf->Text(98.5, $y+4, "4");
            $pdf->Text(106.75, $y+4, "4");
            $pdf->Text(114.5, $y+4, "4");
            $pdf->Text(122.25, $y+4, "4");
            $pdf->Text(130, $y+4, "4");
            $pdf->Text(138, $y+4, "4");
            $pdf->Text(146, $y+4, "4");
            $pdf->Text(154, $y+4, "4");
            $pdf->Text(162, $y+4, "4");
            $pdf->Text(170, $y+4, "4");
            $pdf->Text(177.9, $y+4, "4");
            $pdf->Text(185.9, $y+4, "4");
            $pdf->Text(193.6, $y+4, "4");
            $pdf->setFont('helvetica', '', 12);

            $pdf->Text(203, $y+10, 'P');

            $y += 28.5;
            $reg_number++;
            $num_page_reg++;
        }

        // Check box is more registrations on next page
        if( $reg_number < count($form['registrations']) ) {
            $pdf->setFont('zapfdingbats', '', 16);
            $pdf->Text(7.5, 205.5, "4");
            $pdf->setFont('helvetica', '', 12);
        }

        $pdf->setFont('helvetica', '', 11);
        $pdf->Text(45, 207.5, $page_num+1);
        $pdf->Text(59, 207.5, $total_pages);

        $pdf->Text(174, 206, count($form['registrations']));

        $pdf->setFont('', '', '10');

        if( ($page_num%2) == 0 ) {
            // Host Information
            $pdf->Text(8, 224.5, $form['host_name']);
            $pdf->Text(73, 224.5, $form['host_area_code']);
            $pdf->Text(85, 224.5, $form['host_phone']);
            $pdf->Text(8, 233, $form['host_street']);
            $pdf->Text(8, 241, $form['host_city']);
            $pdf->Text(58, 241, $form['host_province']);
            $pdf->Text(85, 241, $form['host_postal']);

            // Exam Date
            $pdf->Text(25, 256.5, $form['exam_date']->format('Y'));
            $pdf->Text(37.5, 256.5, $form['exam_date']->format('m'));
            $pdf->Text(48, 256.5, $form['exam_date']->format('d'));
            $pdf->setFont('zapfdingbats', '', 12);
            $pdf->Text(($form['options']['recert'] == 'yes' ? 86.5 : 66), 256.5, "4");
            $pdf->setFont('helvetica', '', 11);

            // Instructor Information
            $pdf->Text(108.5, 217, $form['instructor_name']);
            $pdf->Text(180, 217, $form['instructor_id']);
            $pdf->Text(108.5, 223.5, $form['instructor_email']);
            $pdf->Text(111, 231, $form['instructor_area_code']);
            $pdf->Text(122, 231, $form['instructor_phone']);

            $pdf->Text(109, 247.5, $form['examiner_name']);
            $pdf->Text(180, 247.5, $form['examiner_id']);
            $pdf->Text(109, 256, $form['examiner_email']);
            $pdf->Text(111, 263.5, $form['examiner_area_code']);
            $pdf->Text(122, 263.5, $form['examiner_phone']);
        } else {
            // Host Information
            $pdf->Text(8, 218, $form['host_name']);
            $pdf->Text(9.5, 230, $form['host_area_code']);
            $pdf->Text(21, 230, $form['host_phone']);

            // Exam Date
            $pdf->Text(25, 248, $form['exam_date']->format('Y'));
            $pdf->Text(37.5, 248, $form['exam_date']->format('m'));
            $pdf->Text(48.5, 248, $form['exam_date']->format('d'));
            $pdf->setFont('zapfdingbats', '', 12);
            $pdf->Text(($form['options']['recert'] == 'yes' ? 86.5 : 66), 247.75, "4");
            $pdf->setFont('helvetica', '', 11);

            // Examiner Information
            $pdf->Text(109, 248.5, $form['examiner_name']);
            $pdf->Text(180, 248.5, $form['examiner_id']);
            $pdf->Text(109, 256.5, $form['examiner_email']);
            $pdf->Text(111, 264, $form['examiner_area_code']);
            $pdf->Text(122, 264, $form['examiner_phone']);
        }
        $page_num++;
    }

    return array('stat'=>'ok');
}
?>
