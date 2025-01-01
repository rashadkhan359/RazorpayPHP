<?php
$pageTitle = "Payment Successful";
include '../templates/layout.php';
?>

<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6 text-center">
    <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
    </svg>

    <h1 class="text-2xl font-bold mb-4">Payment Successful!</h1>
    <p class="text-gray-600 mb-4">Thank you for your payment. Your transaction ID is:</p>
    <p class="text-blue-600 font-mono bg-gray-100 p-2 rounded">
        <?php echo htmlspecialchars($_GET['payment_id']); ?>
    </p>

    <div class="mt-6">
        <a href="index.php" class="text-blue-600 hover:underline">Return to Homepage</a>
    </div>
</div>
<?php include '../templates/footer.php' ?>
