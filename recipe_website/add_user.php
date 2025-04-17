<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Recipe Website</title>
    <link rel="stylesheet" href="recipestyle.css">
</head>
<body>

<div class="login-container">
    <h1>Create Account</h1>

    <?php
    if (isset($_GET['error']) && $_GET['error'] == "userexists") {
        echo "<div class='alert-danger'>Username already taken. Please choose another.</div>";
    }
    ?>

    <form action="process_user.php" method="POST" onsubmit="return validateForm()">
        <div class="input-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" required>
            <small id="username-error" class="text-danger"></small>
        </div>

        <div class="input-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>
            <small id="password-error" class="text-danger"></small>
        </div>

        <button type="submit" class="login-button">Register</button>
    </form>

    <a href="login.php" class="register-link">Already have an account? Login here.</a>
</div>

<script>
    function validateForm() {
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const usernameError = document.getElementById('username-error');
        const passwordError = document.getElementById('password-error');

        usernameError.textContent = '';
        passwordError.textContent = '';

        let valid = true;

        if (username.length < 5 || username.length > 20) {
            usernameError.textContent = 'Username must be between 5 and 20 characters.';
            valid = false;
        }
        if (!/^[a-zA-Z0-9]+$/.test(username)) {
            usernameError.textContent = 'Username can only contain letters and numbers.';
            valid = false;
        }

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
