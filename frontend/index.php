<?php
session_start();
include '../backend/connection.php';
// Ensure the session email is set
if (!isset($_SESSION['email'])) {
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

// Helper function to check if a record exists
function recordExists($conn, $table, $column, $value) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM $table WHERE $column = ?");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count > 0;
}

// Insert brands only if they don't already exist
$brands = ['Nike', 'Adidas', 'Puma', 'Reebok', 'New Balance'];
foreach ($brands as $brand) {
    if (!recordExists($conn, 'brands', 'name', $brand)) {
        $stmt = $conn->prepare("INSERT INTO brands (name) VALUES (?)");
        $stmt->bind_param("s", $brand);
        $stmt->execute();
        $stmt->close();
    }
}

// Insert categories only if they don't already exist
$categories = ['Running Shoes', 'Casual Shoes', 'Sports Shoes', 'Boots', 'Sandals', 'Formal Shoes'];
foreach ($categories as $category) {
    if (!recordExists($conn, 'categories', 'name', $category)) {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $stmt->close();
    }
}

// Insert products only if they don't already exist
$products = [
    [
        "name" => "Adidas Yeezy Boost 350 V2 'Zebra'",
        "description" => "A highly sought-after Yeezy model featuring a bold zebra print pattern and comfortable boost technology.",
        "price" => 19999.99,
        "stock" => 10,
        "brand_id" => 2,
        "category_id" => 2,
        "featured" => true,
        "sizes" => "41, 42, 43, 44, 45",
        "image" => "adidas_yeezy_boost_350_v2_zebra.jpg"
    ],
    [
        "name" => "Nike Dunk Low 'Travis Scott Cactus Jack'",
        "description" => "A limited collab release featuring bandana print and earthy tones curated by Travis Scott.",
        "price" => 21999.99,
        "stock" => 4,
        "brand_id" => 1,
        "category_id" => 2,
        "featured" => true,
        "sizes" => "41, 42, 43",
        "image" => "nike_dunk_low_travis_scott.jpg"
    ],
    [
        "name" => "Nike Air Max 1/97 Sean Wotherspoon",
        "description" => "A unique mash-up of Air Max 1 and 97 designed by Sean Wotherspoon with vibrant corduroy upper.",
        "price" => 22999.99,
        "stock" => 3,
        "brand_id" => 1,
        "category_id" => 1,
        "featured" => true,
        "sizes" => "40, 41, 42, 43",
        "image" => "nike_air_max_1_97_wotherspoon.jpg"
    ],
    [
        "name" => "Adidas Superstar x Prada",
        "description" => "A luxurious take on the iconic Superstar silhouette in collaboration with Prada.",
        "price" => 29999.99,
        "stock" => 2,
        "brand_id" => 2,
        "category_id" => 2,
        "featured" => true,
        "sizes" => "41, 42",
        "image" => "adidas_superstar_prada.jpg"
    ],
    [
        "name" => "Reebok Instapump Fury x Vetements",
        "description" => "Avant-garde collaboration with Vetements featuring graffiti-style design and Pump tech.",
        "price" => 20999.99,
        "stock" => 5,
        "brand_id" => 4,
        "category_id" => 3,
        "featured" => true,
        "sizes" => "42, 43, 44",
        "image" => "reebok_instapump_vetements.jpg"
    ],
    [
        "name" => "Nike SB Dunk Low 'Ben & Jerry's Chunky Dunky'",
        "description" => "Colorful, ice-cream inspired design with faux cowhide and tie-dye liners.",
        "price" => 25999.99,
        "stock" => 3,
        "brand_id" => 1,
        "category_id" => 2,
        "featured" => true,
        "sizes" => "40, 41, 42",
        "image" => "nike_sb_dunk_chunky_dunky.jpg"
    ]
];

foreach ($products as $product) {
    if (!recordExists($conn, 'products', 'product_name', $product["name"])) {
        $stmt = $conn->prepare(
            "INSERT INTO products (product_name, description, price, stock, brand_id, category_id, featured, sizes, product_image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "ssdiissss", 
            $product["name"], 
            $product["description"], 
            $product["price"], 
            $product["stock"], 
            $product["brand_id"], 
            $product["category_id"], 
            $product["featured"], 
            $product["sizes"], 
            $product["image"]
        );
        $stmt->execute();
        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Featured Products</title>
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
        nav ul {
            list-style-type: none;
            display: flex;
            gap: 20px;
        }
        nav ul li a {
            text-decoration: none;
            color: #333;
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

<h2 style="text-align: center;">Featured Products</h2>

<div class="product-container">
    <?php
    // Fetch only featured products using prepared statement for security
    $stmt = $conn->prepare("SELECT product_id, product_name, description, price, product_image FROM products WHERE featured = 1");
    $stmt->execute();
    $result = $stmt->get_result();

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
        echo "<p style='text-align: center;'>No featured products found.</p>";
    }

    $stmt->close();
    ?>
</div>

</body>
</html>

<?php
$conn->close();
?>