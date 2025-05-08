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
$include_returns = isset($_POST['include_returns']) ? true : true; // Default to including returns

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

// Fetch returns data for the same period
$returns_query = "SELECT r.*, r.sale_id, 
                 DATE_FORMAT(r.return_date, '%Y-%m-%d') as formatted_date
                 FROM returns r
                 WHERE DATE(r.return_date) BETWEEN :start_date AND :end_date
                 ORDER BY r.return_date DESC";
                 
$stmt = $pdo->prepare($returns_query);
$stmt->execute([
    ':start_date' => $start_date,
    ':end_date' => $end_date
]);
$returns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create a lookup of returns by sale_id for easy access
$returns_by_sale = [];
$total_returns_amount = 0;
$total_returns_count = count($returns);

foreach ($returns as $return) {
    if (!isset($returns_by_sale[$return['sale_id']])) {
        $returns_by_sale[$return['sale_id']] = [];
    }
    $returns_by_sale[$return['sale_id']][] = $return;
    $total_returns_amount += $return['total_amount'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - KAYEL AUTO PARTS</title>
    <link rel="icon" href="../assets/images/logo.png" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/images/logo.png" type="image/x-icon">
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
        
        /* Return styling */
        .return-badge {
            background-color: #dc3545;
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            display: inline-block;
            margin-left: 5px;
        }
        
        .returned-row {
            background-color: rgba(220, 53, 69, 0.05);
        }
        
        .net-amount {
            font-weight: bold;
            color: #28a745;
        }
        
        .return-amount {
            color: #dc3545;
            font-weight: bold;
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
    <a href="../admin/dashboard.php" class="mobile-back-btn d-md-none">
        <i class="fas fa-arrow-left"></i>
    </a>

    <div class="container-fluid py-4">
        <h1 class="h3 mb-4">Sales Report</h1>
        
        <!-- Date Range Filter -->
        <div class="card mb-4 filter-card">
            <div class="card-body">
                <form method="POST" class="row g-2">
                    <div class="col-12 col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Options</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_returns" id="include_returns" <?php echo $include_returns ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="include_returns">
                                Account for Returns
                            </label>
                        </div>
                    </div>
                    <div class="col-12 col-md-3 d-flex align-items-end">
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
        $net_sales = 0;
        $net_profit = 0;

        foreach ($result as $row) {
            $total_sales += $row['total_amount'];
            $total_profit += $row['profit'];
            
            // Calculate net figures by subtracting returns
            $sale_returns_total = 0;
            if (isset($returns_by_sale[$row['id']])) {
                foreach ($returns_by_sale[$row['id']] as $return) {
                    $sale_returns_total += $return['total_amount'];
                }
            }
            
            $net_sales += ($row['total_amount'] - $sale_returns_total);
            
            // Estimate the profit reduction for returns
            // This is an approximation since we don't track profit per return
            if ($row['total_amount'] > 0) {
                $profit_ratio = $row['profit'] / $row['total_amount'];
                $estimated_return_profit = $sale_returns_total * $profit_ratio;
                $net_profit += ($row['profit'] - $estimated_return_profit);
            }
        }
        ?>

        <!-- For mobile layout -->
        <div class="row stats-row mb-4">
            <div class="col-md-4 col-6">
                <div class="card bg-primary text-white summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Gross Sales</h5>
                        <h3 class="mb-0">LKR <?php echo number_format($total_sales, 2); ?></h3>
                    </div>
                </div>
            </div>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="col-md-4 col-6">
                <div class="card bg-success text-white summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Gross Profit</h5>
                        <h3 class="mb-0">LKR <?php echo number_format($total_profit, 2); ?></h3>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="col-md-4 <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'col-12' : 'col-6'; ?>">
                <div class="card bg-info text-white summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Transactions</h5>
                        <h3 class="mb-0"><?php echo $total_transactions; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if($include_returns && $total_returns_count > 0): ?>
        <div class="row stats-row mb-4">
            <div class="col-md-4 col-6">
                <div class="card bg-danger text-white summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Returns</h5>
                        <h3 class="mb-0">LKR <?php echo number_format($total_returns_amount, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <div class="card bg-success text-white summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Net Sales</h5>
                        <h3 class="mb-0">LKR <?php echo number_format($net_sales, 2); ?></h3>
                    </div>
                </div>
            </div>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="col-md-4 col-12">
                <div class="card bg-warning text-dark summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Net Profit</h5>
                        <h3 class="mb-0">LKR <?php echo number_format($net_profit, 2); ?></h3>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

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
                                <?php if($include_returns): ?>
                                <th>Returns</th>
                                <th>Net Amount</th>
                                <?php endif; ?>
                                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <th>Profit</th>
                                <?php endif; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($result) > 0): ?>
                                <?php foreach ($result as $row): 
                                    $has_returns = isset($returns_by_sale[$row['id']]);
                                    $row_class = $has_returns ? 'returned-row' : '';
                                    $returns_total = 0;
                                    
                                    if ($has_returns) {
                                        foreach ($returns_by_sale[$row['id']] as $return) {
                                            $returns_total += $return['total_amount'];
                                        }
                                    }
                                    
                                    $net_amount = $row['total_amount'] - $returns_total;
                                ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <td>
                                        <?php echo $row['id']; ?>
                                        <?php if ($has_returns): ?>
                                        <span class="return-badge">R</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($row['sale_date'])); ?></td>
                                    <td>
                                        <span class="d-none d-md-inline"><?php echo $row['total_items']; ?> (<?php echo $row['total_quantity']; ?> units)</span>
                                        <span class="d-md-none"><?php echo $row['total_quantity']; ?> pcs</span>
                                    </td>
                                    <td>LKR <?php echo number_format($row['total_amount'], 2); ?></td>
                                    <?php if($include_returns): ?>
                                    <td class="<?php echo $returns_total > 0 ? 'return-amount' : ''; ?>">
                                        <?php if($returns_total > 0): ?>
                                        -LKR <?php echo number_format($returns_total, 2); ?>
                                        <?php else: ?>
                                        -
                                        <?php endif; ?>
                                    </td>
                                    <td class="net-amount">LKR <?php echo number_format($net_amount, 2); ?></td>
                                    <?php endif; ?>
                                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                    <td>LKR <?php echo number_format($row['profit'], 2); ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-primary" onclick="viewInvoice(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-eye d-md-none"></i>
                                                <span class="d-none d-md-inline">View Invoice</span>
                                            </button>
                                            <?php if($has_returns): ?>
                                            <button class="btn btn-sm btn-danger" onclick="viewReturns(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-undo-alt d-md-none"></i>
                                                <span class="d-none d-md-inline">View Returns</span>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo $include_returns ? (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? '8' : '7') : (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? '6' : '5'); ?>" class="text-center py-3">
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
        <a href="../admin/dashboard.php" class="btn btn-outline-secondary btn-sm">
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
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="include_returns" id="mobile_include_returns" <?php echo $include_returns ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="mobile_include_returns">
                                    Account for Returns
                                </label>
                            </div>
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

    <!-- Returns Modal -->
    <div class="modal fade" id="returnsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Returns for Invoice #<span id="returnModalSaleId"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="returnsModalContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
        
        function viewReturns(saleId) {
            $('#returnModalSaleId').text(saleId);
            $('#returnsModal').modal('show');
            
            // Load returns data
            $.ajax({
                url: 'get_sale_returns.php',
                type: 'GET',
                data: {sale_id: saleId},
                success: function(data) {
                    $('#returnsModalContent').html(data);
                },
                error: function() {
                    $('#returnsModalContent').html('<div class="alert alert-danger">Error loading returns data</div>');
                }
            });
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