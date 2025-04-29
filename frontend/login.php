<?php
session_start();
include '../backend/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        echo "<script>alert('Email and Password cannot be empty!');</script>";
        exit;
    }

    $stmt = $conn->prepare("SELECT user_id, firstname, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $name, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_id;
            $_SESSION['firstname'] = $name;
            $_SESSION['email'] = $email;

            header("Location: index.php");
            exit;
        } else {
            echo "<script>alert('Invalid password!');</script>";
        }
    } else {
        echo "<script>alert('No account found with that email!');</script>";
    }

    $stmt->close();
    $conn->close();
}

if (isset($_SESSION['email'])) {
    header("Location: welcome.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles/login.css">
</head>
<body>
    <div id="container">
        <form action="" method="POST">
            <h1>Login</h1>
            <input type="email" name="email" placeholder="Enter Your Email" required><br>
            <input type="password" name="password" placeholder="Enter Your Password" required><br>
            <input type="submit" name="login" value="Login"><br>
            <label>Don't have an account?</label> 
            <a href="register.php">Sign Up</a>
        </form>
    </div>
</body>
</html>