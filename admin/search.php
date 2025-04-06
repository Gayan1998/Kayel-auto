<?php
include '../includes/db_connection.php';
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

try {
    $searchTerm = isset($_GET['term']) ? $_GET['term'] : '';
    
    if (strlen($searchTerm) >= 2) {
        $stmt = $pdo->prepare("
            SELECT * 
            FROM products 
            WHERE name LIKE :term 
            ORDER BY name
            LIMIT 10
        ");
        
        $stmt->execute(['term' => '%' . $searchTerm . '%']);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($results);
    } else {
        echo json_encode([]);
    }
} catch(PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error occurred']);
}
?>