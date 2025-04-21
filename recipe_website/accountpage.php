
<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Database connection
$db = new mysqli('localhost', 'root', '', 'recipe_website');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$user_id = $_SESSION['user_id'];

// Get user details
$user_query = $db->prepare("SELECT username FROM users WHERE user_id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user = $user_result->fetch_assoc();

// Get favorite recipes
$favorites_query = $db->prepare("SELECT meal_id FROM favorites WHERE user_id = ?");
$favorites_query->bind_param("i", $user_id);
$favorites_query->execute();
$favorites_result = $favorites_query->get_result();
$favorite_meal_ids = $favorites_result->fetch_all(MYSQLI_ASSOC);

// Fetch meal details from API
$favorites = [];
foreach ($favorite_meal_ids as $fav) {
    $meal_id = $fav['meal_id'];
    $api_response = file_get_contents("https://www.themealdb.com/api/json/v1/1/lookup.php?i=$meal_id");
    if ($api_response) {
        $data = json_decode($api_response, true);
        if ($data && isset($data['meals'][0])) {
            $favorites[] = $data['meals'][0];
        }
    }
}

// Handle form submissions
$update_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle account updates
    if (isset($_POST['update_account'])) {
        $new_username = trim($_POST['username']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        
        if (empty($new_username)) {
            $update_message = '<div class="alert alert-danger">Username cannot be empty</div>';
        } else {
            // Verify current password if changing password
            if (!empty($new_password)) {
                $check = $db->prepare("SELECT password FROM users WHERE user_id = ?");
                $check->bind_param("i", $user_id);
                $check->execute();
                $check->bind_result($hashed_password);
                $check->fetch();
                $check->close();
                
                if (!password_verify($current_password, $hashed_password)) {
                    $update_message = '<div class="alert alert-danger">Current password is incorrect</div>';
                }
            }
            
            if (empty($update_message)) {
                // Update account
                if (!empty($new_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update = $db->prepare("UPDATE users SET username = ?, password = ? WHERE user_id = ?");
                    $update->bind_param("ssi", $new_username, $hashed_password, $user_id);
                } else {
                    $update = $db->prepare("UPDATE users SET username = ? WHERE user_id = ?");
                    $update->bind_param("si", $new_username, $user_id);
                }
                
                if ($update->execute()) {
                    $_SESSION['username'] = $new_username;
                    $update_message = '<div class="alert alert-success">Account updated successfully!</div>';
                    $user['username'] = $new_username;
                } else {
                    $update_message = '<div class="alert alert-danger">Error updating account</div>';
                }
                $update->close();
            }
        }
    }
    // Handle favorite removal
    elseif (isset($_POST['action']) && $_POST['action'] === 'remove_favorite') {
        $meal_id = (int)$_POST['meal_id'];
        $delete = $db->prepare("DELETE FROM favorites WHERE user_id = ? AND meal_id = ?");
        $delete->bind_param("ii", $user_id, $meal_id);
        if ($delete->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        $delete->close();
        exit();
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Recipe Finder</title>
    <link rel="stylesheet" href="recipestyle.css">
    <style>
        .account-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .account-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        .back-button, .logout-button {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }
        .back-button {
            background: #f0f0f0;
            color: #333;
        }
        .logout-button {
            background: #dc3545;
            color: white;
            border: none;
            cursor: pointer;
        }
        .account-sections {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }
        .user-details-form input {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .update-button {
            background: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .saved-recipes {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }
        .meal-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        .meal-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .meal-info {
            padding: 1rem;
        }
        .remove-favorite {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem;
            cursor: pointer;
            float: right;
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="account-container">
        <div class="account-header">
            <a href="main_page.php" class="back-button">← Back to Recipes</a>
            <button class="logout-button" onclick="window.location.href='?logout=1'">Logout</button>
        </div>
        
        <div class="account-sections">
            <div class="user-details">
                <h2>Account Settings</h2>
                <?php if (isset($update_message)) echo $update_message; ?>
                <form class="user-details-form" method="POST">
                    <div>
                        <label>Username</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div>
                        <label>Current Password (for changes)</label>
                        <input type="password" name="current_password">
                    </div>
                    <div>
                        <label>New Password (leave blank to keep)</label>
                        <input type="password" name="new_password">
                    </div>
                    <button type="submit" name="update_account" class="update-button">Save Changes</button>
                </form>
            </div>
            
            <div class="saved-recipes-section">
                <h2>Your Saved Recipes</h2>
                <div class="saved-recipes">
                    <?php if (empty($favorites)): ?>
                        <p>No saved recipes yet. Save recipes by clicking the star icon.</p>
                    <?php else: ?>
                        <?php foreach ($favorites as $meal): ?>
                            <div class="meal-card">
                                <img src="<?php echo $meal['strMealThumb']; ?>" alt="<?php echo htmlspecialchars($meal['strMeal']); ?>" class="meal-image">
                                <div class="meal-info">
                                    <h3><?php echo htmlspecialchars($meal['strMeal']); ?></h3>
                                    <p><?php echo $meal['strCategory']; ?></p>
                                    <button class="remove-favorite" data-meal-id="<?php echo $meal['idMeal']; ?>">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle favorite removal
        document.querySelectorAll('.remove-favorite').forEach(button => {
            button.addEventListener('click', function() {
                if (!confirm('Remove this recipe from your favorites?')) return;
                
                const mealId = this.getAttribute('data-meal-id');
                const card = this.closest('.meal-card');
                
                fetch('account.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=remove_favorite&meal_id=${mealId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        card.style.transition = 'opacity 0.3s';
                        card.style.opacity = '0';
                        setTimeout(() => card.remove(), 300);
                    } else {
                        alert('Failed to remove favorite');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error removing favorite');
                });
            });
        });
    </script>
</body>
</html>
