<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Finder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #d4b18b;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .search-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 40px;
            width: 60%;
        }

        .search-container input[type="text"] {
            width: 70%;
            padding: 10px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }

        .search-container input[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }

        .search-container input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .filters-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .filters-container label {
            font-size: 1rem;
            display: flex;
            align-items: center;
        }

        .filters-container input[type="checkbox"] {
            margin-right: 5px;
        }

        .meals-container {
            width: 60%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .meal-card {
            background-color: white;
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .meal-card .meal-header {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 20px;
        }

        .meal-card img {
            width: 150px;  /* Fixed image size */
            height: 150px; /* Ensure all images are the same size */
            object-fit: cover;
            border-radius: 10px;
        }

        .meal-card .meal-details {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            width: 60%;
        }

        .meal-card h2 {
            margin-top: 0;
            font-size: 1.5rem;
        }

        .meal-card p {
            margin: 5px 0;
        }

        ol {
            padding-left: 20px;
        }

        .instructions {
            display: none;
            margin-top: 10px;
        }
        .toggle-instructions-btn {
            margin-top: 10px;
            padding: 6px 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            width: 200px;
            height: 35px;
            text-align: center;
            display: inline-flex; /* Inline-flex for aligning text horizontally */
            justify-content: center; /* Center the text horizontally */
            align-items: center; /* Vertically center the text */
            gap: 5px; /* Space between "Show" and "Instructions" */
        }

        .toggle-instructions-btn:hover {
            background-color: #0056b3;
        }


    </style>
</head>
<body>
    <!-- Search bar -->
    <div class="search-container">
        <input type="text" id="userInput" name="userInput" placeholder="Enter an ingredient" />
        <input type="submit" onclick="fetchFilterMeal();" value="Search" />
    </div>

    <!-- Filter options -->
    <div class="filters-container">
        <label><input type="checkbox" id="vegetarianFilter"> Vegetarian</label>
        <label><input type="checkbox" id="veganFilter"> Vegan</label>
    </div>

    <!-- Meals display area -->
    <div id="mealsContainer" class="meals-container"></div>

    <script>
        const mealsContainer = document.getElementById('mealsContainer');

        function fetchFilterMeal() {
            const userInput = document.getElementById('userInput').value.trim();
            const vegetarianChecked = document.getElementById('vegetarianFilter').checked;
            const veganChecked = document.getElementById('veganFilter').checked;

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
                                const filteredMeals = mealsData.filter(mealData => {
                                    const meal = mealData.meals[0];
                                    if (vegetarianChecked && !meal.strTags?.includes('Vegetarian')) return false;
                                    if (veganChecked && !meal.strTags?.includes('Vegan')) return false;
                                    return true;
                                });

                                if (filteredMeals.length > 0) {
                                    filteredMeals.forEach(mealData => displayMealCard(mealData.meals[0]));
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
    </script>
</body>
</html>
