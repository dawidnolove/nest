<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = $_POST['phone'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Nieprawidłowy adres email.";
        header("Location: login-form.php");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['message'] = "Użytkownik z takim e-mailem już istnieje. Proszę się zalogować.";
            header("Location: login-form.php");
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password, phone, created_at) VALUES (?, ?, ?, NOW())");
        
        if ($stmt->execute([$email, $hashedPassword, $phone])) {
            $_SESSION['message'] = "Rejestracja zakończona pomyślnie. Proszę się zalogować.";
            header("Location: login-form.php");
            exit;
        } else {
            $_SESSION['message'] = "Wystąpił błąd przy rejestracji. Spróbuj ponownie.";
            header("Location: login-form.php");
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['message'] = "Błąd: " . $e->getMessage();
        header("Location: login-form.php");
        exit;
    }
} else {
    $_SESSION['message'] = "Nieprawidłowa metoda żądania.";
    header("Location: login-form.php");
    exit;
}
?>