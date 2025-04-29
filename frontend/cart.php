<?php
session_start();
include '../backend/connection.php';

// Redirect to login if user not logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Fetch user ID and first name
$email = $_SESSION['email'];
$query = $conn->prepare("SELECT user_id, firstname FROM users WHERE email = ?");
$query->bind_param("s", $email);
$query->execute();
$query->bind_result($user_id, $firstName);
$query->fetch();
$query->close();

// Add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];

    // Check if the product_id exists in the products table
    $check_product = $conn->prepare("SELECT product_id FROM products WHERE product_id = ?");
    $check_product->bind_param("i", $product_id);
    $check_product->execute();
    $check_product->store_result();

    if ($check_product->num_rows === 0) {
        $check_product->close();
        die("Error: Product does not exist.");
    }
    $check_product->close();

    // Check if item already exists
    $check = $conn->prepare("SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
    $check->bind_param("ii", $user_id, $product_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $update = $conn->prepare("UPDATE cart_items SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?");
        $update->bind_param("ii", $user_id, $product_id);
        $update->execute();
        $update->close();
    } else {
        $insert = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, 1)");
        $insert->bind_param("ii", $user_id, $product_id);
        $insert->execute();
        $insert->close();
    }

    $check->close();
    header("Location: cart.php");
    exit();
}

// Update quantity
if (isset($_POST['update_quantity'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity_change = (int)$_POST['quantity_change'];
    
    // Validate quantity change
    if ($quantity_change !== 1 && $quantity_change !== -1) {
        die("Invalid quantity change value");
    }

    // Update the quantity in the cart
    $update_quantity = $conn->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?");
    $update_quantity->bind_param("iii", $quantity_change, $user_id, $product_id);
    $update_quantity->execute();
    $update_quantity->close();

    header("Location: cart.php");
    exit();
}

// Remove from cart
if (isset($_POST['remove_from_cart'])) {
    $product_id = (int)$_POST['product_id'];

    // Verify the item belongs to the user before deleting
    $verify = $conn->prepare("SELECT 1 FROM cart_items WHERE user_id = ? AND product_id = ?");
    $verify->bind_param("ii", $user_id, $product_id);
    $verify->execute();
    $verify->store_result();
    
    if ($verify->num_rows === 1) {
        $delete = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
        $delete->bind_param("ii", $user_id, $product_id);
        $delete->execute();
        $delete->close();
    }
    $verify->close();
    
    header("Location: cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Cart</title>
    <style>
        table {
            width: 80%;
            margin: 20px auto;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: center;
        }
        form {
            display: inline-block;
            margin: 0;
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
    <div class="greeting">Hi, <?php echo htmlspecialchars($firstName); ?>!</div>
    <form action="../backend/logout.php" method="POST">
        <button type="submit" name="logout">Logout</button>
    </form>
</header>

<h2 style="text-align: center;">Welcome, <?php echo htmlspecialchars($firstName); ?>! 🛒 Your Cart</h2>

<?php
// Fetch cart items for the logged-in user
$query = $conn->prepare("SELECT p.product_name, p.price, c.quantity, p.product_id
                         FROM cart_items c 
                         JOIN products p ON c.product_id = p.product_id 
                         WHERE c.user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$query->store_result();

if ($query->num_rows > 0) {
    echo '<table>';
    echo '<tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th><th>Action</th></tr>';
    $total_price = 0;

    $query->bind_result($productName, $productPrice, $quantity, $productId);

    while ($query->fetch()) {
        $item_total = $productPrice * $quantity;
        $total_price += $item_total;

        echo "<tr>
            <td>" . htmlspecialchars($productName) . "</td>
            <td>₱" . number_format($productPrice, 2) . "</td>
            <td>
                <form method='POST'>
                    <input type='hidden' name='quantity_change' value='-1'>
                    <input type='hidden' name='product_id' value='{$productId}'>
                    <button type='submit' name='update_quantity'>-</button>
                </form>
                <span style='margin: 0 10px;'>{$quantity}</span>
                <form method='POST'>
                    <input type='hidden' name='quantity_change' value='1'>
                    <input type='hidden' name='product_id' value='{$productId}'>
                    <button type='submit' name='update_quantity'>+</button>
                </form>
            </td>
            <td>₱" . number_format($item_total, 2) . "</td>
            <td>
                <form method='POST'>
                    <input type='hidden' name='product_id' value='{$productId}'>
                    <button type='submit' name='remove_from_cart'>Remove</button>
                </form>
            </td>
        </tr>";
    }

    echo "<tr><td colspan='3'><strong>Total</strong></td><td colspan='2'><strong>₱" . number_format($total_price, 2) . "</strong></td></tr>";
    echo '</table>';
} else {
    echo "<p style='text-align: center;'>Your cart is empty.</p>";
}

$query->close();
?>

</body>
</html>