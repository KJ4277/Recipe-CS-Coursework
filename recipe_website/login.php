<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Recipe Website</title>
    <link rel="stylesheet" href="recipestyle.css">
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>
        
        <?php
            session_start();
            $db = new mysqli('localhost', 'root', '', 'recipe_website');

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $username = $db->real_escape_string($_POST['username']);
                $password = $_POST['password']; // You should hash this in production

                $result = $db->query("SELECT user_id, username, password FROM users WHERE username = '$username'");
                
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['user_id']; // Make sure this matches your column name
                        $_SESSION['username'] = $user['username'];
                        header("Location: main_page.php");
                        exit();
                    } else {
                        $error = "Invalid credentials";
                    }
                } else {
                    $error = "Invalid credentials";
                }
            }
        ?>

        <form action="login.php" method="POST" onsubmit="return validateForm()">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
                <small id="username-error" style="color: #dc3545; font-size: 0.9rem;"></small>
            </div>
            
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <small id="password-error" style="color: #dc3545; font-size: 0.9rem;"></small>
            </div>
            
            <button type="submit" class="login-button">Login</button>
        </form>
        
        <a href="add_user.php" class="register-link">Don't have an account? Register here</a>
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
