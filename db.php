<?php
$host = 'sql101.infinityfree.com';      
$dbname = 'if0_38422307_lore_nest';  
$uname = 'if0_38422307';      
$passwd= 'ad17dp07';

$dsn = "mysql:host=$host;dbname=$dbname";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $uname, $passwd);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Błąd połączenia: " . $e->getMessage());
}

/*
$host = '127.0.0.1';      
$dbname = 'baza2';  
$uname = 'root';      
$passwd= '';   
*/