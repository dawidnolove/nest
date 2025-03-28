<?php
$host = 'localhost';  // Adres serwera
$dbname = 'baza2';  // Nazwa bazy danych
$username = 'root';  // Użytkownik bazy danych
$password = '';  // Hasło (jeśli nie masz hasła, pozostaw puste)

try {
    // Tworzenie połączenia
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Ustawienie trybu błędów PDO na wyjątki
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Błąd połączenia: " . $e->getMessage();
}
?>