<?php
session_start();
include '../backend/connection.php';

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
    $sql = "SELECT * FROM products WHERE product_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $productName);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// 0 means normal product refer nlng sa index.php
$product_queries = [
    ["Nike Air Zoom Pegasus 38", "Nike running shoes with Zoom Air cushioning for a responsive feel.", 7499.99, 15, 'nike_air_zoom_pegasus_38.jpg', 1, 1, 0, '40, 41, 42, 43, 44'], 
    ["Adidas Ultraboost 22", "Comfortable running shoes with Boost technology for all-day comfort.", 9999.99, 20, 'adidas_ultraboost_22.jpg', 1, 2, 0, '39, 40, 41, 42, 43'], 
    ["Puma Future Z 1.1", "Puma football boots for a responsive, comfortable fit on the field.", 5999.99, 10, 'puma_future_z_1_1.jpg', 1, 3, 0, '41, 42, 43, 44, 45'], 
    ["Reebok Nano X1", "Versatile training shoes with Flexweave for stability and flexibility.", 6499.99, 18, 'reebok_nano_x1.jpg', 2, 4, 0, '40, 41, 42, 43, 44'], 
    ["New Balance Fresh Foam 1080v11", "Soft and responsive running shoes with Fresh Foam cushioning for long runs.", 8999.99, 12, 'new_balance_fresh_foam_1080v11.jpg', 1, 5, 0, '40, 41, 42, 43, 44'],
    ["Nike Air Max 270", "A stylish and comfortable running shoe from Nike.", 4999.99, 20, 'nike_air_max_270.jpg', 1, 1, 0, '38, 39, 40, 41, 42'], 
    ["Adidas Originals Stan Smith", "Classic and timeless tennis shoes from Adidas.", 4499.99, 25, 'adidas_stan_smith.jpg', 1, 2, 0, '37, 38, 39, 40, 41'], 
    ["Puma Suede Classic", "Iconic Puma sneakers with a retro look.", 3999.99, 30, 'puma_suede_classic.jpg', 1, 3, 0, '36, 37, 38, 39, 40'], 
    ["Reebok Classic Leather", "Reebok leather sneakers with classic design.", 3499.99, 25, 'reebok_classic_leather.jpg', 2, 4, 0, '38, 39, 40, 41, 42'],
    ["New Balance 990", "A premium quality running shoe from New Balance.", 9999.99, 10, 'new_balance_990.jpg', 1, 5, 0, '40, 41, 42, 43'], 
    ["Nike ZoomX Vaporfly NEXT%", "High-performance running shoes designed for speed and endurance.", 12999.99, 8, 'nike_zoomx_vaporfly_next.jpg', 1, 1, 0, '40, 41, 42, 43'], 
    ["Adidas Adizero Adios Pro 2", "Lightweight and responsive shoes built for racing and speed.", 11999.99, 12, 'adidas_adizero_adios_pro_2.jpg', 1, 2, 0, '39, 40, 41, 42, 43'], 
    ["Puma RS-X3", "Futuristic sneakers with an eclectic design and bold colorway.", 4999.99, 20, 'puma_rs_x3.jpg', 1, 3, 0, '38, 39, 40, 41'], 
    ["Reebok Zig Kinetica", "Innovative sneakers with ZigTech for enhanced comfort and energy return.", 6999.99, 18, 'reebok_zig_kinetica.jpg', 2, 4, 0, '40, 41, 42, 43, 44'], 
    ["New Balance 574", "Classic New Balance sneakers with a heritage-inspired design.", 4599.99, 35, 'new_balance_574.jpg', 1, 5, 0, '37, 38, 39, 40'], 
    ["Nike Air Force 1", "Iconic basketball shoes with timeless style and comfort.", 4999.99, 22, 'nike_air_force_1.jpg', 1, 1, 0, '40, 41, 42, 43, 44'], 
    ["Adidas NMD_R1", "Lifestyle shoes with a sleek and modern design, featuring Boost cushioning.", 8999.99, 14, 'adidas_nmd_r1.jpg', 1, 2, 0, '38, 39, 40, 41, 42'], 
    ["Puma Clyde Court", "Performance basketball shoes with a classic silhouette and comfortable fit.", 6999.99, 15, 'puma_clyde_court.jpg', 1, 3, 0, '40, 41, 42, 43'], 
    ["Reebok Question Mid", "Retro-inspired basketball shoes with a cushioned midsole and supportive fit.", 7999.99, 10, 'reebok_question_mid.jpg', 2, 4, 0, '39, 40, 41, 42'],
    ["New Balance 1080v10", "Cushioned running shoes offering premium comfort for daily runs.", 7999.99, 20, 'new_balance_1080v10.jpg', 1, 5, 0, '40, 41, 42, 43, 44'], 
    ["Nike React Infinity Run Flyknit", "Running shoes with plush cushioning and stability for long distances.", 8499.99, 13, 'nike_react_infinity_run_flyknit.jpg', 1, 1, 0, '40, 41, 42, 43'], 
    ["Adidas Solarboost 19", "Comfortable running shoes with Solar Propulsion Rail for added stability.", 8999.99, 17, 'adidas_solarboost_19.jpg', 1, 2, 0, '39, 40, 41, 42, 43'], 
    ["Puma Future Rider", "Retro-inspired sneakers with vibrant colors and lightweight comfort.", 4999.99, 28, 'puma_future_rider.jpg', 1, 3, 0, '38, 39, 40, 41'], 
    ["Reebok Nano 9", "CrossFit shoes designed to handle intense workouts with superior support.", 7999.99, 9, 'reebok_nano_9.jpg', 2, 4, 0, '40, 41, 42, 43'], 
    ["New Balance 327", "Stylish sneakers with a mix of modern and vintage aesthetics.", 5699.99, 30, 'new_balance_327.jpg', 1, 5, 0, '37, 38, 39, 40'] 
];

foreach ($product_queries as $product) {
    $productName = $product[0];
    if (!productExists($conn, $productName)) {
        $sql = "INSERT INTO products (product_name, product_description, product_price, product_stock, product_image, category_id, brand_id, featured) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdisiii", $productName, $product[1], $product[2], $product[3], $product[4], $product[5], $product[6], $product[7]);
        $stmt->execute();
    }
}

// Pagination logic
$products_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $products_per_page;

// Fetch products for the current page
$query = "SELECT * FROM products LIMIT ?, ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $offset, $products_per_page);
$stmt->execute();
$result = $stmt->get_result();

// Fetch total number of products
$query = "SELECT COUNT(*) AS total_products FROM products";
$result_count = $conn->query($query);
$row = $result_count->fetch_assoc();
$total_products = $row['total_products'];

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
                    <p>" . htmlspecialchars($row['product_description']) . "</p>
                    <p>₱" . number_format($row['product_price'], 2) . "</p>
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
