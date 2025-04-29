<?php 
include '../backend/connection.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($phone) || empty($address)) {
        $message = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif (!is_numeric($phone)) {
        $message = "Contact number must be numeric.";
    } elseif (strlen($phone) !== 11) {
        $message = "Contact number must be 11 digits (e.g., 09123456789)";
    } else {
        $checkEmail = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $checkEmail->store_result();

        if ($checkEmail->num_rows > 0) {
            $message = "Email already registered!";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, password, phone, address) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $firstname, $lastname, $email, $hashedPassword, $phone, $address);

            if ($stmt->execute()) {
                echo "<script>alert('Registration Successful'); window.location='login.php';</script>";
                exit;
            } else {
                $message = "Error: " . htmlspecialchars($stmt->error);
            }

            $stmt->close();
        }

        $checkEmail->close();
    }

    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="styles/register.css">
</head>
<body>
    <div id="container">
        <h1>Register</h1>
        <form action="" method="POST">
            <input type="text" name="firstname" placeholder="First Name" required><br><br>
            <input type="text" name="lastname" placeholder="Last Name" required><br><br>
            <input type="email" name="email" placeholder="Email Address" required><br><br>
            <input type="text" name="phone" placeholder="Contact Number" required><br><br>
            <input type="text" name="address" placeholder="Address" required><br><br>
            <input type="password" name="password" placeholder="Password" required><br><br>
            <input type="submit" name="registration" value="Register"><br>
            <label>Already Have an Account?</label> <a href="login.php">Login</a>
        </form>

        <?php if (!empty($message)): ?>
            <p style='color: red;'><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
    </div>
</body>
</html>