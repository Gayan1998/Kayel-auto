<?php
session_start();
// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
include '../includes/db_connection.php'; // Make sure to create this file with your database connection

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            // Add new product
            $stmt = $pdo->prepare("INSERT INTO products (name, description, purchase_price, selling_price, quantity, category) 
                                 VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'],
                $_POST['purchase_price'],
                $_POST['selling_price'],
                $_POST['quantity'],
                $_POST['category']
            ]);
            $_SESSION['message'] = 'Product added successfully!';
            
        } elseif ($_POST['action'] === 'delete') {
            // Delete product
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $_SESSION['message'] = 'Product deleted successfully!';
        } elseif ($_POST['action'] === 'update') {
            // Update existing product
            $stmt = $pdo->prepare("UPDATE products 
                                 SET name = ?, description = ?, purchase_price = ?, 
                                     selling_price = ?, category = ?
                                 WHERE id = ?");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'],
                $_POST['purchase_price'],
                $_POST['selling_price'],
                $_POST['category'],
                $_POST['id']
            ]);
            $_SESSION['message'] = 'Product updated successfully!';
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch existing products
$stmt = $pdo->query("SELECT * FROM products ORDER BY name");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --dark-bg: #1a1a1a;
            --darker-bg: #141414;
            --card-bg: #242424;
            --border-color: #333;
            --text-primary: #fff;
            --text-secondary: #a0a0a0;
            --accent-blue: #60a5fa;
            --accent-green: #4ade80;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 2rem;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
        }

        .form-control, .form-select {
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .form-control:focus, .form-select:focus {
            background-color: var(--darker-bg);
            border-color: var(--accent-blue);
            color: var(--text-primary);
            box-shadow: none;
        }

        .table {
            color: var(--text-primary);
        }

        .table thead th {
            background-color: var(--darker-bg);
            color: var(--text-secondary);
            border-bottom: 2px solid var(--border-color);
        }

        .table td {
            border-color: var(--border-color);
        }

        .btn-edit {
            color: var(--accent-blue);
            background: transparent;
            border: 1px solid var(--accent-blue);
        }

        .btn-edit:hover {
            background: var(--accent-blue);
            color: var(--darker-bg);
        }

        .message {
            background-color: var(--accent-green);
            color: var(--darker-bg);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .product-table-container {
            max-height: 600px;
            overflow-y: auto;
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
            margin-bottom: 1rem;
        }
        
        .search-input {
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            width: 100%;
        }
        
        .search-input:focus {
            border-color: var(--accent-blue);
            outline: none;
        }
        
        .no-results {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }

        .form-label{
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Add Product Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="mb-0 text-white">Add New Product</h4>
            </div>
            <div class="card-body">
                <form method="POST" id="addProductForm">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="category" name="category">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="purchase_price" class="form-label">Purchase Price</label>
                            <input type="number" step="0.01" class="form-control" id="purchase_price" name="purchase_price" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="selling_price" class="form-label">Selling Price</label>
                            <input type="number" step="0.01" class="form-control" id="selling_price" name="selling_price" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="quantity" class="form-label">Initial Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </form>
            </div>
        </div>

        <!-- Products Table -->
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0 text-white">Product List</h4>
                <div class="search-container mt-3">
                    <input type="text" 
                           id="searchInput" 
                           class="search-input" 
                           placeholder="Search products by name, category, or description..."
                           autocomplete="off">
                </div>
            </div>
            <div class="card-body">
                <div class="product-table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Purchase Price</th>
                                <th>Selling Price</th>
                                <th>Quantity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="productTableBody">
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['category']) ?></td>
                                <td><?= htmlspecialchars($product['description']) ?></td>
                                <td>$<?= number_format($product['purchase_price'], 2) ?></td>
                                <td>$<?= number_format($product['selling_price'], 2) ?></td>
                                <td><?= $product['quantity'] ?></td>
                                <td>
                                    <button class="btn btn-edit btn-sm" 
                                            onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>)">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div id="noResults" class="no-results" style="display: none;">
                        No products found matching your search.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editProductForm">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="edit_category" name="category">
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_purchase_price" class="form-label">Purchase Price</label>
                            <input type="number" step="0.01" class="form-control" id="edit_purchase_price" name="purchase_price" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_selling_price" class="form-label">Selling Price</label>
                            <input type="number" step="0.01" class="form-control" id="edit_selling_price" name="selling_price" required>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-primary">Update Product</button>
                            <button type="button" class="btn btn-danger" onclick="deleteProduct()">Delete Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        let editModal;
        
        document.addEventListener('DOMContentLoaded', function() {
            editModal = new bootstrap.Modal(document.getElementById('editModal'));
        });

        function editProduct(product) {
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_category').value = product.category;
            document.getElementById('edit_description').value = product.description;
            document.getElementById('edit_purchase_price').value = product.purchase_price;
            document.getElementById('edit_selling_price').value = product.selling_price;
            
            editModal.show();
        }

        document.getElementById('searchInput').addEventListener('input', function() {
            initializeSearch();
            searchProducts(this.value);
        });
         function initializeSearch() {
            const searchInput = document.getElementById('searchInput');
            const tableBody = document.getElementById('productTableBody');
            const noResults = document.getElementById('noResults');
            const rows = tableBody.getElementsByTagName('tr');

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                let hasVisibleRows = false;

                for (const row of rows) {
                    const name = row.cells[0].textContent.toLowerCase();
                    const category = row.cells[1].textContent.toLowerCase();
                    const description = row.cells[2].textContent.toLowerCase();

                    const matches = name.includes(searchTerm) || 
                                  category.includes(searchTerm) || 
                                  description.includes(searchTerm);

                    row.style.display = matches ? '' : 'none';
                    if (matches) hasVisibleRows = true;
                }

                noResults.style.display = hasVisibleRows ? 'none' : 'block';
            });
        }

        function deleteProduct() {
            if (confirm('Are you sure you want to delete this product?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = document.getElementById('edit_id').value;

                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>