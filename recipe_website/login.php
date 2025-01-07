<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Recipe Website</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color:rgb(219, 190, 147);
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
    </style>
</head>
<body>

<div class="login-container">
    <h1>Login</h1>
    
    <?php
    // Display any error message if provided
    if (isset($_GET['error'])) {
        echo "<div class='alert alert-danger'>Invalid username or password.</div>";
    }
    ?>

    <form action="authenticate.php" method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" class="form-control" name="username" id="username" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" name="password" id="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
    
    <div class="text-center mt-3">
        <a href="add_user.php">Don't have an account? Register here.</a>
    </div>
</div>

</body>
</html>