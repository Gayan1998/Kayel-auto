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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .summary-card {
            transition: transform 0.2s;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
            cursor: pointer;
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .card-title {
            font-size: 1rem;
            margin-bottom: 8px;
        }
        
        /* Mobile optimization */
        @media (max-width: 767px) {
            .container-fluid {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            h1.h3 {
                font-size: 1.5rem;
                text-align: center;
                margin-bottom: 15px;
            }
            
            .card-title {
                font-size: 0.9rem;
            }
            
            .card h3 {
                font-size: 1.3rem;
            }
            
            .summary-card {
                min-height: 100px;
                display: flex;
                align-items: center;
            }
            
            .summary-card .card-body {
                padding: 15px;
                text-align: center;
                width: 100%;
            }
            
            /* Button optimization for touch */
            .btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
                min-height: 44px; /* Minimum touch target size */
            }
            
            /* Table optimization */
            .table th, .table td {
                padding: 0.5rem;
                font-size: 0.85rem;
                vertical-align: middle;
            }
            
            /* Make invoice number and date columns smaller */
            .table th:nth-child(1), .table td:nth-child(1),
            .table th:nth-child(2), .table td:nth-child(2) {
                white-space: nowrap;
            }
            
            /* Improve visibility of amounts */
            .table td:nth-child(4) {
                font-weight: bold;
            }
            
            /* Ensure action buttons are visible */
            .table td:last-child {
                text-align: center;
            }
            
            .btn-sm {
                padding: 0.375rem 0.5rem;
                min-height: 38px;
                white-space: nowrap;
            }
            
            /* Filter form adjustments */
            label.form-label {
                font-size: 0.9rem;
                margin-bottom: 0.25rem;
            }
            
            .filter-card {
                margin-bottom: 15px;
            }
            
            .form-control {
                font-size: 0.9rem;
                padding: 0.375rem 0.5rem;
                min-height: 40px;
            }
        }
        
        /* Quick stats for small screens */
        @media (max-width: 576px) {
            .stats-row {
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
            }
            
            .stats-col {
                flex: 0 0 48%;
                max-width: 48%;
            }
            
            .full-width-col {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .mobile-action-menu {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
                padding: 10px;
                display: flex;
                justify-content: space-around;
                z-index: 1000;
            }
            
            .mobile-back-btn {
                position: fixed;
                top: 10px;
                left: 10px;
                z-index: 1000;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: #f8f9fa;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            
            body {
                padding-bottom: 70px; /* Add space for mobile menu */
            }
        }
        
        /* Data visualization styles */
        .chart-container {
            height: 300px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 767px) {
            .chart-container {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile back button -->
    <a href="../pos/index.php" class="mobile-back-btn d-md-none">
        <i class="fas fa-arrow-left"></i>
    </a>

    <div class="container-fluid py-4">
        <h1 class="h3 mb-4">Sales Report</h1>
        
        <!-- Date Range Filter -->
        <div class="card mb-4 filter-card">
            <div class="card-body">
                <form method="POST" class="row g-2">
                    <div class="col-12 col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-12 col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
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

        <!-- For mobile layout -->
        <div class="row stats-row mb-4">
            <div class="col-md-4 <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'col-6' : 'col-12'; ?>">
                <div class="card bg-primary text-white summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Sales</h5>
                        <h3 class="mb-0">LKR <?php echo number_format($total_sales, 2); ?></h3>
                    </div>
                </div>
            </div>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="col-md-4 col-6">
                <div class="card bg-success text-white summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Profit</h5>
                        <h3 class="mb-0">LKR <?php echo number_format($total_profit, 2); ?></h3>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="col-md-4 <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'col-12' : 'col-12'; ?>">
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
                                <th>#</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <th>Profit</th>
                                <?php endif; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($result) > 0): ?>
                                <?php foreach ($result as $row): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($row['sale_date'])); ?></td>
                                    <td>
                                        <span class="d-none d-md-inline"><?php echo $row['total_items']; ?> (<?php echo $row['total_quantity']; ?> units)</span>
                                        <span class="d-md-none"><?php echo $row['total_quantity']; ?> pcs</span>
                                    </td>
                                    <td>LKR <?php echo number_format($row['total_amount'], 2); ?></td>
                                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                    <td>LKR <?php echo number_format($row['profit'], 2); ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="viewInvoice(<?php echo $row['id']; ?>)">
                                            <i class="fas fa-eye d-md-none"></i>
                                            <span class="d-none d-md-inline">View Invoice</span>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? '6' : '5'; ?>" class="text-center py-3">
                                        No sales data found for the selected period
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile action menu -->
    <div class="mobile-action-menu d-md-none">
        <a href="../pos/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-home"></i> POS
        </a>
        <a href="../inventory/stock_report.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-boxes"></i> Stock
        </a>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="fas fa-print"></i> Print
        </button>
        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#filterModal">
            <i class="fas fa-filter"></i> Filter
        </button>
    </div>

    <!-- Filter Modal for Mobile -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Sales</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="mobileFilterForm">
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('mobileFilterForm').submit()">Apply Filter</button>
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

        // Mobile optimizations
        document.addEventListener('DOMContentLoaded', function() {
            // Check if we're on a mobile device
            const isMobile = window.innerWidth < 768;
            
            if (isMobile) {
                // Make table headers sticky for better scrolling on mobile
                const tableHeaders = document.querySelector('thead tr');
                if (tableHeaders) {
                    tableHeaders.style.position = 'sticky';
                    tableHeaders.style.top = '0';
                    tableHeaders.style.backgroundColor = '#fff';
                    tableHeaders.style.zIndex = '10';
                }
                
                // Add touch feedback to rows
                const tableRows = document.querySelectorAll('tbody tr');
                tableRows.forEach(row => {
                    row.addEventListener('touchstart', () => {
                        row.style.backgroundColor = '#f8f9fa';
                    });
                    row.addEventListener('touchend', () => {
                        setTimeout(() => {
                            row.style.backgroundColor = '';
                        }, 200);
                    });
                });
            }
        });
    </script>
</body>
</html>