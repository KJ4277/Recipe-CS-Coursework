<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Recipe Website</title>
    <link rel="stylesheet" href="recipestyle.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>

<div class="login-container">
    <h1>Create Account</h1>

    <!-- Display error message if username already exists -->
    <?php
    if (isset($_GET['error']) && $_GET['error'] == "userexists") {
        echo "<div class='alert alert-danger'>Username already taken. Please choose another.</div>";
    }
    ?>

    <!-- Form for creating a new account -->
    <form action="process_user.php" method="POST" onsubmit="return validateForm()">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" class="form-control" name="username" id="username" required>
            <small id="username-error" class="text-danger"></small>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" name="password" id="password" required>
            <small id="password-error" class="text-danger"></small>
        </div>
        <button type="submit" class="btn btn-primary">Register</button>
    </form>

    <div class="text-center mt-3">
        <a href="login.php">Already have an account? Login here.</a>
    </div>
</div>

<script>
    function validateForm() {
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const usernameError = document.getElementById('username-error');
        const passwordError = document.getElementById('password-error');

        // Reset error messages
        usernameError.textContent = '';
        passwordError.textContent = '';

        let valid = true;

        // Username validation
        if (username.length < 5 || username.length > 20) {
            usernameError.textContent = 'Username must be between 5 and 20 characters.';
            valid = false;
        }
        if (!/^[a-zA-Z0-9]+$/.test(username)) {
            usernameError.textContent = 'Username can only contain letters and numbers.';
            valid = false;
        }

        // Password validation
        if (password.length < 8 || password.length > 30) {
            passwordError.textContent = 'Password must be between 8 and 30 characters.';
            valid = false;
        }
        if (!/^[a-zA-Z0-9]+$/.test(password)) {
            passwordError.textContent = 'Password can only contain letters and numbers.';
            valid = false;
        }

        return valid;
    }
</script>

</body>
</html>
