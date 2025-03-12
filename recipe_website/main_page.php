<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
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
</head>
<body>
    <!-- Account Button -->
    <a href="account.html" class="account-button">
        <img src="accountlogo.png" alt="Account">
      </a>

    <!-- Search bar -->
    <div class="search-container">
        <input type="text" id="userInput" name="userInput" placeholder="Enter an ingredient" />
        <input type="submit" onclick="fetchFilterMeal();" value="Search" />
    </div>

    <div class="filters-suggestions-wrapper">
        <!-- Suggestions container -->
        <div id="suggestionsContainer" class="suggestions-container"></div>

        <!-- Filter options -->
        <div class="filters-container">
            <label class="filter-option">
                <input type="checkbox" id="vegetarianFilter" name="dietFilter" value="vegetarian">
                Vegetarian
            </label>
            <label class="filter-option">
                <input type="checkbox" id="veganFilter" name="dietFilter" value="vegan">
                Vegan
            </label>
        </div>
    </div>

    <!-- Meals display area -->
    <div id="mealsContainer" class="meals-container"></div>

    <script>
        const mealsContainer = document.getElementById('mealsContainer');

        // Lists of known non-vegetarian and non-vegan ingredients
        const nonVegetarianIngredients = ['chicken', 'beef', 'pork', 'fish', 'duck', 'lamb', 'bacon',
                                          'salmon', 'cod', 'prawns', 'king_prawn', 'shrimp', 'haddock',
                                           'sausage', 'ham', 'herring'];

        const nonVeganIngredients = ['egg', 'eggs', 'cheese', 'milk', 'butter', 'cream', 'mozzarella', 
                                     'parmesan', 'ricotta', 'paneer', 'feta', 'mayonnaise', 'cream', 
                                     'ricotta'];

        function fetchFilterMeal() {
            const userInput = document.getElementById('userInput').value.trim();
            const vegetarianFilter = document.getElementById('vegetarianFilter').checked;
            const veganFilter = document.getElementById('veganFilter').checked;

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

                                // Apply filters if any are selected
                                if (vegetarianFilter || veganFilter) {
                                    filteredMeals = filteredMeals.filter(meal => {
                                        const ingredients = getIngredients(meal);

                                        if (vegetarianFilter && containsNonVegetarian(ingredients)) {
                                            return false;
                                        }

                                        if (veganFilter && (containsNonVegetarian(ingredients) || containsNonVegan(ingredients))) {
                                            return false;
                                        }

                                        return true;
                                    });
                                }

                                if (filteredMeals.length > 0) {
                                    filteredMeals.forEach(meal => displayMealCard(meal));
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

        function displayMealCard(meal) {
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
                    <button class="save-recipe-btn">&#9733;</button>
                </div>
                <div class="instructions">
                    <p><strong>Instructions:</strong></p>
                    <ol>${formatInstructions(meal.strInstructions)}</ol>
                </div>
                <button class="toggle-instructions-btn" onclick="toggleInstructions(this)">Show Instructions</button>
            `;

            mealsContainer.appendChild(mealCard);
        }

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

        function displayRandomSuggestions() {
            const suggestionsContainer = document.getElementById('suggestionsContainer');

            // Fetch the list of all ingredients from the API
            fetch('https://www.themealdb.com/api/json/v1/1/list.php?i=list')
                .then(response => response.json())
                .then(data => {
                    if (data.meals) {
                        const ingredients = data.meals.map(meal => meal.strIngredient);

                        // Shuffle and pick 5 random ingredients
                        const randomSuggestions = ingredients
                            .sort(() => 0.5 - Math.random())
                            .slice(0, 5);

                        // Render suggestions
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
            fetchFilterMeal(); // Trigger the search
        }

        // Call displayRandomSuggestions on page load
        document.addEventListener('DOMContentLoaded', displayRandomSuggestions);
    </script>
</body>
</html>
