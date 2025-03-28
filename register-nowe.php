<?php
session_start();
require_once('db.php'); 

$message = ''; // Zmienna do komunikatów

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; 
    $phone = $_POST['phone']; 
    
    // Sprawdzamy poprawność adresu e-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div style='color: red;'>Nieprawidłowy format adresu e-mail.</div>";
    } else {
        // Szyfrowanie hasła
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        // Sprawdzamy, czy użytkownik o podanym e-mailu już istnieje
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            // Użytkownik już istnieje, wyświetlamy komunikat
            $message = "<div style='color: red;'>Użytkownik z takim e-mailem już istnieje. Proszę się zalogować.</div>";
        } else {
            // Rejestracja nowego użytkownika
            $stmt = $conn->prepare("INSERT INTO users (email, password, phone) VALUES (?, ?, ?)");
            $stmt->execute([$email, $hashed_password, $phone]);

            // Jeśli rejestracja powiodła się
            if ($stmt) {
                $message = "<script type='text/javascript'>
                    alert('Zaloguj się ponownie, aby otrzymać dostęp do strony.');
                </script>";
            } else {
                $message = "<div style='color: red;'>Wystąpił błąd przy rejestracji. Spróbuj ponownie.</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="stylesheet" href="login-form.css">
    <title>Logowanie</title>
</head>
<body>
    <img src="logo720.png" width="150px" height="150px"/>
    <div class="flip-card">
        <!-- Sekcja logowania -->
        <div class="login-container" id="login">
            <h2>Logowanie</h2>
            
            <!-- Komunikat o błędzie lub rejestracji -->
            <?php if ($message): ?>
                <div class="message" style="margin-bottom: 15px;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Formularz logowania -->
            <form id="login-form" method="POST" action="login.php">
                <input type="email" id="login-mail" name="mail" placeholder="E-MAIL" required />
                <input type="password" id="login-password" pattern="^(?=.*[A-Z])(?=.*[0-9]).{8,}$" title="Hasło musi zawierać co najmniej 8 znaków, w tym jedną dużą literę i jedną cyfrę." name="password" autocomplete="current_password" placeholder="HASŁO" required />
                <input type="submit" value="Zaloguj się" />
                <div id="login-error-message" style="color: red; display: none;"></div>
            </form>
            <div class="footer">
                <p onclick="handleFlip()" style="font-size: 12px">Nie masz konta? <a href="register.php" style="color: #aaa;">Zarejestruj się</a></p>
            </div>
        </div>
    </div>
</body>
</html>