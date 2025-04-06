<?php
session_start();
// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
     header("Location: ../index.php");
    exit();
}
require_once '../includes/db_connection.php';


// Get date range from form submission
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-01');
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d');

// Fetch sales data
$query = "SELECT s.*, 
          COUNT(si.id) as total_items,
          SUM(si.quantity) as total_quantity
          FROM sales s
          LEFT JOIN sale_items si ON s.id = si.sale_id
          WHERE DATE(s.sale_date) BETWEEN :start_date AND :end_date
          GROUP BY s.id
          ORDER BY s.sale_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([
    ':start_date' => $start_date,
    ':end_date' => $end_date
]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - TAROU MOTORS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <style>
        .summary-card {
            transition: transform 0.2s;
        }
        .summary-card:hover {
            transform: translateY(-5px);
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <h1 class="h3 mb-4">Sales Report</h1>
        
        <!-- Date Range Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <?php
        $total_sales = 0;
        $total_profit = 0;
        $total_transactions = count($result);

        foreach ($result as $row) {
            $total_sales += $row['total_amount'];
            $total_profit += $row['profit'];
        }
        ?>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Sales</h5>
                        <h3 class="mb-0">LKR <?php echo number_format($total_sales, 2); ?></h3>
                    </div>
                </div>
            </div>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="col-md-4">
                <div class="card bg-success text-white summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Profit</h5>
                        <h3 class="mb-0">LKR <?php echo number_format($total_profit, 2); ?></h3>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="col-md-4">
                <div class="card bg-info text-white summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Transactions</h5>
                        <h3 class="mb-0"><?php echo $total_transactions; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total Amount</th>
                                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <th>Profit</th>
                                <?php endif; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result as $row): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($row['sale_date'])); ?></td>
                                <td><?php echo $row['total_items']; ?> (<?php echo $row['total_quantity']; ?> units)</td>
                                <td>LKR <?php echo number_format($row['total_amount'], 2); ?></td>
                                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <td>LKR <?php echo number_format($row['profit'], 2); ?></td>
                                <?php endif; ?>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewInvoice(<?php echo $row['id']; ?>)">
                                        View Invoice
                                    </button>
                                </td>
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
        function viewInvoice(saleId) {
            // Open invoice in new window
            window.open(`generate_invoice.php?id=${saleId}`, '_blank');
        }
    </script>
</body>
</html>