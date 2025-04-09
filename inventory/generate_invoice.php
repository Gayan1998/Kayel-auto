<?php
session_start();
// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../admin/login.php");
    exit();
}
include '../includes/db_connection.php';

if (!isset($_GET['id'])) {
    die('No invoice ID provided');
}

$sale_id = intval($_GET['id']);

// Fetch sale data
$sale_query = "SELECT s.*, 
               DATE_FORMAT(s.sale_date, '%Y-%m-%d') as formatted_date
               FROM sales s 
               WHERE s.id = :sale_id";
$stmt = $pdo->prepare($sale_query);
$stmt->execute(['sale_id' => $sale_id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    die('Invoice not found');
}

// Fetch sale items
$items_query = "SELECT si.*, p.name as product_name 
                FROM sale_items si 
                JOIN products p ON si.product_id = p.id 
                WHERE si.sale_id = :sale_id";
$stmt = $pdo->prepare($items_query);
$stmt->execute(['sale_id' => $sale_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['total_price'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* A4 page size settings */
        body {
            width: 210mm;
            height: 297mm;
            margin: 0 auto;
            padding: 0;
            font-size: 0.9rem;
        }
        
        @media print {
            @page {
                size: A4;
                margin: 10mm;
            }
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
                width: 100%;
                height: auto;
            }
            .no-print {
                display: none !important;
            }
            .container {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 10mm !important;
            }
        }

        .invoice-header {
            background-color: #f8f9fa;
            padding: 1rem; /* Reduced padding from 2rem to 1rem */
            border-radius: 0.5rem;
            margin-bottom: 1.5rem; /* Reduced margin from 2rem to 1.5rem */
        }

        .company-details {
            margin-bottom: 0.5rem; /* Further reduced margin for A4 */
        }

        .invoice-title {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 0.25rem;
            font-size: 1.4rem; /* Slightly reduced font size for A4 */
        }

        .company-info p {
            margin-bottom: 0.25rem; /* Reduced paragraph margins */
            font-size: 0.85rem; /* Smaller font size for company info */
            line-height: 1.2; /* Tighter line height */
        }

        .table th {
            background-color: #f8f9fa;
        }

        .total-section {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 2rem;
        }

        .footer {
            margin-top: 3rem;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container mt-3 mb-3"> <!-- Adjusted for A4 size -->
        <!-- Print Button -->
        <div class="row mb-3 no-print"> <!-- Reduced margin -->
            <div class="col-12">
                <button onclick="window.print()" class="btn btn-primary float-end">
                    Print Invoice
                </button>
            </div>
        </div>

        <!-- Invoice Header -->
        <div class="invoice-header">
            <div class="row">
                <div class="col-md-6 company-details company-info">
                    <h1 class="invoice-title">YOHIMURA Auto</h1>
                    <p class="text-muted">
                        Dealers in Japanese Vehicle | Body Parts & Machinery | Bike Spare Parts
                    </p>
                    <div class="row">
                        <div class="col-12">
                            <p>Puttalam Road, Nikaweratiya</p>
                            <p>Tel: 077 720 7573 | 077 778 6876</p>
                            <p>Tel (Japan): +81 90 9181 7573 | +81 80 6914 5435</p>
                            <p>Email: yoshimuraauto88@gmail.com | <strong>Reg No:</strong> 11/3467</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <h2 class="text-uppercase text-muted" style="font-size: 1.25rem;">Invoice</h2>
                    <p class="mb-1" style="font-size: 0.9rem;"><strong>Invoice #:</strong> <?php echo $sale_id; ?></p>
                    <p class="mb-1" style="font-size: 0.9rem;"><strong>Date:</strong> <?php echo $sale['formatted_date']; ?></p>
                </div>
            </div>
        </div>

        <!-- Invoice Items -->
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item Description</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                        <td class="text-end">LKR <?php echo number_format($item['price'], 2); ?></td>
                        <td class="text-end">LKR <?php echo number_format($item['total_price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Total Section -->
        <div class="row">
            <div class="col-md-6 offset-md-6">
                <div class="total-section">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="text-end"><strong>Sub Total:</strong></td>
                            <td class="text-end">LKR <?php echo number_format($subtotal, 2); ?></td>
                        </tr>
                        <tr>
                            <td class="text-end"><strong>Total Amount:</strong></td>
                            <td class="text-end"><strong>LKR <?php echo number_format($sale['total_amount'], 2); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer text-center">
            <p class="text-muted mb-0">Thank you for your business!</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>