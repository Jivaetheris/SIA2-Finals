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

// Function to check if a brand exists
function brandExists($conn, $brandName) {
    $sql = "SELECT * FROM brands WHERE name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $brandName);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Function to check if a category exists
function categoryExists($conn, $categoryName) {
    $sql = "SELECT * FROM categories WHERE name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $categoryName);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Function to check if a product exists
function productExists($conn, $productName) {
    $sql = "SELECT * FROM products WHERE product_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $productName);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Insert brands only if they don't already exist
$brands = ['Nike', 'Adidas', 'Puma', 'Reebok', 'New Balance'];

foreach ($brands as $brand) {
    if (!brandExists($conn, $brand)) {
        $sql = "INSERT INTO brands (name) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $brand);
        $stmt->execute();
    }
}

// Insert categories only if they don't already exist
$categories = ['Running Shoes', 'Casual Shoes', 'Sports Shoes', 'Boots', 'Sandals', 'Formal Shoes'];

foreach ($categories as $category) {
    if (!categoryExists($conn, $category)) {
        $sql = "INSERT INTO categories (name) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $category);
        $stmt->execute();
    }
}

// Insert products only if they don't already exist
$product_queries = [
    ["Nike Air Jordan 1 Retro OG 'Chicago'", "A re-release of the iconic Air Jordan 1 in the 'Chicago' colorway, a must-have for sneaker collectors.", 14999.99, 5, 1, 6, 1, "40, 41, 42, 43, 44", "nike_air_jordan_1_chicago.jpg"], // Featured
    ["Adidas Yeezy Boost 350 V2 'Zebra'", "A highly sought-after Yeezy model featuring a bold zebra print pattern and comfortable boost technology.", 19999.99, 10, 2, 7, 0, "41, 42, 43, 44, 45", "adidas_yeezy_boost_350_v2_zebra.jpg"], // Not Featured
    ["Off-White x Nike Dunk Low 'Lot 50'", "A collaboration between Off-White and Nike, this Dunk Low is part of the exclusive 'Lot 50' collection.", 24999.99, 3, 3, 8, 1, "40, 41, 42, 43", "off_white_nike_dunk_low_lot_50.jpg"], // Featured
    ["Balenciaga Triple S 'Black & White'", "Balenciaga's signature chunky sneaker design in a black and white color scheme.", 39999.99, 7, 4, 9, 0, "41, 42, 43, 44", "balenciaga_triple_s_black_white.jpg"], // Not Featured
    ["Louis Vuitton x Nike Air Force 1 '2022'", "A luxury collaboration between Louis Vuitton and Nike, blending high fashion with athletic footwear.", 79999.99, 2, 5, 10, 1, "40, 41, 42, 43, 44", "louis_vuitton_nike_air_force_1_2022.jpg"], // Featured
    ["Patek Philippe x Nike Air Force 1", "A collaboration between the prestigious Swiss watchmaker Patek Philippe and Nike for an ultra-luxury Air Force 1 edition.", 99999.99, 1, 6, 11, 0, "41, 42, 43", "patek_philippe_nike_air_force_1.jpg"] // Not Featured
];

foreach ($product_queries as $product) {
    $product_name = $product[0];
    $description = $product[1];
    $price = $product[2];
    $stock = $product[3];
    $brand_id = $product[4]; // Corrected brand_id (integer)
    $category_id = $product[5]; // Corrected category_id (integer)
    $featured = $product[6]; // Featured flag
    $sizes = $product[7]; // Sizes available
    $product_image = $product[8]; // Product image

    // Handle missing image file by using a default image
    if (empty($product_image)) {
        $product_image = 'default_image.jpg'; // Placeholder image
    }

    // Step 1: Check if the brand_id exists in the brands table
    $brand_check_query = "SELECT COUNT(*) FROM brands WHERE brand_id = ?";
    $stmt = $conn->prepare($brand_check_query);
    $stmt->bind_param("i", $brand_id);
    $stmt->execute();
    $stmt->bind_result($brand_exists);
    $stmt->fetch();
    $stmt->close();

    // Step 2: If brand doesn't exist, handle the error
    if ($brand_exists == 0) {
        echo "Error: Brand ID {$brand_id} does not exist in the brands table.<br>";
        continue; // Skip to the next product in the loop
    }

    // Step 3: Check if the category_id exists in the categories table
    $category_check_query = "SELECT COUNT(*) FROM categories WHERE category_id = ?";
    $stmt = $conn->prepare($category_check_query);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $stmt->bind_result($category_exists);
    $stmt->fetch();
    $stmt->close();

    // Step 4: If category doesn't exist, handle the error
    if ($category_exists == 0) {
        echo "Error: Category ID {$category_id} does not exist in the categories table.<br>";
        continue; // Skip to the next product in the loop
    }

    // Step 5: Proceed to insert the product into the products table
    $insert_query = "INSERT INTO products (product_name, price, stock, brand_id, category_id, featured, sizes, product_image) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("sdisiiss", $product_name, $price, $stock, $brand_id, $category_id, $featured, $sizes, $product_image);

    if ($stmt->execute()) {
        echo "Product '{$product_name}' inserted successfully.<br>";
    } else {
        echo "Error inserting product '{$product_name}': " . $stmt->error . "<br>";
    }

    $stmt->close();
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
    $stmt = $conn->prepare("SELECT * FROM products WHERE featured = 1");
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
    ?>
</div>

</body>
</html>

<?php
$conn->close();
?>
