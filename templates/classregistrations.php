<?php
//
// Description
// ===========
// This method will produce a PDF of the class.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_fatt_templates_classregistrations(&$ciniki, $tnid, $class_id, $tenant_details, $fatt_settings) {

    //
    // Load the class
    //
    $rsp = array('stat'=>'ok');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'classLoad');
    $rc = ciniki_fatt_classLoad($ciniki, $tnid, array('class_id'=>$class_id));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['class']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.127', 'msg'=>'Unable to find requested class'));
    }
    $class = $rc['class'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        public $left_margin = 18;
        public $right_margin = 18;
        public $top_margin = 18;
        //Page header
        public $header_image = null;
        public $header_name = '';
        public $header_addr = array();
        public $header_details = array();
        public $header_height = 0;      // The height of the image and address
        public $tenant_details = array();
        public $fatt_settings = array();

        public function Header() {
        }

        // Page footer
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 
                0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('L', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    //
    // Figure out the header tenant name and address information
    //
    $pdf->header_height = 0;
    $pdf->header_name = '';
    $pdf->header_height += (count($pdf->header_addr)*5);

    //
    // Load the header image
    //
    if( isset($fatt_settings['default-header-image']) && $fatt_settings['default-header-image'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
        $rc = ciniki_images_loadImage($ciniki, $tnid, 
            $fatt_settings['default-header-image'], 'original');
        if( $rc['stat'] == 'ok' ) {
            $pdf->header_image = $rc['image'];
        }
    }

    $pdf->tenant_details = $tenant_details;
    $pdf->fatt_settings = $fatt_settings;

//  print "<pre>" . print_r($class, true) . "</pre>";

    $instructors = '';
    if( isset($class['instructors']) ) {
        foreach($class['instructors'] as $iid => $instructor) {
            $instructors .= ($instructors!=''?', ':'') . $instructor['instructor']['name'];
        }
    }

    //
    // Determine the header details
    //
    $pdf->header_details = array(
        array('label'=>'Date', 'value'=>$class['start_date']),
        array('label'=>'Courses', 'value'=>$class['course_codes']),
        array('label'=>'Location', 'value'=>$class['location_name']),
        array('label'=>'Instructors', 'value'=>$instructors),
        array('label'=>'Registrations', 'value'=>count($class['registrations'])),
        );

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle($class['location_code'] . ' - ' . $class['date']);
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->top_margin + $pdf->header_height, $pdf->right_margin);
    $pdf->SetHeaderMargin($pdf->top_margin);


    // set font
    $pdf->SetFont('times', 'BI', 10);
    $pdf->SetCellPadding(2);

    // add a page
    $pdf->AddPage();
    $pdf->SetFillColor(255);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(51);
    $pdf->SetLineWidth(0.15);

    //
    // Add header image if specified
    //
    if( $pdf->header_image != null ) {
        $height = $pdf->header_image->getImageHeight();
        $width = $pdf->header_image->getImageWidth();
        $image_ratio = $width/$height;
        $img_width = 90;
        $available_ratio = $img_width/40;
        // Check if the ratio of the image will make it too large for the height,
        // and scaled based on either height or width.
        if( $available_ratio < $image_ratio ) {
            $pdf->Image('@'.$pdf->header_image->getImageBlob(), $pdf->left_margin, $pdf->top_margin, 
                $img_width, 0, 'JPEG', '', 'TL', 2, '150');
        } else {
            $pdf->Image('@'.$pdf->header_image->getImageBlob(), $pdf->left_margin, $pdf->top_margin, 
                0, 42, 'JPEG', '', 'TL', 2, '150');
        }
    }

    //
    // Add the information to the first page
    //
    $w = array(25, 118);
    foreach($pdf->header_details as $detail) {
        $pdf->SetFillColor(224);
        $pdf->SetX($pdf->left_margin + 100);
        $pdf->SetFont('', 'B');
        $pdf->Cell($w[0], 6, $detail['label'], 1, 0, 'L', 1);
        $pdf->SetFillColor(255);
        $pdf->SetFont('', '');
        $pdf->Cell($w[1], 6, $detail['value'], 1, 0, 'L', 1);
        $pdf->Ln();
    }
    $pdf->Ln();

    //
    // Add the registrations
    //
    $w = array(23, 86, 86, 15, 18, 15);
    $pdf->SetFillColor(224);
    $pdf->SetFont('', 'B');
    $pdf->SetCellPadding(2);
    $pdf->Cell($w[0], 6, 'Course', 1, 0, 'L', 1);
    $pdf->Cell($w[1], 6, 'Student', 1, 0, 'L', 1);
    $pdf->Cell($w[2], 6, 'Parent/Employer', 1, 0, 'L', 1);
    $pdf->Cell($w[3], 6, 'Initials', 1, 0, 'L', 1);
    $pdf->Cell($w[4], 6, 'Status', 1, 0, 'L', 1);
    $pdf->Cell($w[5], 6, 'P/F', 1, 0, 'L', 1);
    $pdf->Ln();
    $pdf->SetFillColor(236);
    $pdf->SetTextColor(0);
    $pdf->SetFont('');

    $fill=0;
    foreach($class['registrations'] as $reg) {
        $reg = $reg['registration'];

        //
        // Get the student information, so it can be added to the form and verified
        //
        $student_information = $reg['student_display_name'] . "\n";
        if( $reg['student_id'] > 0 ) {
            $rc = ciniki_customers_hooks_customerDetails($ciniki, $tnid, 
                array('customer_id'=>$reg['student_id'], 'addresses'=>'yes', 'phones'=>'yes', 'emails'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
//          print "<pre>" . print_r($rc, true) . "</pre>";
            if( isset($rc['customer']) ) {
                $customer = $rc['customer'];
                $student_information = $customer['first'] . ' ' . $customer['last'] . "\n";
                if( isset($customer['addresses']) ) {
                    foreach($customer['addresses'] as $a => $address) {
                        if( count($customer['addresses']) > 1 ) {
                            $flags = $address['address']['flags'];
                            $comma = '';
                            if( ($flags&0x01) == 0x01 ) { $label .= $comma . 'Shipping'; $comma = ', ';}
                            if( ($flags&0x02) == 0x02 ) { $label .= $comma . 'Billing'; $comma = ', ';}
                            if( ($flags&0x04) == 0x04 ) { $label .= $comma . 'Mailing'; $comma = ', ';}
                        }
                        $joined_address = $address['address']['address1'];
                        if( isset($address['address']['address2']) && $address['address']['address2'] != '' ) {
                            $joined_address .= ($joined_address!=''?', ':'') . $address['address']['address2'];
                        }
                        $city = '';
                        $comma = '';
                        if( isset($address['address']['city']) && $address['address']['city'] != '' ) {
                            $city = $address['address']['city'];
                            $comma = ', ';
                        }
                        if( isset($address['address']['province']) && $address['address']['province'] != '' ) {
                            $city .= $comma . $address['address']['province'];
                            $comma = ', ';
                        }
                        if( isset($address['address']['postal']) && $address['address']['postal'] != '' ) {
                            $city .= '  ' . $address['address']['postal'];
                            $comma = ', ';
                        }
                        if( $city != '' ) {
                            if( $pdf->getStringWidth($joined_address . ', ' . $city) > $w[1] ) {
                                $joined_address .= "\n" . $city . "\n";
                            } else {
                                $joined_address .= ', ' . $city . "\n";
                            }
                        }
                        $student_information .= $joined_address;
                    }
                } else {
                    $student_information .= "Address: \n";
                }
                if( isset($customer['phones']) ) {
                    $phones = "";
                    foreach($customer['phones'] as $phone) {
                        if( count($customer['phones']) > 1 ) {
                            $p = $phone['phone_label'] . ': ' . $phone['phone_number'];
                            if( $pdf->getStringWidth($phones . ', ' . $p) > $w[1] ) {
                                $phones .= "\n" . $p;
                            } else {
                                $phones .= ($phones!=''?', ':'') . $p;
                            }
                        } else {
                            $phones .= $phone['phone_number'];
                        }
                    }
                    if( count($customer['phones']) > 0 ) {
                        $student_information .= $phones . "\n";
                    } else {
                        $student_information .= "Phone: \n";
                    }
                }
                if( isset($customer['emails']) ) {
                    $emails = '';
                    $comma = '';
                    foreach($customer['emails'] as $e => $email) {
                        $emails .= ($emails!=''?', ':'') . $email['email']['address'];
                    }
                    $student_information .= $emails . "\n";
                }
                if( isset($customer['birthdate']) ) {
                    $student_information .= "Birthday: " . $customer['birthdate'] . "\n";
                }
            }
        }

        // If a tenant, then convert "Payment Required" to "Invoice"
        $tenant_information = '';
        if( $reg['customer_type'] == 2 || $reg['student_id'] != $reg['customer_id'] ) {
            if( $reg['invoice_status'] == 'Payment Required' ) {
                $reg['invoice_status'] = 'Invoice';
            }
            $rc = ciniki_customers_hooks_customerDetails($ciniki, $tnid, 
                array('customer_id'=>$reg['customer_id'], 'addresses'=>'yes', 'phones'=>'yes', 'emails'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $tenant_information = $reg['customer_display_name'] . "\n";
//          print "<pre>" . print_r($rc, true) . "</pre>";
            if( isset($rc['customer']) ) {
                $customer = $rc['customer'];
                if( isset($customer['addresses']) ) {
                    foreach($customer['addresses'] as $a => $address) {
                        if( count($customer['addresses']) > 1 ) {
                            $flags = $address['address']['flags'];
                            $comma = '';
                            if( ($flags&0x01) == 0x01 ) { $label .= $comma . 'Shipping'; $comma = ', ';}
                            if( ($flags&0x02) == 0x02 ) { $label .= $comma . 'Billing'; $comma = ', ';}
                            if( ($flags&0x04) == 0x04 ) { $label .= $comma . 'Mailing'; $comma = ', ';}
                        }
                        $joined_address = $address['address']['address1'];
                        if( isset($address['address']['address2']) && $address['address']['address2'] != '' ) {
                            $joined_address .= $address['address']['address2'];
                        }
                        $city = '';
                        $comma = '';
                        if( isset($address['address']['city']) && $address['address']['city'] != '' ) {
                            $city = $address['address']['city'];
                            $comma = ', ';
                        }
                        if( isset($address['address']['province']) && $address['address']['province'] != '' ) {
                            $city .= $comma . $address['address']['province'];
                            $comma = ', ';
                        }
                        if( isset($address['address']['postal']) && $address['address']['postal'] != '' ) {
                            $city .= '  ' . $address['address']['postal'];
                            $comma = ', ';
                        }
                        if( $city != '' ) {
                            if( $pdf->getStringWidth($joined_address . ', ' . $city) > $w[1] ) {
                                $joined_address .= "\n" . $city . "\n";
                            } else {
                                $joined_address .= ', ' . $city . "\n";
                            }
                        }
                        $tenant_information .= $joined_address;
                    }
                } else {
                    $tenant_information .= "Address: \n";
                }
                if( isset($customer['phones']) ) {
                    $phones = "";
                    foreach($customer['phones'] as $phone) {
                        if( count($customer['phones']) > 1 ) {
                            $p = $phone['phone_label'] . ': ' . $phone['phone_number'];
                            if( $pdf->getStringWidth($phones . ', ' . $p) > $w[2] ) {
                                $phones .= "\n" . $p;
                            } else {
                                $phones .= ($phones!=''?', ':'') . $p;
                            }
                        } else {
                            $phones .= $phone['phone_number'];
                        }
                    }
                    if( count($customer['phones']) > 0 ) {
                        $tenant_information .= $phones . "\n";
                    } else {
                        $tenant_information .= "Phone: \n";
                    }
                }
                if( isset($customer['emails']) ) {
                    $emails = '';
                    $comma = '';
                    foreach($customer['emails'] as $e => $email) {
                        $emails .= ($emails!=''?', ':'') . $email['email']['address'];
                    }
                    $tenant_information .= $emails . "\n";
                }
            }
        }

        // Calculate the line height required
        $lh = $pdf->getStringHeight($w[1], $tenant_information);
        $lh1 = $pdf->getStringHeight($w[1], $student_information);
        $lh2 = $pdf->getStringHeight($w[3], $reg['invoice_status']);
        if( $lh1 > $lh ) { $lh = $lh1; }
        if( $lh2 > $lh ) { $lh = $lh2; }

        // Check if we need a page break
        if( $pdf->getY() > ($pdf->getPageHeight() - $lh - $pdf->top_margin - $pdf->header_height) ) {
            $pdf->AddPage();
            $pdf->SetFillColor(224);
            $pdf->SetFont('', 'B');
            $pdf->Cell($w[0], 6, 'Course', 1, 0, 'L', 1);
            $pdf->Cell($w[1], 6, 'Student', 1, 0, 'L', 1);
            $pdf->Cell($w[2], 6, 'Tenant', 1, 0, 'L', 1);
            $pdf->Cell($w[3], 6, 'Initials', 1, 0, 'L', 1);
            $pdf->Cell($w[4], 6, 'Status', 1, 0, 'L', 1);
            $pdf->Cell($w[5], 6, 'P/F', 1, 0, 'L', 1);
            $pdf->Ln();
            $pdf->SetFillColor(236);
            $pdf->SetTextColor(0);
            $pdf->SetFont('');
        }

        $pdf->MultiCell($w[0], $lh, $reg['course_code'], 1, 'L', $fill, 0);
        $pdf->MultiCell($w[1], $lh, $student_information, 1, 'L', $fill, 0);
        $pdf->MultiCell($w[2], $lh, $tenant_information, 1, 'L', $fill, 0);
        $pdf->MultiCell($w[3], $lh, ' ', 1, 'L', $fill, 0);
        $pdf->MultiCell($w[4], $lh, $reg['invoice_status'], 1, 'L', $fill, 0);
        $pdf->MultiCell($w[5], $lh, ' ', 1, 'L', $fill, 0);
        $pdf->Ln();

        $fill=!$fill;
    }

    return array('stat'=>'ok', 'class'=>$class, 'pdf'=>$pdf);
}
?>
