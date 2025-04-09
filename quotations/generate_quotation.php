<?php
session_start();
// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
include '../includes/db_connection.php';

if (!isset($_GET['id'])) {
    die('No quotation ID provided');
}

$quotation_id = intval($_GET['id']);

// Fetch quotation data
$quote_query = "SELECT q.*, 
               DATE_FORMAT(q.quote_date, '%Y-%m-%d') as formatted_date,
               DATE_FORMAT(q.valid_until, '%Y-%m-%d') as formatted_valid_until,
               c.name as customer_name, c.phone as customer_phone, 
               c.email as customer_email, c.address as customer_address
               FROM quotations q 
               LEFT JOIN customers c ON q.customer_id = c.id
               WHERE q.id = :quotation_id";
$stmt = $pdo->prepare($quote_query);
$stmt->execute(['quotation_id' => $quotation_id]);
$quotation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quotation) {
    die('Quotation not found');
}

// Check if expired
$isExpired = strtotime($quotation['valid_until']) < time() && $quotation['status'] === 'pending';
if ($isExpired) {
    // Update status to expired
    $updateStmt = $pdo->prepare("UPDATE quotations SET status = 'expired' WHERE id = ? AND status = 'pending'");
    $updateStmt->execute([$quotation_id]);
    $quotation['status'] = 'expired';
}

// Fetch quotation items
$items_query = "SELECT qi.*, p.name as product_name 
                FROM quotation_items qi 
                JOIN products p ON qi.product_id = p.id 
                WHERE qi.quotation_id = :quotation_id";
$stmt = $pdo->prepare($items_query);
$stmt->execute(['quotation_id' => $quotation_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['total_price'];
}

// Get status label and class
$statusLabels = [
    'pending' => 'Pending',
    'accepted' => 'Accepted',
    'rejected' => 'Rejected',
    'expired' => 'Expired',
    'converted' => 'Converted to Sale'
];

$statusClass = [
    'pending' => 'primary',
    'accepted' => 'success',
    'rejected' => 'danger',
    'expired' => 'secondary',
    'converted' => 'info'
];

$status = $isExpired ? 'expired' : $quotation['status'];
$statusLabel = $statusLabels[$status] ?? 'Unknown';
$statusColorClass = $statusClass[$status] ?? 'secondary';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation #<?php echo $quotation_id; ?> - YOSHIMURA Auto</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
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

        .quotation-header {
            background-color: #f8f9fa;
            padding: 1rem; /* Reduced padding from 2rem to 1rem */
            border-radius: 0.5rem;
            margin-bottom: 1.5rem; /* Reduced margin from 2rem to 1.5rem */
        }

        .company-details {
            margin-bottom: 0.5rem; /* Further reduced margin for A4 */
        }

        .quotation-title {
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

        .customer-info {
            background-color: #f8f9fa;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
            border-radius: 0.25rem;
        }

        .table th {
            background-color: #f8f9fa;
        }

        .total-section {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 1.5rem;
        }

        .validity-note {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }

        .footer {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }

        .terms-section {
            font-size: 0.8rem;
            margin-top: 2rem;
            padding: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container mt-3 mb-3"> <!-- Adjusted for A4 size -->
        <!-- Print Button -->
        <div class="row mb-3 no-print"> <!-- Reduced margin -->
            <div class="col-12">
                <button onclick="window.print()" class="btn btn-primary float-end">
                    Print Quotation
                </button>
                <a href="view_quotations.php" class="btn btn-secondary me-2 float-end">Back to Quotations</a>
            </div>
        </div>

        <!-- Quotation Header -->
        <div class="quotation-header">
            <div class="row">
                <div class="col-md-6 company-details company-info">
                    <h1 class="quotation-title">YOHIMURA Auto</h1>
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
                    <h2 class="text-uppercase text-muted" style="font-size: 1.25rem;">Quotation</h2>
                    <p class="mb-1" style="font-size: 0.9rem;"><strong>Quotation #:</strong> <?php echo $quotation_id; ?></p>
                    <p class="mb-1" style="font-size: 0.9rem;"><strong>Date:</strong> <?php echo $quotation['formatted_date']; ?></p>
                    <p class="mb-1" style="font-size: 0.9rem;"><strong>Valid Until:</strong> <?php echo $quotation['formatted_valid_until']; ?></p>
                    <p class="mb-0">
                        <span class="status-badge bg-<?php echo $statusColorClass; ?> text-white">
                            <?php echo $statusLabel; ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Customer Information -->
        <?php if ($quotation['customer_id']): ?>
        <div class="customer-info">
            <h5 class="mb-2">Customer Information</h5>
            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($quotation['customer_name']); ?></p>
            <?php if (!empty($quotation['customer_phone'])): ?>
            <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($quotation['customer_phone']); ?></p>
            <?php endif; ?>
            <?php if (!empty($quotation['customer_email'])): ?>
            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($quotation['customer_email']); ?></p>
            <?php endif; ?>
            <?php if (!empty($quotation['customer_address'])): ?>
            <p class="mb-0"><strong>Address:</strong> <?php echo htmlspecialchars($quotation['customer_address']); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Quotation Items -->
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
                            <td class="text-end"><strong>LKR <?php echo number_format($quotation['total_amount'], 2); ?></strong></td>
                        </tr>
                    </table>
                    <p class="validity-note mb-0">
                        This quotation is valid until <?php echo $quotation['formatted_valid_until']; ?>
                        <?php if ($isExpired || $quotation['status'] === 'expired'): ?>
                        <span class="text-danger">(Expired)</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Terms and Conditions -->
        <div class="terms-section">
            <h6>Terms and Conditions</h6>
            <ol class="mb-0">
                <li>This quotation is valid for 14 days from the date of issue.</li>
                <li>Prices are subject to change without prior notice after the validity period.</li>
                <li>Payment terms: 100% payment upon confirmation of order.</li>
                <li>Delivery timeline will be confirmed upon receiving the order.</li>
                <li>Product specifications may vary slightly from the description provided.</li>
                <li>Warranty terms as per manufacturer's policy.</li>
            </ol>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="row">
                <div class="col-md-12 text-center">
                    <p class="text-muted mb-2">This is a computer generated document. No signature required.</p>
                    <p class="text-muted mb-0">Thank you for your business!</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>