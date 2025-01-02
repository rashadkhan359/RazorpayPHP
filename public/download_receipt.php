<?php
ob_start();
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../vendor/autoload.php';

use TCPDF as PDF;

// Validate payment ID
$validator = validate($_GET, ['payment_id' => ['required', 'string']]);
if (!$validator->validate()) {
    ob_end_clean();
    die("Payment ID is missing or incorrect.");
}

$input = $validator->sanitized();
$paymentId = $input['payment_id'];
$transactionData = $GLOBALS['paymentProcessor']->getTransactionDetails($paymentId);

// Create new PDF document
$pdf = new PDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($GLOBALS['config']['app']['name']);
$pdf->SetTitle('Receipt #' . $paymentId);

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);

// Add a page
$pdf->AddPage();

// Set default font
$pdf->SetFont('helvetica', '', 10);

// Add logo - with proper error handling
$logoUrl = $GLOBALS['config']['app']['logo'];
if ($logoUrl) {
    // Convert relative URL to absolute if needed
    if (strpos($logoUrl, 'http') !== 0) {
        $logoUrl = $GLOBALS['config']['app']['url'] . '/' . ltrim($logoUrl, '/');
    }

    // Get image type from URL
    $imgInfo = getimagesize($logoUrl);
    if ($imgInfo) {
        $extension = image_type_to_extension($imgInfo[2], false);
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_logo') . '.' . $extension;

        // Download and save image
        $logoContent = file_get_contents($logoUrl);
        if ($logoContent) {
            file_put_contents($tempFile, $logoContent);
            try {
                $pdf->Image($tempFile, 15, 15, 40);
            } catch (Exception $e) {
                error_log("PDF Logo Error: " . $e->getMessage());
            }
            @unlink($tempFile); // Clean up temp file
        }
    }
}

// Company Information (right-aligned)
$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetXY(60, 15);
$pdf->Cell(135, 10, $GLOBALS['config']['app']['name'], 0, 1, 'R');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetX(60);
$pdf->Cell(135, 6, $GLOBALS['config']['app']['address'], 0, 1, 'R');
$pdf->SetX(60);
$pdf->Cell(135, 6, $GLOBALS['config']['app']['email'], 0, 1, 'R');

// Add a decorative line
$pdf->Ln(5);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(5);

// Receipt Title with invoice number
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'PAYMENT RECEIPT', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 6, '#' . $paymentId, 0, 1, 'C');
$pdf->Ln(5);

// Reset Y position for consistent starting point
$initialY = $pdf->GetY();

// Customer Information (if available)
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(95, 8, 'Bill To:', 0);
$pdf->Cell(95, 8, 'Receipt Details:', 0);
$pdf->Ln();

// Store Y position after headers
$contentStartY = $pdf->GetY();

$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(15, $contentStartY); // Reset to left margin
// Customer details (left column)
$pdf->MultiCell(95, 6,
    $transactionData['transaction']['name'] . "\n" .
    $transactionData['transaction']['email'] . "\n" .
    $transactionData['transaction']['address'] . "\n" .
    ($transactionData['transaction']['address2'] ? $transactionData['transaction']['address2'] . "\n" : '') .
    $transactionData['transaction']['city'] . ', ' . $transactionData['transaction']['zip_code'] . "\n" .
    $transactionData['transaction']['country'],
    0, 'L');

// Reset Y position for the right column
// $currentY = $pdf->GetY();
// $pdf->SetXY(110, $currentY - 30);
$pdf->SetXY(110, $contentStartY); // Move to right column

// Transaction details (right column)
$pdf->MultiCell(95, 6,
    'Date: ' . date('F j, Y, g:i a') . "\n" .
    'Status: ' . ucfirst($transactionData['transaction']['status']) . "\n" .
    'Payment Method: ' . ucfirst($transactionData['transaction']['payment_method']) . "\n" .
    ($transactionData['transaction']['card_network'] ? 'Card Network: ' . $transactionData['transaction']['card_network'] . "\n" : '') .
    ($transactionData['transaction']['card_last4'] ? 'Card: ****' . $transactionData['transaction']['card_last4'] . "\n" : ''),
    0, 'L');

// $pdf->Ln(10);

// // Add another decorative line
// $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
// $pdf->Ln(5);

// Find the lowest Y position between both columns
$leftColumnY = $pdf->GetY();
$pdf->SetXY(110, $contentStartY); // Reset to right column
$pdf->MultiCell(95, 6, $receiptDetails, 0, 'L');
$rightColumnY = $pdf->GetY();
$newY = max($leftColumnY, $rightColumnY);

// Add spacing after the sections
$pdf->SetY($newY + 20);

// Add decorative line
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(5);

// Payment Details in a nice table format
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('helvetica', 'B', 10);

// Table Header
$pdf->Cell(140, 8, 'Description', 1, 0, 'L', true);
$pdf->Cell(40, 8, 'Amount', 1, 1, 'R', true);

// Table Content
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(140, 8, 'Payment for Order #' . $transactionData['transaction']['order_id'], 1);
$pdf->Cell(40, 8, number_format($transactionData['transaction']['amount'], 2) . ' ' . $transactionData['transaction']['currency_type'], 1, 1, 'R');

// Total
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(140, 8, 'Total Amount', 1, 0, 'L', true);
$pdf->Cell(40, 8, number_format($transactionData['transaction']['amount'], 2) . ' ' . $transactionData['transaction']['currency_type'], 1, 1, 'R', true);

$pdf->Ln(10);

// Additional Notes
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'Note:', 0, 1);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(0, 5, "Thank you for choosing " . $GLOBALS['config']['app']['name'] . ". This is a computer-generated receipt and doesn't require a physical signature. For any queries, please contact our support team.", 0, 'L');

// QR Code with payment ID (optional)
$style = array(
    'border' => false,
    'padding' => 0,
    'fgcolor' => array(0, 0, 0),
    'bgcolor' => false
);
$pdf->write2DBarcode($GLOBALS['config']['app']['url'] . '/success.php?payment_id=' . $paymentId, 'QRCODE,L', 15, $pdf->GetY() + 5, 30, 30, $style);

ob_end_clean();
$pdf->Output('receipt_' . $paymentId . '.pdf', 'D');
exit;
