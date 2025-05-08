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
               DATE_FORMAT(s.sale_date, '%Y-%m-%d') as formatted_date,
               s.customer_id
               FROM sales s 
               WHERE s.id = :sale_id";
$stmt = $pdo->prepare($sale_query);
$stmt->execute(['sale_id' => $sale_id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    die('Invoice not found');
}

// Fetch customer data if customer_id exists
$customer = null;
if (!empty($sale['customer_id'])) {
    $customer_query = "SELECT name, phone, email, address 
                      FROM customers 
                      WHERE id = :customer_id";
    $stmt = $pdo->prepare($customer_query);
    $stmt->execute(['customer_id' => $sale['customer_id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
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
            font-size: 0.85rem;
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
                padding: 8mm !important;
            }
        }

        /* Responsive styles for mobile */
        @media screen and (max-width: 767px) {
            body {
                width: 100%;
                height: auto;
            }
            .container {
                padding: 10px !important;
            }
            .logo-section, .company-title, .invoice-details, .company-contact, .customer-details {
                text-align: left !important;
                margin-bottom: 10px;
            }
        }

        .container {
            max-width: 210mm !important;
            padding: 8mm !important;
        }

        .invoice-header {
            padding: 0.5rem;
            margin-bottom: 1rem;
            position: relative;
        }

        .header-divider {
            height: 4px;
            background-color: #8B0000;
            margin-top: 0.5rem;
        }

        .logo-section {
            padding-right: 5px;
        }

        .company-title {
            color: #8B0000;
            font-weight: 700;
            font-size: 1.5rem;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.2;
        }

        .company-contact, .customer-details {
            font-size: 0.8rem;
            line-height: 1.3;
        }

        .company-contact p, .customer-details p {
            margin: 0;
        }

        .invoice-details {
            font-size: 0.8rem;
            line-height: 1.3;
        }

        .invoice-details p {
            margin: 0;
        }

        .company-logo {
            max-height: 60px; /* Reduced for compactness */
        }

        .table th {
            background-color: #f8f9fa;
            font-size: 0.85rem;
        }

        .table td {
            font-size: 0.85rem;
        }

        .total-section {
            background-color: #f8f9fa;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-top: 1.5rem;
        }

        .total-section table {
            font-size: 0.85rem;
        }

        .footer {
            margin-top: 2rem;
            padding-top: 0.75rem;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Print Button -->
        <div class="row mb-2 no-print">
            <div class="col-12">
                <button onclick="window.print()" class="btn btn-primary btn-sm float-end">
                    Print Invoice
                </button>
            </div>
        </div>

        <!-- Invoice Header -->
        <div class="invoice-header">
            <!-- Top Row: Logo + Company Title | Invoice Details -->
            <div class="row align-items-center mb-2">
                <div class="col-8">
                    <div class="d-flex align-items-center">
                        <div class="logo-section">
                            <img src="../assets/images/logo.png" alt="Kayel Auto Parts Logo" class="company-logo img-fluid">
                        </div>
                        <h1 class="company-title ms-2">KAYEL AUTO PARTS</h1>
                    </div>
                </div>
                <div class="col-4 text-end">
                    <div class="invoice-details">
                        <p><strong>Invoice #:</strong> <?php echo $sale_id; ?></p>
                        <p><strong>Date:</strong> <?php echo $sale['formatted_date']; ?></p>
                    </div>
                </div>
            </div>
            <!-- Bottom Row: Company Contact | Customer Details -->
            <div class="row">
                <div class="col-6">
                    <div class="company-contact">
                        <p>Dealer of All Japan, Indian & China Vehicle Parts</p>
                        <p>Kurunegala Road, Vithikuliya, Nikaweratiya</p>
                        <p>Hot Line: 077-9632277</p>
                    </div>
                </div>
                <div class="col-6 text-end">
                    <div class="customer-details">
                        <?php if ($customer): ?>
                            <p><strong><?php echo htmlspecialchars($customer['name']); ?></strong><?php echo !empty($customer['phone']) ? ' | ' . htmlspecialchars($customer['phone']) : ''; ?></p>
                            <?php if (!empty($customer['email'])): ?>
                                <p><?php echo htmlspecialchars($customer['email']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($customer['address'])): ?>
                                <p><?php echo htmlspecialchars($customer['address']); ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>No customer details</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="header-divider"></div>
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
        <div class="footer">
            <p class="text-muted mb-0">Thank you for your business!</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>