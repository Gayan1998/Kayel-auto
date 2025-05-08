<?php
include '../includes/db_connection.php'; 
session_start();
// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Fetch existing products
$stmt = $pdo->query("SELECT * FROM products ORDER BY name");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing customers
$stmt = $pdo->query("SELECT id, name, phone, email, address FROM customers ORDER BY name");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="icon" href="../assets/images/logo.png" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/images/logo.png" type="image/x-icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet">
    <style>
        .btn-remove {
            background-color: transparent;
            border: none;
            color: var(--accent-red);
            padding: 0.5rem;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-remove:hover {
            opacity: 0.8;
        }

        :root {
            --dark-bg: #1a1a1a;
            --darker-bg: #141414;
            --card-bg: #242424;
            --border-color: #333;
            --text-primary: #fff;
            --text-secondary: #a0a0a0;
            --accent-green: #4ade80;
            --accent-blue: #60a5fa;
            --accent-red: #f87171;
            --accent-yellow: #b6e134;
            --accent-purple: #a78bfa;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
            overflow-x: hidden;
        }

        /* Mobile-first approach: content stacks by default */
        .content-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* For tablets and larger */
        @media (min-width: 768px) {
            .content-wrapper {
                display: grid;
                grid-template-columns: 1fr;
                gap: 1rem;
                min-height: calc(100vh - 2rem);
            }
        }

        /* For desktops */
        @media (min-width: 992px) {
            body {
                overflow: hidden;
            }
            .content-wrapper {
                display: grid;
                grid-template-columns: 1fr 300px;
                gap: 1rem;
                height: calc(100vh - 2rem);
            }
        }

        .main-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .search-bar {
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            width: 100%;
            margin-bottom: 1rem;
        }

        .search-bar:focus {
            outline: none;
            border-color: var(--accent-blue);
        }

        .table {
            color: var(--text-primary);
            margin: 0;
        }

        .table th {
            background-color: var(--darker-bg);
            color: var(--text-secondary);
            font-weight: 500;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        /* Make the table responsive */
        @media (max-width: 767px) {
            .table-container {
                overflow-x: auto;
            }
            
            .table th, .table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.9rem;
            }
            
            .table th:nth-child(3), 
            .table td:nth-child(3) {
                width: 60px;
            }
            
            .table th:nth-child(4), 
            .table td:nth-child(4) {
                width: 70px;
            }
            
            .table th:nth-child(5), 
            .table td:nth-child(5) {
                width: 40px;
            }
        }

        .editable-cell {
            background-color: transparent;
            border: none;
            padding: 0.25rem 2.5rem 0.25rem 0.25rem;
            width: 120px;
            color: rgb(0, 0, 0);
            text-align: right;
            font-size: 1rem;
            appearance: textfield;
            position: relative;
        }

        @media (max-width: 767px) {
            .editable-cell {
                width: 80px;
                padding: 0.25rem 2rem 0.25rem 0.25rem;
                font-size: 0.9rem;
            }
        }

        /* Style for Webkit browsers (Chrome, Safari) */
        .editable-cell::-webkit-outer-spin-button,
        .editable-cell::-webkit-inner-spin-button {
            opacity: 1;
            margin-left: 10px;
            height: 24px;
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
        }

        /* Style for Firefox */
        .editable-cell[type="number"] {
            -moz-appearance: textfield;
            padding-right: 2.5rem;
        }

        .editable-cell:focus {
            outline: none;
            border-radius: 4px;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 767px) {
            .action-buttons {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .action-buttons {
                grid-template-columns: 1fr;
            }
        }

        .btn-pay, .btn-print, .btn-quotation, .btn-cancel, .btn-logout {
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 500;
            width: 100%;
            transition: opacity 0.2s;
            margin-bottom: 0.5rem;
            /* Improve touch target size on mobile */
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-pay {
            background-color: var(--accent-green);
            color: #000;
        }

        .btn-print {
            background-color: var(--accent-blue);
            color: #000;
        }

        .btn-quotation {
            background-color: var(--accent-yellow);
            color: #000;
        }

        .btn-cancel {
            background-color: var(--accent-red);
            color: #000;
        }

        .btn-logout {
            background-color: var(--accent-red);
            color: #000;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .table-container {
            flex: 1;
            overflow-y: auto;
            min-height: 200px; /* Minimum height on mobile */
            position: relative;
        }

        @media (min-width: 992px) {
            .table-container {
                max-height: calc(100vh - 130px);
            }
        }

        .table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: var(--darker-bg);
        }

        .footer {
            padding: 1rem;
            border-top: 1px solid var(--border-color);
            background-color: var(--darker-bg);
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .subtotal {
            display: flex;
            justify-content: space-between;
            font-weight: 500;
            font-size: 1.1rem;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--darker-bg);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #444;
        }

        .search-container {
            position: relative;
            margin-bottom: 1rem;
        }

        .suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 300px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .suggestion-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }

        .suggestion-item:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .suggestion-item .price {
            color: #666;
            font-size: 0.9em;
        }

        .suggestion-item .stock {
            color: #28a745;
            font-size: 0.9em;
        }

        .suggestion-item .out-of-stock {
            color: #dc3545;
        }

        /* Select2 customization for dark theme */
        .select2-container--default .select2-selection--single {
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            height: 38px;
            display: flex;
            align-items: center;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--text-primary);
            line-height: 38px;
            padding-left: 12px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        .select2-dropdown {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .select2-container--default .select2-results__option {
            color: var(--text-primary);
            padding: 8px 12px;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: var(--accent-blue);
            color: white;
        }

        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: var(--darker-bg);
        }

        .customer-section {
            margin-bottom: 1rem;
            padding: 1rem;
            background-color: var(--darker-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        /* Mobile specific styling for buttons */
        @media (max-width: 767px) {
            .d-flex.justify-content-between.gap-2.mb-3 {
                flex-direction: column;
            }
            
            .d-flex.justify-content-between.gap-2.mb-3 button {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            hr {
                margin: 1rem 0;
            }
            
            /* Larger touch targets for mobile */
            .suggestion-item {
                padding: 12px;
                min-height: 44px;
            }
            
            /* Make search dropdown more mobile-friendly */
            .select2-container {
                width: 100% !important;
            }
        }

        /* Mobile navigation panel for small screens */
        .mobile-nav-panel {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: var(--darker-bg);
            border-top: 1px solid var(--border-color);
            padding: 0.5rem;
            z-index: 1000;
        }

        @media (max-width: 767px) {
            .mobile-nav-panel {
                display: flex;
                justify-content: space-around;
            }
            
            body {
                padding-bottom: 60px; /* Space for the nav panel */
            }
            
            .mobile-nav-btn {
                background: transparent;
                border: none;
                color: var(--text-primary);
                font-size: 0.8rem;
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 0.5rem;
            }
            
            .mobile-nav-btn i {
                font-size: 1.2rem;
                margin-bottom: 0.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <!-- Main Sale Table Section -->
        <div class="main-card mb-3">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item name</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="saleTableBody">
                        <!-- Table rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
            <div class="footer">
                <div class="subtotal">
                    <span>Sub Total</span>
                    <span id="subtotal">LKR 0.00</span>
                </div>
            </div>
        </div>

        <!-- Action Buttons Section -->
        <div class="d-flex flex-column">
            <div>
                <div class="search-container">
                    <input type="text" class="search-bar" id="searchInput" placeholder="Search items...">
                    <div id="suggestions" class="suggestions"></div>
                </div>
                
                <!-- Customer Selection Section -->
                <div class="customer-section">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label text-secondary">Customer</label>
                            <button id="refreshCustomersBtn" class="btn btn-sm btn-outline-secondary" type="button">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <select id="customerSelect" class="form-select">
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= $customer['id'] ?>" data-info='<?= json_encode($customer) ?>'>
                                    <?= htmlspecialchars($customer['name']) ?> <?= !empty($customer['phone']) ? '(' . htmlspecialchars($customer['phone']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex justify-content-center">
                        <button class="btn-print" onclick="window.open('../customers/view_customers.php', '_blank')">
                            <i class="fa-solid fa-users"></i> Manage Customers
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-between gap-2 mb-3">
                    <button class="btn-pay" id="btnPay">
                        <i class="fa-solid fa-money-bill"></i> Pay
                    </button>
                    <button class="btn-quotation" id="btnQuotation">
                        <i class="fa-solid fa-file-invoice"></i> Quotation
                    </button>
                </div>
                
                <hr style="border-color: var(--border-color); margin: 0 0 1.5rem;">
                <div class="action-buttons">
                    <button class="btn-cancel" id="btnCancel">
                        <i class="fa-solid fa-ban"></i> Cancel
                    </button>
                </div>
                <hr style="border-color: var(--border-color); margin: 0 0 1.5rem;">

                <!-- On mobile, hide these buttons in the main interface -->
                <div class="action-buttons d-none d-md-grid">
                    <button class="btn-print" onclick="window.open('../inventory/add_product.php', '_blank')">Item Register</button>
                    <button class="btn-print" onclick="window.open('../inventory/GRN.php', '_blank')">Add New Stock</button>
                    <button class="btn-print" onclick="window.open('../inventory/sales_report.php', '_blank')">Sales Report</button>
                    <button class="btn-print" onclick="window.open('../inventory/stock_report.php', '_blank')">Stock Report</button>
                    <button class="btn-print" onclick="window.open('../quotations/view_quotations.php', '_blank')">Quotations</button>
                    <button class="btn-print" onclick="window.open('../returns/returns.php', '_blank')"> Returns/Refunds</button>
                </div>

                <!-- On desktop, show these buttons normally -->
                <div class="mt-3 mb-4 d-md-none">
                    <div class="d-grid">
                        <button class="btn-print" type="button" data-bs-toggle="collapse" data-bs-target="#moreOptions" aria-expanded="false" aria-controls="moreOptions">
                            <i class="fa-solid fa-ellipsis"></i> More Options
                        </button>
                    </div>
                    <div class="collapse mt-2" id="moreOptions">
                        <div class="d-grid gap-2">
                            <button class="btn-print" onclick="window.open('../inventory/add_product.php', '_blank')">
                                <i class="fa-solid fa-plus-circle"></i> Item Register
                            </button>
                            <button class="btn-print" onclick="window.open('../inventory/GRN.php', '_blank')">
                                <i class="fa-solid fa-boxes"></i> Add New Stock
                            </button>
                            <button class="btn-print" onclick="window.open('../inventory/sales_report.php', '_blank')">
                                <i class="fa-solid fa-chart-line"></i> Sales Report
                            </button>
                            <button class="btn-print" onclick="window.open('../inventory/stock_report.php', '_blank')">
                                <i class="fa-solid fa-box"></i> Stock Report
                            </button>
                            <button class="btn-print" onclick="window.open('../quotations/view_quotations.php', '_blank')">
                                <i class="fa-solid fa-file-alt"></i> Quotations
                            </button>
                            <button class="btn-print" onclick="window.open('../returns/returns.php', '_blank')">
                                <i class="fa-solid fa-undo"></i> Returns/Refunds
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-auto">
                <button class="btn-logout" onclick="window.location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Panel -->
    <div class="mobile-nav-panel d-md-none">
        <button class="mobile-nav-btn" id="mobileSearchBtn">
            <i class="fa-solid fa-search"></i>
            <span>Search</span>
        </button>
        <button class="mobile-nav-btn" id="mobileCartBtn">
            <i class="fa-solid fa-shopping-cart"></i>
            <span>Cart</span>
        </button>
        <button class="mobile-nav-btn" id="mobileCustomerBtn">
            <i class="fa-solid fa-user"></i>
            <span>Customer</span>
        </button>
        <button class="mobile-nav-btn" id="mobilePayBtn">
            <i class="fa-solid fa-money-bill"></i>
            <span>Pay</span>
        </button>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
    <script>
        let currentSale = []; // Initialize empty sale
        let selectedCustomer = null;
        const searchInput = document.getElementById('searchInput');
        const suggestionsContainer = document.getElementById('suggestions');
        let debounceTimer;
        let searchResults = []; // Store the full search results
        let isMobile = window.innerWidth < 768;

        $(document).ready(function() {
            // Initialize Select2 for customer dropdown
            $('#customerSelect').select2({
                theme: 'default',
                placeholder: 'Search for a customer...',
                allowClear: true,
                width: '100%'
            });

            // Listen for customer selection change
            $('#customerSelect').on('change', function() {
                const customerId = $(this).val();
                if (customerId) {
                    const selectedOption = $(this).find(':selected');
                    selectedCustomer = JSON.parse(selectedOption.attr('data-info'));
                    console.log("Selected customer:", selectedCustomer);
                } else {
                    selectedCustomer = null;
                }
            });

            // Handle window resize to check if mobile or desktop
            $(window).resize(function() {
                isMobile = window.innerWidth < 768;
            });

                // Set up periodic refresh every 30 seconds
            setInterval(refreshCustomerDropdown, 30000);
            
            // Option to manually refresh
            $('#refreshCustomersBtn').on('click', function() {
                refreshCustomerDropdown();
            });

            // Mobile navigation handlers
            $('#mobileSearchBtn').click(function() {
                $('html, body').animate({
                    scrollTop: $('#searchInput').offset().top - 20
                }, 200);
                $('#searchInput').focus();
            });

            $('#mobileCartBtn').click(function() {
                $('html, body').animate({
                    scrollTop: $('.main-card').offset().top - 20
                }, 200);
            });

            $('#mobileCustomerBtn').click(function() {
                $('html, body').animate({
                    scrollTop: $('.customer-section').offset().top - 20
                }, 200);
                $('#customerSelect').select2('open');
            });

            $('#mobilePayBtn').click(function() {
                $('#btnPay').click();
            });
        });

        function renderTableRows() {
            const tableBody = document.getElementById('saleTableBody');
            tableBody.innerHTML = ''; // Clear existing rows

            currentSale.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.name}</td>
                    <td><input type="number" class="editable-cell" value="${item.price}" step="0.01" data-id="${item.id}" data-field="price"></td>
                    <td><input type="number" class="editable-cell" value="${item.qty}" min="1" data-id="${item.id}" data-field="qty"></td>
                    <td>${(item.price * item.qty).toFixed(2)}</td>
                    <td>
                        <button class="btn-remove" data-id="${item.id}">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                `;
                tableBody.appendChild(row);
            });

            updateTotal();
        }

        function updateTotal() {
            const subtotal = currentSale.reduce((sum, item) => sum + (item.price * item.qty), 0);
            document.getElementById('subtotal').textContent = `LKR ${subtotal.toFixed(2)}`;
        }

        function handleInputChange(event) {
            const input = event.target;
            if (!input.classList.contains('editable-cell')) return;

            const itemId = parseInt(input.dataset.id);
            const field = input.dataset.field;
            const value = parseFloat(input.value) || 0;

            const item = currentSale.find(item => item.id === itemId);
            if (item) {
                item[field] = value;
                // Update the total for this row
                const row = input.closest('tr');
                const totalCell = row.cells[3]; // Index 3 is the total column
                totalCell.textContent = `${(item.price * item.qty).toFixed(2)}`;
                updateTotal();
            }
        }

        async function fetchSuggestions(searchTerm) {
            try {
                const response = await fetch(`search.php?term=${encodeURIComponent(searchTerm)}`);
                if (!response.ok) throw new Error('Network response was not ok');
                const data = await response.json();
                console.log('Search response:', data); // Debug log to see the exact data structure
                return data;
            } catch (error) {
                console.error('Error fetching suggestions:', error);
                return [];
            }
        }

        // Function to refresh customer dropdown
            function refreshCustomerDropdown() {
                // Add timestamp to prevent caching
                const timestamp = new Date().getTime();
                
                fetch(`get_customers.php?timestamp=${timestamp}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.customers) {
                            updateCustomerDropdown(data.customers);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching customers:', error);
                    });
            }

            // Function to update the customer dropdown with new data
            function updateCustomerDropdown(customers) {
                const dropdown = $('#customerSelect');
                
                // Store the currently selected value
                const currentSelection = dropdown.val();
                
                // Clear existing options except for the first one
                dropdown.find('option:not(:first)').remove();
                
                // Add new options
                customers.forEach(customer => {
                    const phoneDisplay = customer.phone ? ` (${customer.phone})` : '';
                    const option = new Option(
                        `${customer.name}${phoneDisplay}`, 
                        customer.id
                    );
                    
                    // Add the data-info attribute with JSON data
                    $(option).attr('data-info', JSON.stringify(customer));
                    
                    // Add to dropdown
                    dropdown.append(option);
                });
                
                // Restore previous selection if it exists
                if (currentSelection) {
                    dropdown.val(currentSelection).trigger('change');
                }
                
                // Refresh the Select2 instance
                dropdown.trigger('select2:destroy').select2({
                    theme: 'default',
                    placeholder: 'Search for a customer...',
                    allowClear: true,
                    width: '100%'
                });
            }

        function displaySuggestions(suggestions) {
            // Store the full results for later use
            searchResults = suggestions;

            if (suggestions.length === 0) {
                suggestionsContainer.style.display = 'none';
                return;
            }

            suggestionsContainer.innerHTML = suggestions.map(item => `
                <div class="suggestion-item" data-id="${item.id}">
                    <strong style="color:black">${item.name}</strong>
                    <div class="price">Price: LKR ${parseFloat(item.selling_price).toFixed(2)}</div>
                    <div class="${parseInt(item.quantity) > 0 ? 'stock' : 'stock out-of-stock'}">
                        ${parseInt(item.quantity) > 0 ? 'In Stock: ' + item.quantity : 'Out of Stock'}
                    </div>
                    <small>${item.category || ''}</small>
                </div>
            `).join('');

            suggestionsContainer.style.display = 'block';
        }

        function addItemToSale(itemData) {
            console.log('Adding item:', itemData); // Debug log
            
            // Get price and cost, with fallback for different field names
            const price = parseFloat(itemData.selling_price || itemData.price);
            const cost = parseFloat(itemData.purchase_price || itemData.cost);
            
            if (isNaN(price)) {
                console.error('Invalid price:', itemData);
                return;
            }

            const existingItem = currentSale.find(saleItem => saleItem.id === parseInt(itemData.id));
            
            if (existingItem) {
                existingItem.qty += 1;
            } else {
                currentSale.push({
                    id: parseInt(itemData.id),
                    name: itemData.name,
                    price: price,
                    cost: cost || 0, // Fallback to 0 if cost is not available
                    qty: 1
                });
            }
            
            console.log('Current sale after add:', currentSale); // Debug log
            renderTableRows();
            
            // For mobile: scroll to the table to show the added item
            if (isMobile) {
                setTimeout(() => {
                    $('html, body').animate({
                        scrollTop: $('.main-card').offset().top - 20
                    }, 200);
                }, 100);
            }
        }

        // Event listener for input changes
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.trim();
            clearTimeout(debounceTimer);
            
            if (searchTerm.length < 2) {
                suggestionsContainer.style.display = 'none';
                return;
            }
            
            debounceTimer = setTimeout(async () => {
                const suggestions = await fetchSuggestions(searchTerm);
                displaySuggestions(suggestions);
            }, 300);
        });

        // Event listener for clicking outside
        document.addEventListener('click', (e) => {
            if (!suggestionsContainer.contains(e.target) && e.target !== searchInput) {
                suggestionsContainer.style.display = 'none';
            }
        });

        // Event listener for suggestion selection
        suggestionsContainer.addEventListener('click', (e) => {
            try {
                const suggestionItem = e.target.closest('.suggestion-item');
                if (suggestionItem) {
                    const productId = parseInt(suggestionItem.dataset.id);
                    console.log('Selected product ID:', productId);
                    
                    // Find the full product data from searchResults
                    const productData = searchResults.find(item => parseInt(item.id) === productId);
                    console.log('Found product data:', productData);
                    
                    if (productData) {
                        addItemToSale(productData);
                        searchInput.value = '';
                        suggestionsContainer.style.display = 'none';
                    } else {
                        console.error('Product not found in searchResults. Available results:', searchResults);
                    }
                }
            } catch (error) {
                console.error('Error in click handler:', error);
            }
        });

        function removeItem(itemId) {
            currentSale = currentSale.filter(item => item.id !== itemId);
            renderTableRows();
        }

        function handleRemoveClick(event) {
            const removeButton = event.target.closest('.btn-remove');
            if (removeButton) {
                const itemId = parseInt(removeButton.dataset.id);
                removeItem(itemId);
            }
        }

        // Save sale function
        async function saveSale() {
            if (currentSale.length === 0) {
                alert('No items in sale');
                return;
            }

            try {
                const requestData = {
                    items: currentSale,
                    customer_id: selectedCustomer ? selectedCustomer.id : null,
                    customer_info: selectedCustomer
                };
                console.log('Sending data:', requestData);

                const response = await fetch('save_sale.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                });

                const responseText = await response.text();
                console.log('Raw response:', responseText);

                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    console.log('Failed to parse response:', responseText);
                    throw new Error('Invalid response format from server');
                }

                if (result.success) {
                    alert(`Sale completed successfully!\nTotal Amount: LKR ${parseFloat(result.total_amount).toFixed(2)}`);
                    // Open invoice in new window
                    openInvoiceWindow(result.sale_id, currentSale, selectedCustomer);
                    // Clear the current sale
                    currentSale = [];
                    renderTableRows();
                    // Clear customer selection
                    $('#customerSelect').val('').trigger('change');
                    selectedCustomer = null;
                } else {
                    throw new Error(result.error || 'Failed to save sale');
                }
            } catch (error) {
                console.error('Error saving sale:', error);
                alert('Failed to save sale: ' + error.message);
            }
        }

        // Save quotation function
        async function saveQuotation() {
            if (currentSale.length === 0) {
                alert('No items in quotation');
                return;
            }

            try {
                const requestData = {
                    items: currentSale,
                    customer_id: selectedCustomer ? selectedCustomer.id : null,
                    customer_info: selectedCustomer
                };
                console.log('Sending quotation data:', requestData);

                const response = await fetch('../quotations/save_quotation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                });

                const responseText = await response.text();
                console.log('Raw response:', responseText);

                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    console.log('Failed to parse response:', responseText);
                    throw new Error('Invalid response format from server');
                }

                if (result.success) {
                    alert(`Quotation created successfully!\nTotal Amount: LKR ${parseFloat(result.total_amount).toFixed(2)}`);
                    // Open quotation in new window
                    window.open(`../quotations/generate_quotation.php?id=${result.quotation_id}`, '_blank');
                    // Clear the current sale
                    currentSale = [];
                    renderTableRows();
                    // Clear customer selection
                    $('#customerSelect').val('').trigger('change');
                    selectedCustomer = null;
                } else {
                    throw new Error(result.error || 'Failed to create quotation');
                }
            } catch (error) {
                console.error('Error creating quotation:', error);
                alert('Failed to create quotation: ' + error.message);
            }
        }

        function openInvoiceWindow(saleId, items, customer) {
            // Open new window
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            
            // Generate invoice HTML
            const invoiceHtml = generateInvoiceHtml(saleId, items, customer);
            
            // Write to new window
            printWindow.document.write(invoiceHtml);
            printWindow.document.close();
            
            printWindow.document.title = `Invoice - ${saleId}`;
            // Wait for resources to load then print
            printWindow.onload = function() {
                printWindow.print();
            };
        }

        function generateInvoiceHtml(saleId, items, customer) {
            const today = new Date().toLocaleDateString('en-GB', {
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric'
            });
            const total = items.reduce((sum, item) => sum + (item.price * item.qty), 0);
            
            // Generate customer details HTML if customer exists
            let customerHtml = '';
            if (customer) {
                customerHtml = `
                    <p><strong>${customer.name}</strong>${customer.phone ? ' | ' + customer.phone : ''}</p>
                    ${customer.address ? `<p>${customer.address}</p>` : ''}
                    ${customer.email ? `<p>${customer.email}</p>` : ''}
                `;
            } else {
                customerHtml = '<p>No customer details</p>';
            }
            
            // Get the invoice template with A4 size formatting based on the Kayel Auto Parts design
            const invoiceTemplate = `<!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Invoice - ${saleId}</title>
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
                                        <p><strong>Invoice #:</strong> ${saleId}</p>
                                        <p><strong>Date:</strong> ${today}</p>
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
                                        ${customerHtml}
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
                                    ${items.map((item, index) => `
                                        <tr>
                                            <td>${index + 1}</td>
                                            <td>${item.name}</td>
                                            <td class="text-center">${item.qty}</td>
                                            <td class="text-end">LKR ${parseFloat(item.price).toFixed(2)}</td>
                                            <td class="text-end">LKR ${(item.price * item.qty).toFixed(2)}</td>
                                        </tr>
                                    `).join('')}
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
                                            <td class="text-end">LKR ${total.toFixed(2)}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-end"><strong>Total Amount:</strong></td>
                                            <td class="text-end"><strong>LKR ${total.toFixed(2)}</strong></td>
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
                </body>
                </html>`;   

            return invoiceTemplate;
        }

        // Event Listeners
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('saleTableBody').addEventListener('input', handleInputChange);
            document.getElementById('saleTableBody').addEventListener('click', handleRemoveClick);
        });

        // Add event listener for pay button
        document.getElementById('btnPay').addEventListener('click', saveSale);
        
        // Add event listener for quotation button
        document.getElementById('btnQuotation').addEventListener('click', saveQuotation);
        
        // Add event listener for cancel button
        document.getElementById('btnCancel').addEventListener('click', () => {
            currentSale = [];
            renderTableRows();
            // Clear customer selection
            $('#customerSelect').val('').trigger('change');
            selectedCustomer = null;
        });
    </script>
</body>
</html>