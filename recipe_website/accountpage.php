<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
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

</head>
<body>
    <div class="account-container">
        <div class="account-header">
            <div>
                <a href="main_page.php" class="back-button">← Back to Recipes</a>
            </div>
            <div>
                <h1 class="account-title">My Account</h1>
            </div>
            <div>
                <button class="logout-button" onclick="window.location.href='?logout=1'">Logout</button>
            </div>
        </div>
        
        <div class="account-content">
            <div class="account-section">
                <h2 class="section-title">Account Settings</h2>
                <?php if (!empty($update_message)) echo $update_message; ?>
                
                <form class="account-form" method="POST">
                    <div class="input-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo isset($user['username']) ? htmlspecialchars($user['username']) : ''; ?>" required>
                    </div>
                    
                    <div class="input-group">
                        <label for="current_password">Current Password (for changes)</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>
                    
                    <div class="input-group">
                        <label for="new_password">New Password (leave blank to keep)</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>
                    
                    <button type="submit" name="update_account" class="update-button">Save Changes</button>
                </form>
            </div>
            
            <div class="account-section">
                <h2 class="section-title">Saved Recipes</h2>
                
                <?php if (empty($favorites)): ?>
                    <p class="no-favorites">You haven't saved any recipes yet. Click the star icon on recipes to save them.</p>
                <?php else: ?>
                    <div class="saved-recipes">
                        <?php foreach ($favorites as $meal): 
                            $ingredients = [];
                            for ($i = 1; $i <= 20; $i++) {
                                $ingredient = $meal['strIngredient'.$i];
                                $measure = $meal['strMeasure'.$i];
                                if ($ingredient && trim($ingredient) !== '') {
                                    $ingredients[] = trim($measure).' '.trim($ingredient);
                                }
                            }
                        ?>
                            <div class="meal-card">
                                <div class="meal-header">
                                    <img src="<?php echo $meal['strMealThumb']; ?>" alt="<?php echo htmlspecialchars($meal['strMeal']); ?>">
                                    <div class="meal-details">
                                        <h2><?php echo htmlspecialchars($meal['strMeal']); ?></h2>
                                        <p><strong>Category:</strong> <?php echo $meal['strCategory']; ?></p>
                                        <p><strong>Area:</strong> <?php echo $meal['strArea']; ?></p>
                                        <p><strong>Ingredients:</strong> <?php echo implode(', ', array_slice($ingredients, 0, 5)); ?></p>
                                    </div>
                                    <button class="save-recipe-btn favorited" 
                                            onclick="removeFavorite(this, '<?php echo $meal['idMeal']; ?>')">
                                        ★
                                    </button>
                                </div>
                                <div class="instructions">
                                    <p><strong>Instructions:</strong></p>
                                    <ol><?php 
                                        $instructions = preg_split('/(?<=[.?!])\s+/', $meal['strInstructions'], -1, PREG_SPLIT_NO_EMPTY);
                                        foreach ($instructions as $step) {
                                            echo '<li>'.htmlspecialchars(trim($step)).'</li>';
                                        }
                                    ?></ol>
                                </div>
                                <button class="toggle-instructions-btn" onclick="toggleInstructions(this)">Show Instructions</button>

                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Handle favorite removal
        function removeFavorite(button, mealId) {
            if (!confirm('Remove this recipe from your favorites?')) return;
            
            const mealCard = button.closest('.meal-card');
            
            fetch('accountpage.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove_favorite&meal_id=${mealId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mealCard.style.transition = 'opacity 0.3s';
                    mealCard.style.opacity = '0';
                    setTimeout(() => mealCard.remove(), 300);
                    
                    // Show message if no favorites left
                    if (document.querySelectorAll('.meal-card').length === 0) {
                        document.querySelector('.saved-recipes').innerHTML = 
                            '<p class="no-favorites">You haven\'t saved any recipes yet. Click the star icon on recipes to save them.</p>';
                    }
                } else {
                    alert('Failed to remove favorite');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error removing favorite');
            });
        }

        // Toggle instructions visibility
        function toggleInstructions(button) {
            const instructionsDiv = button.previousElementSibling;
            if (instructionsDiv.style.display === 'block') {
                instructionsDiv.style.display = 'none';
                button.textContent = 'Show Instructions';
            } else {
                instructionsDiv.style.display = 'block';
                button.textContent = 'Hide Instructions';
            }
        }
    </script>
</body>
</html>
