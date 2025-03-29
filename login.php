<?php
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['mail']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT id, password, email, phone FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['phone'] = $user['phone'];
            header("Location: home.php");
            exit();
        } else {
            $_SESSION['message'] = "Błędny email lub hasło";
            header("Location: login-form.php"); // Zmień na .php
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Błąd: " . $e->getMessage();
        header("Location: login-form.php"); // Zmień na .php
        exit();
    }
} else {
    $_SESSION['message'] = "Nieprawidłowa metoda żądania.";
    header("Location: login-form.php"); // Zmień na .php
    exit();
}
?>