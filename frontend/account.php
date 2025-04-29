<?php
session_start();
include '../backend/connection.php';

// Redirect to login if user is not logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$email = $_SESSION['email'];

// Fetch user details
$query = $conn->prepare("SELECT user_id, firstname, lastname, email, phone, address, role, created_at FROM users WHERE email = ?");
$query->bind_param("s", $email);
$query->execute();
$query->bind_result($user_id, $firstName, $lastName, $currentEmail, $phone, $address, $role, $createdAt);
$query->fetch();
$query->close();

$error_message = "";
$success_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $newEmail = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $newPassword = $_POST['password'] ?? '';
    $currentPassword = $_POST['current_password'] ?? '';
    $newPhone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $newAddress = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);

    // Validate the current password if changing sensitive info
    if ($newPassword || ($newEmail && $newEmail !== $currentEmail)) {
        $passwordQuery = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $passwordQuery->bind_param("i", $user_id);
        $passwordQuery->execute();
        $passwordQuery->bind_result($storedPassword);
        $passwordQuery->fetch();
        $passwordQuery->close();

        if (!password_verify($currentPassword, $storedPassword)) {
            $error_message = "Current password is incorrect.";
        }
    }

    if (!$error_message) {
        // Update password if provided
        if ($newPassword) {
            if (strlen($newPassword) < 8) {
                $error_message = "Password must be at least 8 characters long.";
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updatePasswordQuery = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $updatePasswordQuery->bind_param("si", $hashedPassword, $user_id);
                if (!$updatePasswordQuery->execute()) {
                    $error_message = "Error updating password: " . $conn->error;
                }
                $updatePasswordQuery->close();
            }
        }

        // Update email if changed
        if ($newEmail && $newEmail !== $currentEmail && !$error_message) {
            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $error_message = "Invalid email format.";
            } else {
                $emailCheckQuery = $conn->prepare("SELECT email FROM users WHERE email = ? AND user_id != ?");
                $emailCheckQuery->bind_param("si", $newEmail, $user_id);
                $emailCheckQuery->execute();
                $emailCheckQuery->store_result();

                if ($emailCheckQuery->num_rows > 0) {
                    $error_message = "The email is already in use.";
                } else {
                    $updateEmailQuery = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                    $updateEmailQuery->bind_param("si", $newEmail, $user_id);
                    if (!$updateEmailQuery->execute()) {
                        $error_message = "Error updating email: " . $conn->error;
                    } else {
                        $_SESSION['email'] = $newEmail;
                    }
                    $updateEmailQuery->close();
                }
                $emailCheckQuery->close();
            }
        }

        // Update phone if changed
        if ($newPhone && $newPhone !== $phone && !$error_message) {
            $updatePhoneQuery = $conn->prepare("UPDATE users SET phone = ? WHERE user_id = ?");
            $updatePhoneQuery->bind_param("si", $newPhone, $user_id);
            if (!$updatePhoneQuery->execute()) {
                $error_message = "Error updating phone: " . $conn->error;
            }
            $updatePhoneQuery->close();
        }

        // Update address if changed
        if ($newAddress && $newAddress !== $address && !$error_message) {
            $updateAddressQuery = $conn->prepare("UPDATE users SET address = ? WHERE user_id = ?");
            $updateAddressQuery->bind_param("si", $newAddress, $user_id);
            if (!$updateAddressQuery->execute()) {
                $error_message = "Error updating address: " . $conn->error;
            }
            $updateAddressQuery->close();
        }

        if (!$error_message) {
            $success_message = "Your account has been updated successfully.";
            // Refresh user data
            $query = $conn->prepare("SELECT firstname, lastname, email, phone, address FROM users WHERE user_id = ?");
            $query->bind_param("i", $user_id);
            $query->execute();
            $query->bind_result($firstName, $lastName, $currentEmail, $phone, $address);
            $query->fetch();
            $query->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Account</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .form-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .form-container h2 {
            text-align: center;
        }
        label {
            display: block;
            margin-bottom: 10px;
        }
        input[type="email"], input[type="password"], input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            width: 100%;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            text-align: center;
        }
        .user-info {
            margin-bottom: 20px;
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
            <li><a href="../backend/logout.php">Logout</a></li>
        </ul>
    </nav>
</header>

<div class="form-container">
    <h2>Update Account Details</h2>

    <?php if ($error_message): ?>
        <p class="error"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

    <div class="user-info">
        <p><strong>Name:</strong> <?= htmlspecialchars($firstName) ?> <?= htmlspecialchars($lastName) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($currentEmail) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($phone) ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($address) ?></p>
        <p><strong>Role:</strong> <?= htmlspecialchars($role) ?></p>
        <p><strong>Account Created:</strong> <?= htmlspecialchars($createdAt) ?></p>
    </div>

    <form action="account.php" method="POST">
        <label for="email">New Email:</label>
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($currentEmail) ?>" placeholder="Enter new email (optional)">
        
        <label for="current_password">Current Password:</label>
        <input type="password" id="current_password" name="current_password" placeholder="Enter current password">

        <label for="password">New Password:</label>
        <input type="password" id="password" name="password" placeholder="Enter new password (optional)">

        <label for="phone">New Phone:</label>
        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>" placeholder="Enter new phone number (optional)">

        <label for="address">New Address:</label>
        <textarea id="address" name="address" placeholder="Enter new address (optional)"><?= htmlspecialchars($address) ?></textarea>

        <input type="submit" value="Update Account">
    </form>
</div>

</body>
</html>

<?php
$conn->close();
?>
