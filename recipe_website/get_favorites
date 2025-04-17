<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

// Sanitize user_id
$user_id = (int) $_SESSION['user_id'];
if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit();
}

// Database connection
$db = new mysqli('localhost', 'root', '', 'recipe_website');
if ($db->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Prepared statement for safety
$stmt = $db->prepare("SELECT meal_id FROM favorites WHERE user_id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database prepare failed']);
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$favorites = [];
while ($row = $result->fetch_assoc()) {
    // Allow alphanumeric and dash characters (commonly used in MealDB API IDs)
    if (preg_match('/^[a-zA-Z0-9\-]+$/', $row['meal_id'])) {
        $favorites[] = $row;
    }
}

$stmt->close();
$db->close();

// Return the favorites list (you could add meal info here if needed)
echo json_encode($favorites);
?>
