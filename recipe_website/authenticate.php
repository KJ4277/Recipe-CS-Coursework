<?php
// Include database connection
include_once 'link_recipe_website.php';
session_start();

// Get data from the form
$username = $_POST['username'];
$password = $_POST['password'];

// Query to find the user by username
$sql = "SELECT * FROM users WHERE username='$username'";
$result = mysqli_query($mysqli, $sql);

if (mysqli_num_rows($result) > 0) {
    // User exists
    $user = mysqli_fetch_assoc($result);
    
    // Compare the plain text password directly
    if ($password == $user['password']) {
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
?>
