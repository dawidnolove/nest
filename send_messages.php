<?php
session_start();
if (!isset($_SESSION['email'])) exit("Brak sesji");

$from = $_SESSION['email'];
$to = $_POST['to'] ?? '';
$message = $_POST['message'] ?? '';

if (!$to || !$message) exit("Brak danych");

$mysqli = new mysqli("localhost", "root", "", "baza2");
if ($mysqli->connect_error) exit("DB error");

$to = $mysqli->real_escape_string($to);
$message = $mysqli->real_escape_string($message);

$sql = "INSERT INTO messages (sender_email, receiver_email, message, created_at)
        VALUES ('$from', '$to', '$message', NOW())";

if ($mysqli->query($sql)) {
    echo "OK";
} else {
    echo "Błąd zapisu";
}
?>