<?php
session_start();
if (!isset($_SESSION['email'])) exit;

$mysqli = new mysqli("localhost", "root", "", "baza2");
if ($mysqli->connect_error) exit;

$current_email = $_SESSION['email'];
$with = $_GET['with'] ?? '';
$with = $mysqli->real_escape_string($with);

$sql = "SELECT sender_email, message, created_at FROM messages
        WHERE (sender_email = '$current_email' AND receiver_email = '$with')
           OR (sender_email = '$with' AND receiver_email = '$current_email')
        ORDER BY created_at ASC LIMIT 10";

$res = $mysqli->query($sql);
while ($row = $res->fetch_assoc()) {
    $msg = htmlspecialchars($row['message']);
    $side = ($row['sender_email'] == $current_email) ? 'right' : 'left';
    echo "<div style='text-align:$side;'>$msg</div>";
}
?>