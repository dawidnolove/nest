<?php
session_start();
require_once('db.php');  // Załaduj połączenie z bazą danych

// Sprawdzamy, czy formularz logowania został wysłany
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['mail']; // Pobieramy e-mail
    $password = $_POST['password']; // Pobieramy hasło

    // Zapytanie SQL, aby sprawdzić, czy użytkownik o podanym e-mailu istnieje w bazie
    $stmt = $conn->prepare("SELECT id, email, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Jeśli użytkownik istnieje
    if ($user) {
        // Sprawdzamy, czy hasło jest poprawne
        if (password_verify($password, $user['password'])) {
            // Zalogowano pomyślnie, ustawiamy sesję
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            header('Location: index.php'); // Przekierowanie na stronę główną po zalogowaniu
            exit;
        } else {
            // Błędne hasło
            echo "<div id='login-error-message' style='color: red;'>Błędne hasło</div>";
        }
    } else {
        // Brak użytkownika w bazie
        echo "<div id='login-error-message' style='color: red;'>Nie znaleziono użytkownika o takim adresie e-mail</div>";
    }
}
?>