<?php
// Include database connection
include_once 'link_recipe_website.php';
session_start();

// Get data from the form
$username = $_POST['username'];
$password = $_POST['password'];

// Server-side validation
if (strlen($username) < 5 || strlen($username) > 20 || !preg_match('/^[a-zA-Z0-9]+$/', $username)) {
    // Invalid username
    header("Location: login.php?error=1");
    exit();
}

if (strlen($password) < 8 || strlen($password) > 30 || !preg_match('/^[a-zA-Z0-9]+$/', $password)) {
    // Invalid password
    header("Location: login.php?error=1");
    exit();
}

// Query to find the user by username using prepared statements
$sql = "SELECT * FROM users WHERE username = ?";
$stmt = mysqli_prepare($mysqli, $sql);

if ($stmt) {
    // Bind the username parameter
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        // User exists
        $user = mysqli_fetch_assoc($result);

        // Verify the password using password_verify()
        if (password_verify($password, $user['password'])) {
            // Password matches, set session variables and redirect
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Redirect to the main page
            header("Location: main_page.php");
            exit();  // Ensure no further code is executed after the redirect
        } else {
            // Password doesn't match, redirect back to login page with error
            header("Location: login.php?error=1");
            exit();
        }
    } else {
        // User doesn't exist, redirect back to login page with error
        header("Location: login.php?error=1");
        exit();
    }

    // Close the statement
    mysqli_stmt_close($stmt);
} else {
    // Handle SQL statement preparation error
    die("Database error: Unable to prepare statement.");
}

// Close the database connection
mysqli_close($mysqli);
?>
