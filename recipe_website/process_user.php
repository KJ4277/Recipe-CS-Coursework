<?php
// Include database connection
include_once 'link_recipe_website.php';

// Get data from the form
$username = $_POST['username'];
$email = $_POST['email'];
$password = $_POST['password']; // Keeping the password as plain text

// Check if the username already exists
$sql_username = "SELECT * FROM users WHERE username = '$username'";
$result_username = mysqli_query($mysqli, $sql_username);

if (mysqli_num_rows($result_username) > 0) {
    // Username already exists
    $message = "The username is already taken. Please choose another one.";
    $alert_class = "alert-danger";
} else {
    // Check if the email already exists
    $sql_email = "SELECT * FROM users WHERE email = '$email'";
    $result_email = mysqli_query($mysqli, $sql_email);

    if (mysqli_num_rows($result_email) > 0) {
        // Email already exists
        $message = "The email address is already registered. Please use a different one.";
        $alert_class = "alert-danger";
    } else {
        // Insert the new user into the database
        $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";

        if (mysqli_query($mysqli, $sql)) {
            // Successfully added user
            $message = "User added successfully! You can now <a href='login.php'>login here</a>.";
            $alert_class = "alert-success";
        } else {
            // Error during insertion
            $message = "Error: " . mysqli_error($mysqli);
            $alert_class = "alert-danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Registration Status - Recipe Website</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: rgb(219, 190, 147);
            font-family: Arial, sans-serif;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .login-container h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        .login-container button {
            width: 100%;
        }
        .login-container .alert {
            text-align: center;
        }
        .text-center a {
            color: #007bff;
        }
        .text-center a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h1>Account Registration</h1>

    <!-- Display success or error message -->
    <div class="alert <?php echo $alert_class; ?>" role="alert">
        <?php echo $message; ?>
    </div>

</div>

</body>
</html>
