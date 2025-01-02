<?php

use App\Controllers\OrderController;
use App\Controllers\PagesController;
use App\Controllers\PaymentController;
use App\Controllers\ReceiptController;



// Register routes
$route->get('/', );
$route->post('/verify-payment', [PaymentController::class, 'verifyPayment']);
$route->get('/policy', [PagesController::class, 'policy']);
$router->post('/create-order', [OrderController::class, 'create']);
$router->get('/download-receipt/html/{payment_id}', [ReceiptController::class, 'downloadHTML']);
$router->get('/download-receipt/pdf/{payment_id}', [ReceiptController::class, 'downloadPDF']);
