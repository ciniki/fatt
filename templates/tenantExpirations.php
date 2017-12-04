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
function ciniki_fatt_templates_tenantExpirations(&$ciniki, $tnid, $args) {

    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        //Page header
        public $header_image = null;
        public $header_name = '';
        public $header_addr = array();
        public $header_details = array();
        public $header_height = 0;      // The height of the image and address
        public $footer_message = '';
        public $tenant_details = array();
        public $sapos_settings = array();

        public function Header() {
            //
            // Check if there is an image to be output in the header.   The image
            // will be displayed in a narrow box if the contact information is to
            // be displayed as well.  Otherwise, image is scaled to be 100% page width
            // but only to a maximum height of the header_height (set far below).
            //
            $img_width = 0;
            if( $this->header_image != null ) {
                $height = $this->header_image->getImageHeight();
                $width = $this->header_image->getImageWidth();
                $image_ratio = $width/$height;
                if( count($this->header_addr) == 0 && $this->header_name == '' ) {
                    $img_width = 180;
                } else {
                    $img_width = 120;
                }
                $available_ratio = $img_width/$this->header_height;
                // Check if the ratio of the image will make it too large for the height,
                // and scaled based on either height or width.
                if( $available_ratio < $image_ratio ) {
                    $this->Image('@'.$this->header_image->getImageBlob(), 15, 12, 
                        $img_width, 0, 'JPEG', '', 'L', 2, '150');
                } else {
                    $this->Image('@'.$this->header_image->getImageBlob(), 15, 12, 
                        0, $this->header_height-5, 'JPEG', '', 'L', 2, '150');
                }
            }

            //
            // Add the contact information
            //
            if( !isset($this->sapos_settings['invoice-header-contact-position']) 
                || $this->sapos_settings['invoice-header-contact-position'] != 'off' ) {
                if( isset($this->sapos_settings['invoice-header-contact-position'])
                    && $this->sapos_settings['invoice-header-contact-position'] == 'left' ) {
                    $align = 'L';
                } elseif( isset($this->sapos_settings['invoice-header-contact-position'])
                    && $this->sapos_settings['invoice-header-contact-position'] == 'right' ) {
                    $align = 'R';
                } else {
                    $align = 'C';
                }
                $this->Ln(8);
                if( $this->header_name != '' ) {
                    $this->SetFont('times', 'B', 20);
                    if( $img_width > 0 ) {
                        $this->Cell($img_width, 10, '', 0);
                    }
                    $this->Cell(180-$img_width, 10, $this->header_name, 
                        0, false, $align, 0, '', 0, false, 'M', 'M');
                    $this->Ln(5);
                }
                $this->SetFont('times', '', 10);
                if( count($this->header_addr) > 0 ) {
                    $address_lines = count($this->header_addr);
                    if( $img_width > 0 ) {
                        $this->Cell($img_width, ($address_lines*5), '', 0);
                    }
                    $this->MultiCell(180-$img_width, $address_lines, implode("\n", $this->header_addr), 
                        0, $align, 0, 0, '', '', true, 0, false, true, 0, 'M', false);
                    $this->Ln();
                }
            }

            //
            // Output the invoice details which should be at the top of each page.
            //
            $this->SetCellPadding(2);
/*          if( count($this->header_details) <= 6 ) {
                if( $this->header_name == '' && count($this->header_addr) == 0 ) {
                    $this->Ln($this->header_height+6);
                } elseif( $this->header_name == '' && count($this->header_addr) > 0 ) {
                    $used_space = 4 + count($this->header_addr)*5;
                    if( $used_space < 30 ) {
                        $this->Ln(30-$used_space+5);
                    } else {
                        $this->Ln(7);
                    }
                } elseif( $this->header_name != '' && count($this->header_addr) > 0 ) {
                    $used_space = 10 + count($this->header_addr)*5;
                    if( $used_space < 30 ) {
                        $this->Ln(30-$used_space+6);
                    } else {
                        $this->Ln(5);
                    }
                } elseif( $this->header_name != '' && count($this->header_addr) == 0 ) {
                    $this->Ln(25);
                }
                $this->SetFont('times', '', 10);
                $num_elements = count($this->header_details);
                if( $num_elements == 3 ) {
                    $w = array(60,60,60);
                } elseif( $num_elements == 4 ) {
                    $w = array(45,45,45,45);
                } elseif( $num_elements == 5 ) {
                    $w = array(36,36,36,36,36);
                } else {
                    $w = array(30,30,30,30,30,30);
                }
                $lh = 6;
                $this->SetFont('', 'B');
                for($i=0;$i<$num_elements;$i++) {
                    if( $this->header_details[$i]['label'] != '' ) {
                        $this->SetFillColor(224);
                        $this->Cell($w[$i], $lh, $this->header_details[$i]['label'], 1, 0, 'C', 1);
                    } else {
                        $this->SetFillColor(255);
                        $this->Cell($w[$i], $lh, '', 'T', 0, 'C', 1);
                    }
                }
                $this->Ln();
                $this->SetFillColor(255);
                $this->SetFont('');
                for($i=0;$i<$num_elements;$i++) {
                    if( $this->header_details[$i]['label'] != '' ) {
                        $this->Cell($w[$i], $lh, $this->header_details[$i]['value'], 1, 0, 'C', 1);
                    } else {
                        $this->Cell($w[$i], $lh, '', 0, 0, 'C', 1);
                    }
                }
                $this->Ln();
            }
            */
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            // Set font
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(90, 10, $this->footer_message,
                0, false, 'L', 0, '', 0, false, 'T', 'M');
            $this->Cell(90, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 
                0, false, 'R', 0, '', 0, false, 'T', 'M');
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    //
    // Figure out the header tenant name and address information
    //
    $pdf->header_height = 0;
    $pdf->header_name = '';
    if( !isset($args['sapos_settings']['invoice-header-contact-position'])
        || $args['sapos_settings']['invoice-header-contact-position'] != 'off' ) {
        if( !isset($args['sapos_settings']['invoice-header-tenant-name'])
            || $args['sapos_settings']['invoice-header-tenant-name'] == 'yes' ) {
            $pdf->header_name = $args['tenant_details']['name'];
            $pdf->header_height = 8;
        }
        if( !isset($args['sapos_settings']['invoice-header-tenant-address'])
            || $args['sapos_settings']['invoice-header-tenant-address'] == 'yes' ) {
            if( isset($args['tenant_details']['contact.address.street1']) 
                && $args['tenant_details']['contact.address.street1'] != '' ) {
                $pdf->header_addr[] = $args['tenant_details']['contact.address.street1'];
            }
            if( isset($args['tenant_details']['contact.address.street2']) 
                && $args['tenant_details']['contact.address.street2'] != '' ) {
                $pdf->header_addr[] = $args['tenant_details']['contact.address.street2'];
            }
            $city = '';
            if( isset($args['tenant_details']['contact.address.city']) 
                && $args['tenant_details']['contact.address.city'] != '' ) {
                $city .= $args['tenant_details']['contact.address.city'];
            }
            if( isset($args['tenant_details']['contact.address.province']) 
                && $args['tenant_details']['contact.address.province'] != '' ) {
                $city .= ($city!='')?', ':'';
                $city .= $args['tenant_details']['contact.address.province'];
            }
            if( isset($args['tenant_details']['contact.address.postal']) 
                && $args['tenant_details']['contact.address.postal'] != '' ) {
                $city .= ($city!='')?'  ':'';
                $city .= $args['tenant_details']['contact.address.postal'];
            }
            if( $city != '' ) {
                $pdf->header_addr[] = $city;
            }
        }
        if( !isset($args['sapos_settings']['invoice-header-tenant-phone'])
            || $args['sapos_settings']['invoice-header-tenant-phone'] == 'yes' ) {
            if( isset($args['tenant_details']['contact.phone.number']) 
                && $args['tenant_details']['contact.phone.number'] != '' ) {
                $pdf->header_addr[] = 'phone: ' . $args['tenant_details']['contact.phone.number'];
            }
            if( isset($args['tenant_details']['contact.tollfree.number']) 
                && $args['tenant_details']['contact.tollfree.number'] != '' ) {
                $pdf->header_addr[] = 'phone: ' . $args['tenant_details']['contact.tollfree.number'];
            }
        }
        if( !isset($args['sapos_settings']['invoice-header-tenant-cell'])
            || $args['sapos_settings']['invoice-header-tenant-cell'] == 'yes' ) {
            if( isset($args['tenant_details']['contact.cell.number']) 
                && $args['tenant_details']['contact.cell.number'] != '' ) {
                $pdf->header_addr[] = 'cell: ' . $args['tenant_details']['contact.cell.number'];
            }
        }
        if( (!isset($args['sapos_settings']['invoice-header-tenant-fax'])
            || $args['sapos_settings']['invoice-header-tenant-fax'] == 'yes')
            && isset($args['tenant_details']['contact.fax.number']) 
            && $args['tenant_details']['contact.fax.number'] != '' ) {
            $pdf->header_addr[] = 'fax: ' . $args['tenant_details']['contact.fax.number'];
        }
        if( (!isset($args['sapos_settings']['invoice-header-tenant-email'])
            || $args['sapos_settings']['invoice-header-tenant-email'] == 'yes')
            && isset($args['tenant_details']['contact.email.address']) 
            && $args['tenant_details']['contact.email.address'] != '' ) {
            $pdf->header_addr[] = $args['tenant_details']['contact.email.address'];
        }
        if( (!isset($args['sapos_settings']['invoice-header-tenant-website'])
            || $args['sapos_settings']['invoice-header-tenant-website'] == 'yes')
            && isset($args['tenant_details']['contact-website-url']) 
            && $args['tenant_details']['contact-website-url'] != '' ) {
            $pdf->header_addr[] = $args['tenant_details']['contact-website-url'];
        }
    }
    $pdf->header_height += (count($pdf->header_addr)*5);

    //
    // Set the minimum header height
    //
    if( $pdf->header_height < 30 ) {
        $pdf->header_height = 30;
    }

    //
    // Load the header image
    //
    if( isset($args['sapos_settings']['invoice-header-image']) && $args['sapos_settings']['invoice-header-image'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
        $rc = ciniki_images_loadImage($ciniki, $tnid, 
            $args['sapos_settings']['invoice-header-image'], 'original');
        if( $rc['stat'] == 'ok' ) {
            $pdf->header_image = $rc['image'];
        }
    }

    $pdf->tenant_details = $args['tenant_details'];
    $pdf->sapos_settings = $args['sapos_settings'];

    //
    // Determine the header details
    //
/*  $pdf->header_details = array(
        array('label'=>'Employer', 'value'=>$args['customer']['display_name']),
        array('label'=>'Report Date', 'value'=>$args['report_date']),
        );*/

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($args['tenant_details']['name']);
    $pdf->SetTitle('Employee Certifications');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');
    $pdf->footer_message = $args['customer']['display_name'] . ', Employee Certifications, ' . $args['report_date'];

    // set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, $pdf->header_height+20, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);


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
    // Determine the billing address information
    //
    $baddr = array();
    if( isset($args['customer']['display_name']) && $args['customer']['display_name'] != '' ) {
        $baddr[] = $args['customer']['display_name'];
    }
    if( isset($args['customer']['billing_address1']) && $args['customer']['billing_address1'] != '' ) {
        $baddr[] = $args['customer']['billing_address1'];
    }
    if( isset($args['customer']['billing_address2']) && $args['customer']['billing_address2'] != '' ) {
        $baddr[] = $args['customer']['billing_address2'];
    }
    $city = '';
    if( isset($args['customer']['billing_city']) && $args['customer']['billing_city'] != '' ) {
        $city = $args['customer']['billing_city'];
    }
    if( isset($args['customer']['billing_province']) && $args['customer']['billing_province'] != '' ) {
        $city .= (($city!='')?', ':'') . $args['customer']['billing_province'];
    }
    if( isset($args['customer']['billing_postal']) && $args['customer']['billing_postal'] != '' ) {
        $city .= (($city!='')?',  ':'') . $args['customer']['billing_postal'];
    }
    if( $city != '' ) {
        $baddr[] = $city;
    }
    if( isset($args['customer']['billing_country']) && $args['customer']['billing_country'] != '' ) {
        $baddr[] = $args['customer']['billing_country'];
    }

    //
    // Output the company address
    //
    $w = array(100, 80);
    $lh = 6;
    $pdf->SetFillColor(224);
    $pdf->setCellPadding(2);
    if( count($baddr) > 0 ) {
        $pdf->SetFont('', 'B');
        $pdf->Cell($w[0], $lh, 'Employer:', 1, 0, 'L', 1);
        $border = 1;
        $pdf->Ln($lh);  
        $pdf->SetFont('');
        $pdf->setCellPaddings(2, 4, 2, 2);
        $pdf->MultiCell($w[0], $lh, implode("\n", $baddr), $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', false);
        $pdf->Ln($lh);
    }
    $pdf->Ln();

    //
    // Add report title
    //
    $pdf->SetFont('', 'B', 20);
    $pdf->Cell(180, 8, 'Employee Certifications as of ' . $args['report_date'], 0, 0, 'L', 0);
    $pdf->Ln(18);
    $pdf->SetFont('', '', 10);

    //
    // Add the cert items
    //
    $w = array(80, 50, 50);
    $pdf->SetFillColor(224);
    $pdf->SetFont('', 'B');
    $pdf->SetCellPadding(2);
    $pdf->Cell($w[0], 6, 'Employee', 1, 0, 'C', 1);
    $pdf->Cell($w[1], 6, 'Certification', 1, 0, 'C', 1);
    $pdf->Cell($w[2], 6, 'Expiration', 1, 0, 'C', 1);
    $pdf->Ln();
    $pdf->SetFillColor(236);
    $pdf->SetTextColor(0);
    $pdf->SetFont('');

    $fill=0;
    foreach($args['certs'] as $cert) {
        // Check if we need a page break
        if( $pdf->getY() > ($pdf->getPageHeight() - 30) ) {
            $pdf->AddPage();
            $pdf->SetFillColor(224);
            $pdf->SetFont('', 'B');
            $pdf->Cell($w[0], 6, 'Employee', 1, 0, 'C', 1);
            $pdf->Cell($w[1], 6, 'Certification', 1, 0, 'C', 1);
            $pdf->Cell($w[2], 6, 'Expiration', 1, 0, 'C', 1);
            $pdf->Ln();
            $pdf->SetFillColor(236);
            $pdf->SetTextColor(0);
            $pdf->SetFont('');
        }
        if( $cert['cert']['name'] != '' ) {
            $lh = 13;
            $certification = $cert['cert']['name'] . "\n" . $cert['cert']['date_received'];
            $expiration = $cert['cert']['expiry_text'] . "\n" . $cert['cert']['date_expiry'];
        } else {
            $lh = 6;
            $certification = '';
            $expiration = '';
        }
        $pdf->MultiCell($w[0], $lh, $cert['cert']['display_name'], 1, 'L', $fill, 
            0, '', '', true, 0, false, true, 0, 'T', false);
        $pdf->MultiCell($w[1], $lh, $certification, 1, 'L', $fill, 
            0, '', '', true, 0, false, true, 0, 'T', false);
        $pdf->MultiCell($w[2], $lh, $expiration, 1, 'L', $fill, 
            0, '', '', true, 0, false, true, 0, 'T', false);
        $pdf->Ln(); 
        $fill=!$fill;
    }


    return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
