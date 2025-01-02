<?php
require __DIR__ . '/../bootstrap.php';

// Check if payment_id is provided
if (!isset($_GET['payment_id'])) {
    die("Payment ID is missing.");
}

$paymentId = htmlspecialchars($_GET['payment_id']);

// Assuming you have the same method for retrieving transaction details
$transactionData = $GLOBALS['paymentProcessor']->getTransactionDetails($paymentId);

// Company Information (adjust as necessary)
$companyName = $GLOBALS['config']['app']['name'];
$companyLogoUrl = $GLOBALS['config']['app']['logo'];
$companyAddress = $GLOBALS['config']['app']['address'];
$companyEmail = $GLOBALS['config']['app']['email'];

// Generate the HTML content for the receipt
$receiptHtml = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Receipt - {$companyName}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header img {
            width: 150px;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 28px;
            margin: 10px 0;
            color: #333;
        }
        .header p {
            font-size: 14px;
            color: #777;
        }
        .receipt-details {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .receipt-details p {
            margin: 5px 0;
            font-size: 16px;
        }
        .receipt-details .bold {
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #777;
        }
        .footer a {
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body>

    <div class='container'>
        <div class='header'>
            <img src='{$companyLogoUrl}' alt='Company Logo'>
            <h1>{$companyName}</h1>
            <p>{$companyAddress}</p>
            <p>{$companyEmail}</p>
        </div>

        <div class='receipt-details'>
            <p><span class='bold'>Transaction ID:</span> {$paymentId}</p>
            <p><span class='bold'>Amount Paid:</span> {$transactionData['transaction']['amount']} {$transactionData['transaction']['currency_type']}</p>
            <p><span class='bold'>Payment Method:</span> {$transactionData['transaction']['payment_method']}</p>
            <p><span class='bold'>Status:</span> {$transactionData['transaction']['status']}</p>
            <p><span class='bold'>Date:</span> " . date("F j, Y, g:i a") . "</p>
        </div>

        <div class='footer'>
            <p>Thank you for choosing {$companyName}!</p>
            <p><a href='mailto:{$companyEmail}'>Contact Support</a></p>
        </div>
    </div>

</body>
</html>
";

// Set the headers to download the receipt as an HTML file (could also be set as a PDF later)
header('Content-Type: text/html');
header('Content-Disposition: attachment; filename="receipt_' . $paymentId . '.html"');

// Output the HTML content
echo $receiptHtml;
exit;
?>
