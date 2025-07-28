<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db_connect.php';

$term = isset($_GET['term']) ? trim($_GET['term']) : '';
if (empty($term)) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT title FROM books WHERE available_copies = 0 AND title LIKE ? LIMIT 10");
    $stmt->execute(["%$term%"]);
    $books = $stmt->fetchAll(PDO::FETCH_COLUMN);
    header('Content-Type: application/json');
    echo json_encode($books);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([]);
    error_log("Autocomplete error: " . $e->getMessage());
}
?>
