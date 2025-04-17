<?php
session_start();
ob_start();

// Check if the user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

// Database connection for favorites
$db = new mysqli('localhost', 'root', '', 'recipe_website');
if ($db->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Handle favorite button submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meal_id'])) {
    header('Content-Type: application/json');
    ob_clean(); // clean any buffer before outputting JSON

    $user_id = (int)$_SESSION['user_id'];
    $meal_id = preg_replace('/\D/', '', $_POST['meal_id']); // keep only digits

    if (empty($meal_id)) {
        echo json_encode(['error' => 'Invalid meal ID']);
        exit();
    }

    // Check if the meal is already favorited
    $check = $db->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND meal_id = ?");
    $check->bind_param("ii", $user_id, $meal_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $delete = $db->prepare("DELETE FROM favorites WHERE user_id = ? AND meal_id = ?");
        $delete->bind_param("ii", $user_id, $meal_id);
        $delete->execute();
        echo json_encode(['status' => 'removed']);
    } else {
        $insert = $db->prepare("INSERT INTO favorites (user_id, meal_id) VALUES (?, ?)");
        $insert->bind_param("ii", $user_id, $meal_id);
        $insert->execute();
        echo json_encode(['status' => 'added']);
    }

    $check->close();
    $db->close();
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Finder</title>
    <link rel="stylesheet" href="recipestyle.css">
    <!-- NEW: Favorite button style -->
    <style>
        .save-recipe-btn.favorited {
            color: gold;
            text-shadow: 0 0 3px rgba(0,0,0,0.5);
        }
    </style>
</head>
<body>
    <!-- Account Button (UNCHANGED) -->
    <a href="account.html" class="account-button">
        <img src="accountlogo.png" alt="Account">
    </a>

    <!-- Search bar (UNCHANGED) -->
    <div class="search-container">
        <input type="text" id="userInput" name="userInput" placeholder="Enter an ingredient" />
        <input type="submit" onclick="fetchFilterMeal();" value="Search" />
    </div>

    <div class="filters-suggestions-wrapper">
        <!-- Suggestions container (UNCHANGED) -->
        <div id="suggestionsContainer" class="suggestions-container"></div>

        <!-- Filter options (UNCHANGED) -->
        <div class="filters-container">
            <label class="filter-option">
                <input type="checkbox" id="vegetarianFilter" name="dietFilter" value="vegetarian">
                Vegetarian
            </label>
            <label class="filter-option">
                <input type="checkbox" id="veganFilter" name="dietFilter" value="vegan">
                Vegan
            </label>
            <label class="filter-option">
                <input type="checkbox" id="glutenfreeFilter" name="dietFilter" value="glutenfree">
                Gluten-Free
            </label>
        </div>
    </div>

    <!-- Meals display area (UNCHANGED) -->
    <div id="mealsContainer" class="meals-container"></div>

    <script>
        // UNCHANGED VARIABLES
        const mealsContainer = document.getElementById('mealsContainer');
        const nonVegetarianIngredients = ['chicken', 'beef', 'pork', 'fish', 'duck', 'lamb', 'bacon',
                                      'salmon', 'cod', 'prawns', 'king_prawn', 'shrimp', 'haddock',
                                       'sausage', 'ham', 'herring'];
        const nonVeganIngredients = ['egg', 'eggs', 'cheese', 'milk', 'butter', 'cream', 'mozzarella', 
                                   'parmesan', 'ricotta', 'paneer', 'feta', 'mayonnaise', 'cream', 
                                   'ricotta'];
        const glutenIngredients = ['wheat', 'barley', 'rye', 'triticale', 'malt', 'spelt', 'farro', 
                                 'kamut', 'semolina', 'couscous', 'soy sauce', 'teriyaki sauce', 
                                 'stout', 'breadcrumbs', 'croutons', 'flour', 'pastry', 'pasta', 
                                 'noodles', 'cake', 'cookies', 'crackers', 'pretzels', 'pie crust', 
                                 'pizza dough', 'wraps', 'tortillas', 'stuffing', 'dressing', 
                                 'digestive biscuits'];

        // NEW: Favorite toggle function
        const userId = <?php echo json_encode($_SESSION['user_id']); ?>;

        function toggleFavorite(button, mealId) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `meal_id=${encodeURIComponent(mealId)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'added' || data.status === 'removed') {
                    button.classList.toggle('favorited', data.status === 'added');
                } else {
                    console.error("Unexpected response:", data);
                }
            })
            .catch(error => {
                console.error('Error toggling favorite:', error);
            });
        }

        // MODIFIED: Now checks favorites before display
        function fetchFilterMeal() {
            const userInput = document.getElementById('userInput').value.trim();
            const vegetarianFilter = document.getElementById('vegetarianFilter').checked;
            const veganFilter = document.getElementById('veganFilter').checked;
            const glutenfreeFilter = document.getElementById('glutenfreeFilter').checked;

            if (!userInput) {
                alert('Please enter an ingredient');
                return;
            }

            fetch(`https://www.themealdb.com/api/json/v1/1/filter.php?i=${encodeURIComponent(userInput)}`)
                .then(response => response.json())
                .then(data => {
                    mealsContainer.innerHTML = '';

                    if (data.meals) {
                        const promises = data.meals.map(meal =>
                            fetch(`https://www.themealdb.com/api/json/v1/1/lookup.php?i=${meal.idMeal}`)
                                .then(response => response.json())
                        );

                        Promise.all(promises)
                            .then(mealsData => {
                                let filteredMeals = mealsData.map(mealData => mealData.meals[0]);

                                if (vegetarianFilter || veganFilter || glutenfreeFilter) {
                                    filteredMeals = filteredMeals.filter(meal => {
                                        const ingredients = getIngredients(meal);

                                        if (vegetarianFilter && containsNonVegetarian(ingredients)) return false;
                                        if (veganFilter && (containsNonVegetarian(ingredients) || containsNonVegan(ingredients))) return false;
                                        if (glutenfreeFilter && containsGluten(ingredients)) return false;

                                        return true;
                                    });
                                }

                                if (filteredMeals.length > 0) {
                                    fetch('get_favorites.php')
                                        .then(response => response.json())
                                        .then(favorites => {
                                            if (!Array.isArray(favorites)) {
                                                console.warn("Unexpected favorites response:", favorites);
                                                favorites = [];
                                            }

                                            filteredMeals.forEach(meal => {
                                                const isFavorited = favorites.some(fav => fav.meal_id === meal.idMeal);
                                                displayMealCard(meal, isFavorited);
                                            });
                                        })
                                        .catch(error => {
                                            console.error("Error loading favorites:", error);
                                            filteredMeals.forEach(meal => {
                                                displayMealCard(meal, false);
                                            });
                                        });
                                } else {
                                    mealsContainer.innerHTML = '<p>No meals found for the selected filters.</p>';
                                }
                            });
                    } else {
                        mealsContainer.innerHTML = '<p>No meals found for this ingredient.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching meals:', error);
                    mealsContainer.innerHTML = '<p>Sorry, there was an error fetching the meals.</p>';
                });
        }

        // MODIFIED: Added isFavorited parameter
        function displayMealCard(meal, isFavorited = false) {
            const mealCard = document.createElement('div');
            mealCard.classList.add('meal-card');

            const ingredients = getIngredients(meal);

            mealCard.innerHTML = `
                <div class="meal-header">
                    <img src="${meal.strMealThumb}" alt="${meal.strMeal}">
                    <div class="meal-details">
                        <h2>${meal.strMeal}</h2>
                        <p><strong>Category:</strong> ${meal.strCategory}</p>
                        <p><strong>Area:</strong> ${meal.strArea}</p>
                        <p><strong>Ingredients:</strong> ${ingredients.join(', ')}</p>
                    </div>
                    <button class="save-recipe-btn ${isFavorited ? 'favorited' : ''}" 
                            onclick="toggleFavorite(this, '${meal.idMeal}')">&#9733;</button>
                </div>
                <div class="instructions">
                    <p><strong>Instructions:</strong></p>
                    <ol>${formatInstructions(meal.strInstructions)}</ol>
                </div>
                <button class="toggle-instructions-btn" onclick="toggleInstructions(this)">Show Instructions</button>
            `;

            mealsContainer.appendChild(mealCard);
        }

        // ALL ORIGINAL FUNCTIONS REMAIN EXACTLY THE SAME
        function getIngredients(meal) {
            const ingredients = [];
            for (let i = 1; i <= 20; i++) {
                const ingredient = meal[`strIngredient${i}`];
                const measure = meal[`strMeasure${i}`];
                if (ingredient && ingredient.trim() !== '') {
                    ingredients.push(`${measure.trim()} ${ingredient.trim()}`);
                }
            }
            return ingredients;
        }

        function formatInstructions(instructions) {
            return instructions.split(/(?<=\.)\s+/)
                .map(step => `<li>${step.trim()}</li>`)
                .join('');
        }

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

        function containsNonVegetarian(ingredients) {
            return ingredients.some(ingredient =>
                nonVegetarianIngredients.some(nonVeg => ingredient.toLowerCase().includes(nonVeg))
            );
        }

        function containsNonVegan(ingredients) {
            return ingredients.some(ingredient =>
                nonVeganIngredients.some(nonVegan => ingredient.toLowerCase().includes(nonVegan))
            );
        }

        function containsGluten(ingredients) {
            return ingredients.some(ingredient =>
                glutenIngredients.some(gluten => ingredient.toLowerCase().includes(gluten))
            );
        }

        function displayRandomSuggestions() {
            const suggestionsContainer = document.getElementById('suggestionsContainer');

            fetch('https://www.themealdb.com/api/json/v1/1/list.php?i=list')
                .then(response => response.json())
                .then(data => {
                    if (data.meals) {
                        const ingredients = data.meals.map(meal => meal.strIngredient);

                        const randomSuggestions = ingredients
                            .sort(() => 0.5 - Math.random())
                            .slice(0, 5);

                        suggestionsContainer.innerHTML = `
                            <p>Try these ingredients:</p>
                            <ul class="suggestions-list">
                                ${randomSuggestions.map(ingredient => `<li onclick="fillSearch('${ingredient}')">${ingredient}</li>`).join('')}
                            </ul>
                        `;
                    } else {
                        suggestionsContainer.innerHTML = '<p>Unable to fetch suggestions at the moment.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching ingredient suggestions:', error);
                    suggestionsContainer.innerHTML = '<p>Error fetching ingredient suggestions.</p>';
                });
        }

        function fillSearch(ingredient) {
            document.getElementById('userInput').value = ingredient;
            fetchFilterMeal();
        }

        document.addEventListener('DOMContentLoaded', displayRandomSuggestions);
    </script>
</body>
</html>
