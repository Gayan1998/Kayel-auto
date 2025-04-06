<?php
include '../includes/db_connection.php';
session_start();

// Ensure no output before this point
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Set headers before any output
header('Content-Type: application/json');

try {
    // Get POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['items']) || empty($data['items'])) {
        throw new Exception('No items in sale');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Initialize totals
    $totalAmount = 0;
    $totalProfit = 0;

    // Create new sale record
    $stmt = $pdo->prepare("
        INSERT INTO sales (customer_id, total_amount, profit, sale_date, created_at, updated_at)
        VALUES (:customer_id, :total_amount, :profit, NOW(), NOW(), NOW())
    ");

    // Initially insert with 0 totals
    $stmt->execute([
        'customer_id' => null,
        'total_amount' => 0,
        'profit' => 0
    ]);

    $saleId = $pdo->lastInsertId();

    // Insert sale items
    $stmtItems = $pdo->prepare("
        INSERT INTO sale_items (sale_id, product_id, quantity, price, total_price, created_at, updated_at)
        VALUES (:sale_id, :product_id, :quantity, :price, :total_price, NOW(), NOW())
    ");

    // Process each item and calculate totals
    foreach ($data['items'] as $item) {
        $quantity = (int)$item['qty'];
        $sellingPrice = (float)$item['price'];
        $costPrice = (float)$item['cost'];
        
        // Calculate item totals
        $itemTotalPrice = $sellingPrice * $quantity;
        $itemProfit = ($sellingPrice - $costPrice) * $quantity;

        // Insert sale item
        $stmtItems->execute([
            'sale_id' => $saleId,
            'product_id' => $item['id'],
            'quantity' => $quantity,
            'price' => $sellingPrice,
            'total_price' => $itemTotalPrice
        ]);

        // Add to running totals
        $totalAmount += $itemTotalPrice;
        $totalProfit += $itemProfit;
    }

    // Update the sale with final totals
    $stmtUpdate = $pdo->prepare("
        UPDATE sales 
        SET total_amount = :total_amount,
            profit = :profit
        WHERE id = :sale_id
    ");

    $stmtUpdate->execute([
        'total_amount' => $totalAmount,
        'profit' => $totalProfit,
        'sale_id' => $saleId
    ]);

    foreach ($data['items'] as $item) {
        $stmtUpdateStock = $pdo->prepare("
            UPDATE products 
            SET quantity = quantity - :quantity
            WHERE id = :product_id
        ");

        $stmtUpdateStock->execute([
            'quantity' => $item['qty'],
            'product_id' => $item['id']
        ]);
    }

    // Commit transaction
    $pdo->commit();

    // Return success response
    $response = [
        'success' => true,
        'sale_id' => $saleId,
        'total_amount' => $totalAmount,
        'profit' => $totalProfit
    ];
    
    echo json_encode($response);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    
    echo json_encode($response);
}
?>