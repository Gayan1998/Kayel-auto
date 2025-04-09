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

    // Calculate totals upfront to avoid recalculation
    $totalAmount = 0;
    $totalProfit = 0;
    
    foreach ($data['items'] as $item) {
        $quantity = (int)$item['qty'];
        $sellingPrice = (float)$item['price'];
        $costPrice = (float)$item['cost'];
        
        $itemTotalPrice = $sellingPrice * $quantity;
        $itemProfit = ($sellingPrice - $costPrice) * $quantity;
        
        $totalAmount += $itemTotalPrice;
        $totalProfit += $itemProfit;
    }

    // Extract customer data if present
    $customerId = isset($data['customer_id']) ? $data['customer_id'] : null;

    // Create new sale record with final totals immediately
    $stmt = $pdo->prepare("
        INSERT INTO sales (customer_id, total_amount, profit, sale_date, created_at, updated_at)
        VALUES (:customer_id, :total_amount, :profit, NOW(), NOW(), NOW())
    ");

    $stmt->execute([
        'customer_id' => $customerId,
        'total_amount' => $totalAmount,
        'profit' => $totalProfit
    ]);

    $saleId = $pdo->lastInsertId();
    
    // Prepare batch insert for sale items
    $itemValues = [];
    $placeholders = [];
    $stockUpdates = [];
    $index = 1;
    
    foreach ($data['items'] as $item) {
        $quantity = (int)$item['qty'];
        $sellingPrice = (float)$item['price'];
        $itemTotalPrice = $sellingPrice * $quantity;
        
        // Add placeholders for this item
        $placeholders[] = "(:sale_id, :product_id{$index}, :quantity{$index}, :price{$index}, :total_price{$index}, NOW(), NOW())";
        
        // Add parameter values
        $itemValues[":product_id{$index}"] = $item['id'];
        $itemValues[":quantity{$index}"] = $quantity;
        $itemValues[":price{$index}"] = $sellingPrice;
        $itemValues[":total_price{$index}"] = $itemTotalPrice;
        
        // Track stock updates
        $stockUpdates[] = [
            'product_id' => $item['id'],
            'quantity' => $quantity
        ];
        
        $index++;
    }
    
    // Add the sale_id to the parameters
    $itemValues[':sale_id'] = $saleId;
    
    // Execute batch insert for sale items if there are items
    if (!empty($placeholders)) {
        $placeholderStr = implode(', ', $placeholders);
        $sqlItems = "INSERT INTO sale_items (sale_id, product_id, quantity, price, total_price, created_at, updated_at) 
                     VALUES {$placeholderStr}";
        
        $stmtItems = $pdo->prepare($sqlItems);
        $stmtItems->execute($itemValues);
    }
    
    // Batch update stock with a single query using CASE statement
    if (!empty($stockUpdates)) {
        $cases = "";
        $productIds = [];
        
        foreach ($stockUpdates as $index => $update) {
            $paramName = ":product_id{$index}";
            $quantityName = ":quantity{$index}";
            
            $cases .= " WHEN id = {$paramName} THEN quantity - {$quantityName}";
            $productIds[$paramName] = $update['product_id'];
            $productIds[$quantityName] = $update['quantity'];
        }
        
        $updateProductIds = implode(',', array_map(function($index) { 
            return ":product_id{$index}"; 
        }, array_keys($stockUpdates)));
        
        $sqlStock = "UPDATE products SET quantity = CASE {$cases} ELSE quantity END 
                     WHERE id IN ({$updateProductIds})";
        
        $stmtStock = $pdo->prepare($sqlStock);
        $stmtStock->execute($productIds);
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