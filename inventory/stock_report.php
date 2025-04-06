<?php
session_start();
// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
require_once '../includes/db_connection.php';

// Filter variables
$category_filter = isset($_POST['category']) ? $_POST['category'] : '';
$search_query = isset($_POST['search']) ? $_POST['search'] : '';
$stock_status = isset($_POST['stock_status']) ? $_POST['stock_status'] : '';

// Get all categories for the filter dropdown
$categories_query = "SELECT DISTINCT category FROM products ORDER BY category";
$categories_stmt = $pdo->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);

// Build the query with filters
$query = "SELECT id, name, description, purchase_price, selling_price, quantity, 
          category, created_at, updated_at 
          FROM products 
          WHERE 1=1";

$params = [];

if (!empty($category_filter)) {
    $query .= " AND category = :category";
    $params[':category'] = $category_filter;
}

if (!empty($search_query)) {
    $query .= " AND (name LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search_query%";
}

if (!empty($stock_status)) {
    if ($stock_status == 'low') {
        $query .= " AND quantity <= 5 AND quantity > 0";
    } elseif ($stock_status == 'out') {
        $query .= " AND quantity = 0";
    } elseif ($stock_status == 'available') {
        $query .= " AND quantity > 5";
    }
}

$query .= " ORDER BY category, name";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary statistics
$total_products = count($products);
$total_value = 0;
$total_retail_value = 0;
$out_of_stock = 0;
$low_stock = 0;

foreach ($products as $product) {
    $total_value += $product['purchase_price'] * $product['quantity'];
    $total_retail_value += $product['selling_price'] * $product['quantity'];
    
    if ($product['quantity'] == 0) {
        $out_of_stock++;
    } elseif ($product['quantity'] <= 5) {
        $low_stock++;
    }
}

$potential_profit = $total_retail_value - $total_value;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Report - TAROU MOTORS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .summary-card {
            transition: transform 0.2s;
        }
        .summary-card:hover {
            transform: translateY(-5px);
            cursor: pointer;
        }
        .low-stock {
            background-color: #fff3cd;
        }
        .out-of-stock {
            background-color: #f8d7da;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <h1 class="h3 mb-4">Stock Report</h1>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>" <?php echo ($category_filter == $category) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Stock Status</label>
                        <select class="form-select" name="stock_status">
                            <option value="">All Status</option>
                            <option value="out" <?php echo ($stock_status == 'out') ? 'selected' : ''; ?>>Out of Stock</option>
                            <option value="low" <?php echo ($stock_status == 'low') ? 'selected' : ''; ?>>Low Stock (≤ 5)</option>
                            <option value="available" <?php echo ($stock_status == 'available') ? 'selected' : ''; ?>>Available (> 5)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search by name or description">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                        <a href="stock_report.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Products</h5>
                        <h3 class="mb-0"><?php echo $total_products; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Out of Stock</h5>
                        <h3 class="mb-0"><?php echo $out_of_stock; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Low Stock</h5>
                        <h3 class="mb-0"><?php echo $low_stock; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Inventory Value</h5>
                        <h3 class="mb-0">LKR <?php echo number_format($total_value, 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card bg-info text-white summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Retail Value</h5>
                        <h3 class="mb-0">LKR <?php echo number_format($total_retail_value, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-secondary text-white summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Potential Profit</h5>
                        <h3 class="mb-0">LKR <?php echo number_format($potential_profit, 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Export and Print Buttons -->
        <div class="mb-3">
            <button class="btn btn-success me-2" onclick="exportToCSV()">
                <i class="bi bi-file-earmark-excel"></i> Export to CSV
            </button>
            <button class="btn btn-secondary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Report
            </button>
        </div>

        <!-- Products Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Selling Price</th>
                                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <th>Purchase Price</th>
                                <th>Profit Margin</th>
                                <?php endif; ?>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): 
                                $row_class = '';
                                if ($product['quantity'] == 0) {
                                    $row_class = 'out-of-stock';
                                } elseif ($product['quantity'] <= 5) {
                                    $row_class = 'low-stock';
                                }
                                
                                $profit_margin = 0;
                                if ($product['purchase_price'] > 0) {
                                    $profit_margin = (($product['selling_price'] - $product['purchase_price']) / $product['purchase_price']) * 100;
                                }
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td><?php echo $product['id']; ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td>
                                    <?php echo $product['quantity']; ?>
                                    <?php if ($product['quantity'] == 0): ?>
                                        <span class="badge bg-danger">Out of Stock</span>
                                    <?php elseif ($product['quantity'] <= 5): ?>
                                        <span class="badge bg-warning text-dark">Low Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>LKR <?php echo number_format($product['selling_price'], 2); ?></td>
                                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <td>LKR <?php echo number_format($product['purchase_price'], 2); ?></td>
                                <td><?php echo number_format($profit_margin, 2); ?>%</td>
                                <?php endif; ?>
                                <td><?php echo date('Y-m-d', strtotime($product['updated_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportToCSV() {
            // Prepare data for CSV export
            let rows = [];
            const table = document.querySelector('table');
            const headerRow = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
            rows.push(headerRow);
            
            table.querySelectorAll('tbody tr').forEach(tr => {
                const row = Array.from(tr.querySelectorAll('td')).map(td => {
                    // Remove any HTML tags and trim the content
                    return td.textContent.replace(/(\r\n|\n|\r)/gm, "").trim();
                });
                rows.push(row);
            });
            
            // Convert to CSV format
            const csvContent = rows.map(row => row.join(',')).join('\n');
            
            // Create download link
            const encodedUri = encodeURI('data:text/csv;charset=utf-8,' + csvContent);
            const link = document.createElement('a');
            link.setAttribute('href', encodedUri);
            link.setAttribute('download', 'stock_report_' + new Date().toISOString().slice(0,10) + '.csv');
            document.body.appendChild(link);
            
            // Trigger download and remove link
            link.click();
            document.body.removeChild(link);
        }
        

    </script>
</body>
</html>