<?php
session_start();
include("db.php");
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['mail']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT id, password, email, phone FROM admins WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['email'] = $admin['email'];
            $_SESSION['is_admin'] = true; 
            $_SESSION['message'] = "Zalogowano jako administrator";
            header("Location: home.php");
            exit();
        }
        $stmt = $pdo->prepare("SELECT id, password, email, phone FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['phone'] = $user['phone'];
            $_SESSION['is_admin'] = false;
            $_SESSION['message'] = "Zalogowano jako użytkownik";
            header("Location: home.php");
            exit();
        }
        $_SESSION['message'] = "Błędny e-mail lub hasło.";
        header("Location: login-form.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['message'] = "Błąd: " . $e->getMessage();
        header("Location: login-form.php");
        exit();
    }
} else {
    $_SESSION['message'] = "Nieprawidłowa metoda żądania.";
    header("Location: login-form.php");
    exit();
}
?>
