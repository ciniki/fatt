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
function ciniki_fatt_forms_processCAONLSSHCP2026($ciniki, $tnid, &$pdf, $form) {

    $reg_number = 0;
    $page_num = 0;
    $total_pages = ceil(count($form['registrations']) / 4);
    while($reg_number < count($form['registrations']) ) {
        $pdf->AddPage();
        $pdf->SetCellPaddings(1, 0, 1, 0);
        if( ($page_num%2) == 0 ) {
            $pdf->Image($ciniki['config']['core']['modules_dir'] . '/fatt/forms/bgCAONLSSHCP20261.png', 0, 0, 216, 279, '', '', '', false, 300, '', false, false, 0);
        } else {
            $pdf->Image($ciniki['config']['core']['modules_dir'] . '/fatt/forms/bgCAONLSSHCP20262.png', 0, 0, 216, 279, '', '', '', false, 300, '', false, false, 0);
        }

        //
        // Setup the registration information
        //
        $y = 69.0;
        $num_page_reg = 0;
        $pdf->SetFillColor(255,255,255);
        $pdf->setCellPadding(0);
        while($reg_number < count($form['registrations']) && $num_page_reg < 4) {
            $reg = $form['registrations'][$reg_number];
            $pdf->setFont('', '', '9');
            $pdf->Text(12, $y, $reg['display_name']);
            $pdf->Text(29, $y+9, $reg['birthyear'] . '/' . $reg['birthmonth'] . '/' . $reg['birthday']);
//            $pdf->Text(32, $y+9, $reg['birthmonth']);
//            $pdf->Text(35, $y+9, $reg['birthday']);
            $pdf->Text(46, $y+9, $reg['phone']);
            $pdf->Text(17, $y+13.5, $reg['address'] . '  ' . $reg['province']);
//            $pdf->Cell(16, $y+13.5, $reg['address'] . '  ' . $reg['province']);
            $pdf->Text(57, $y+13.5, $reg['apt']);
            $pdf->Text(13, $y+18.5, $reg['city']);
            $pdf->Text(46, $y+18.5, $reg['postal']);
            $pdf->Text(15, $y+23, $reg['email']);


            //
            // Add checkmarks
            //
            $pdf->setFont('zapfdingbats', '', 16);
            $pdf->Text(80, $y+6, "4");
            $pdf->Text(96.25, $y+6, "4");
            $pdf->Text(112, $y+6, "4");
            $pdf->Text(128, $y+6, "4");
            $pdf->Text(143, $y+6, "4");
            $pdf->Text(160.5, $y+6, "4");
            $pdf->Text(175.0, $y+6, "4"); // 15
            $pdf->Text(192, $y+6, "4");
            $pdf->setFont('helvetica', '', 12);

            $pdf->Text(204, $y+10, 'P');

            $y += 29.5;
            $reg_number++;
            $num_page_reg++;
        }

        // Check box is more registrations on next page
        if( $reg_number < count($form['registrations']) ) {
            $pdf->setFont('zapfdingbats', '', 16);
            $pdf->Text(9, 190.0, "4");
            $pdf->setFont('helvetica', '', 12);
        }

        if( ($page_num%2) == 0 ) {
            $pdf->setFont('helvetica', '', 11);
            $pdf->Text(46, 191, $page_num+1);
            $pdf->Text(60, 191, $total_pages);

            $pdf->Text(177, 191, count($form['registrations']));

            $pdf->setFont('', '', '9');

            // Host Information
            $pdf->Text(9, 212.5, $form['host_name']);
            $pdf->Text(79, 212.5, $form['host_area_code']);
            $pdf->Text(87, 212.5, $form['host_phone']);
            $pdf->Text(9, 220.5, $form['host_street']);
            $pdf->Text(9, 229, $form['host_city']);
            $pdf->Text(55, 229, $form['host_province']);
            $pdf->Text(88, 229, $form['host_postal']);

            // Exam Date
            $pdf->Text(23, 245.5, $form['exam_date']->format('Y'));
            $pdf->Text(34.5, 245.5, $form['exam_date']->format('m'));
            $pdf->Text(46, 245.5, $form['exam_date']->format('d'));
            $pdf->Text(9, 256, $form['host_name']);
            $pdf->Text(79.5, 256.5, $form['host_area_code']);
            $pdf->Text(88, 256.5, $form['host_phone']);
            $pdf->setFont('zapfdingbats', '', 9);
            $pdf->Text(($form['options']['recert'] == 'yes' ? 76.5 : 56), 239, "4");
            $pdf->setFont('helvetica', '', 9);

            // Instructor Information
            $pdf->Text(110, 208, $form['instructor_name']);
            $pdf->Text(180, 208, $form['instructor_id']);
            $pdf->Text(110, 217, $form['instructor_email']);
            $pdf->Text(112, 226, $form['instructor_area_code']);
            $pdf->Text(122, 226, $form['instructor_phone']);
            //
            // Add instructor signature
            //
            if( isset($form['instructor_sig_image_id']) ) {
                $rc = ciniki_images_loadImage($ciniki, $tnid, $form['instructor_sig_image_id'], 'original');
                if( $rc['stat'] == 'ok' ) {
                    $pdf->Image('@'.$rc['image']->getImageBlob(), 160, 221.5, 50, 8, 'PNG', '', 'C', 2, '150', '', false, false, 0, 1);
                }
            }

            $pdf->setFont('zapfdingbats', '', 8);
//            $pdf->Text(197, 230, "4");
//            $pdf->Text(197, 257, "4");
            $pdf->setFont('helvetica', '', 9);
            $pdf->Text(110, 244, $form['examiner_name']);
            $pdf->Text(180, 244, $form['examiner_id']);
            $pdf->Text(110, 251.5, $form['examiner_email']);
            $pdf->Text(112, 258.5, $form['examiner_area_code']);
            $pdf->Text(122, 259.5, $form['examiner_phone']);
            //
            // Add instructor signature
            //
            if( isset($form['instructor_sig_image_id']) ) {
                $rc = ciniki_images_loadImage($ciniki, $tnid, $form['instructor_sig_image_id'], 'original');
                if( $rc['stat'] == 'ok' ) {
                    $pdf->Image('@'.$rc['image']->getImageBlob(), 160, 256, 50, 7.5, 'PNG', '', 'C', 2, '150', '', false, false, 0, 1);
                }
            }
        } else {
            $pdf->setFont('helvetica', '', 11);
            $pdf->Text(45, 195, $page_num+1);
            $pdf->Text(59, 195, $total_pages);

            $pdf->Text(176, 194, count($form['registrations']));

            $pdf->setFont('', '', '9');

            // Host Information
            $pdf->setFont('helvetica', '', 9);
            $pdf->Text(9, 212.5, $form['host_name']);
            $pdf->Text(11, 223, $form['host_area_code']);
            $pdf->Text(23, 223, $form['host_phone']);

            $pdf->setFont('zapfdingbats', '', 9);
            $pdf->Text(($form['options']['recert'] == 'yes' ? 76.5 : 56), 234, "4");
            $pdf->setFont('helvetica', '', 9);

            // Exam Date
            $pdf->Text(23, 240.5, $form['exam_date']->format('Y'));
            $pdf->Text(34.5, 240.5, $form['exam_date']->format('m'));
            $pdf->Text(46, 240.5, $form['exam_date']->format('d'));
            $pdf->Text(9, 249, $form['host_name']);
            $pdf->Text(11, 258, $form['host_area_code']);
            $pdf->Text(21, 258, $form['host_phone']);
//            $pdf->setFont('zapfdingbats', '', 12);
//            $pdf->Text(($form['options']['recert'] == 'yes' ? 86.5 : 66), 247.75, "4");

//            $pdf->setFont('zapfdingbats', '', 8);
//            $pdf->Text(184.75, 211, "4");
            // Examiner Information
            $pdf->Text(110, 240, $form['examiner_name']);
            $pdf->Text(180, 240, $form['examiner_id']);
            $pdf->Text(110, 248, $form['examiner_email']);
            $pdf->Text(111.5, 255.5, $form['examiner_area_code']);
            $pdf->Text(122, 255.5, $form['examiner_phone']);
            $pdf->setFont('helvetica', '', 9);
            //
            // Add instructor signature
            //
            if( isset($form['instructor_sig_image_id']) ) {
                $rc = ciniki_images_loadImage($ciniki, $tnid, $form['instructor_sig_image_id'], 'original');
                if( $rc['stat'] == 'ok' ) {
                    $pdf->Image('@'.$rc['image']->getImageBlob(), 160, 251.5, 50, 8, 'PNG', '', 'C', 2, '150', '', false, false, 0, 1);
                }
            }
        }
        $page_num++;
    }

    return array('stat'=>'ok');
}
?>
