<?php
// Include database connection
include_once 'link_recipe_website.php';
session_start();

// Get data from the form
$username = trim($_POST['username']);
$password = trim($_POST['password']);

// Check if username already exists using a prepared statement
$sql_username = "SELECT * FROM users WHERE username = ?";
$stmt = mysqli_prepare($mysqli, $sql_username);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result_username = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result_username) > 0) {
    // Username already exists, redirect to the registration page with an error
    header("Location: add_user.php?error=userexists");
    exit();
} else {
    // Hash the password before saving to the database
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user into the database using a prepared statement
    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = mysqli_prepare($mysqli, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $username, $hashed_password);

    if (mysqli_stmt_execute($stmt)) {
        // Set session and redirect to the main page
        $_SESSION['username'] = $username;
        $_SESSION['user_id'] = mysqli_insert_id($mysqli);  // Get the ID of the inserted user
        header("Location: main_page.php");
        exit();
    } else {
        // Error occurred during insertion
        echo "Error: " . mysqli_error($mysqli);
    }
}

// Close the statement and connection
mysqli_stmt_close($stmt);
mysqli_close($mysqli);
?>
