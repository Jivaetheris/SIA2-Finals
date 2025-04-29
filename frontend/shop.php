<?php
session_start();
include '../backend/connection.php';
include '../backend/productQueries.php';
// Ensure the session email is set
if (!isset($_SESSION['email'])) {
    echo "Session email not set.";
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email']; 

$query = $conn->prepare("SELECT firstname FROM users WHERE email = ?");
$query->bind_param("s", $email);
$query->execute();
$query->bind_result($firstName);
$query->fetch();
$query->close();

// Function to check if a product exists
function productExists($conn, $productName) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE product_name = ?");
    $stmt->bind_param("s", $productName);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count > 0;
}

// Insert products if they don't already exist
// nilipat ko na sa backend productQueries.php

foreach ($product_queries as $product) {
    $productName = $product[0];
    if (!productExists($conn, $productName)) {
        $stmt = $conn->prepare(
            "INSERT INTO products (product_name, description, price, stock, product_image, category_id, brand_id, featured, sizes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "ssdisiiss", 
            $productName, 
            $product[1], 
            $product[2], 
            $product[3], 
            $product[4], 
            $product[5], 
            $product[6], 
            $product[7], 
            $product[8]
        );
        $stmt->execute();
        $stmt->close();
    }
}

// Pagination logic
$products_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $products_per_page;

// Fetch products for the current page
$stmt = $conn->prepare("SELECT * FROM products LIMIT ?, ?");
$stmt->bind_param("ii", $offset, $products_per_page);
$stmt->execute();
$result = $stmt->get_result();

// Fetch total number of products
$total_products_query = $conn->query("SELECT COUNT(*) AS total_products FROM products");
$total_products = $total_products_query->fetch_assoc()['total_products'];

// Calculate total pages
$total_pages = ceil($total_products / $products_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shop - All Products</title>
    <style>
        .product-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }
        .product-card {
            border: 1px solid #ddd;
            padding: 20px;
            width: 200px;
            text-align: center;
            border-radius: 10px;
        }
        .product-card img {
            width: 100%;
            height: auto;
            border-radius: 10px;
        }
        .product-card button {
            margin-top: 10px;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }
        .pagination {
            text-align: center;
            margin-top: 20px;
        }
        .pagination a {
            margin: 0 5px;
            padding: 5px 10px;
            background-color: #f1f1f1;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
        }
        .pagination a:hover {
            background-color: #4CAF50;
            color: white;
        }
    </style>
</head>
<body>

<header>
    <div class="logo">ShoeStore</div>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="shop.php">Shop</a></li>
            <li><a href="cart.php">Cart</a></li>
            <li><a href="account.php">Account</a></li>
        </ul>
    </nav>
</header>

<h2 style="text-align: center;">All Products</h2>

<div class="product-container">
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<div class='product-card'>
                    <img src='" . htmlspecialchars($row['product_image']) . "' alt='" . htmlspecialchars($row['product_name']) . "'>
                    <h3>" . htmlspecialchars($row['product_name']) . "</h3>
                    <p>" . htmlspecialchars($row['description']) . "</p>
                    <p>₱" . number_format($row['price'], 2) . "</p>
                    <form action='cart.php' method='POST'>
                        <input type='hidden' name='product_id' value='" . $row['product_id'] . "'>
                        <button type='submit' name='add_to_cart'>Add to Cart</button>
                    </form>
                </div>";
        }
    } else {
        echo "<p style='text-align: center;'>No products available.</p>";
    }
    ?>
</div>

<div class="pagination">
    <?php
    for ($i = 1; $i <= $total_pages; $i++) {
        echo "<a href='shop.php?page=$i'>$i</a>";
    }
    ?>
</div>

</body>
</html>

<?php
$conn->close();
?>