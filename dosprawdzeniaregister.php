<?php
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = $_POST['phone'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email address.";
        exit;
    }

    /*if (!preg_match('/^\d{9}$/', $phone)) {
        echo "Invalid phone number.";
        exit;
    }

    if (strlen($password) < 8) {
        echo "Password must be at least 8 characters long.";
        exit;
    }*/

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            echo "Email is already registered.";
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (email, password, phone, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$email, $hashedPassword, $phone]);

        $userId = $pdo->lastInsertId();

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $email;
        $_SESSION['phone'] = $phone;


        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request method.";
}
?>
