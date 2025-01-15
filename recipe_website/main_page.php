<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filter Meals</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #d4b18b;
            display: flex;
            justify-content: flex-start;
        }

        /* Container for layout (search bar on left, meals on right) */
        .container {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }

        /* Left side (search bar and filters) */
        .filters-container {
            width: 30%;
            padding: 20px;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            margin: 20px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .search-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 60px;
            margin-bottom: 20px;
        }

        .search-container input[type="text"] {
            width: 80%;
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

        .meals-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
            width: 65%;
            margin: 20px;
        }

        .meal-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            text-align: center;
            padding: 20px;
        }

        .meal-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .meal-card h2 {
            font-size: 1.2rem;
            margin: 10px 0;
        }

        .meal-card button {
            margin-top: 10px;
            padding: 8px 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .meal-card button:hover {
            background-color: #0056b3;
        }

        .meal-details {
            text-align: left;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Filters and search bar on the left -->
        <div class="filters-container">
            <div class="search-container">
                <input type="text" id="userInput" name="userInput" placeholder="Enter an ingredient" />
                <input type="submit" onclick="fetchFilterMeal();" value="Search" />
            </div>
            <h3>Dietary Preferences</h3>
            <label>
                <input type="checkbox" id="vegetarianFilter"> Vegetarian
            </label><br>
            <label>
                <input type="checkbox" id="veganFilter"> Vegan
            </label>
        </div>

        <!-- Meals on the right side -->
        <div id="mealsContainer" class="meals-container"></div>
    </div>

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
                                const filteredMeals = mealsData
                                    .map(mealData => mealData.meals[0])
                                    .filter(meal => {
                                        if (vegetarianChecked && meal.strCategory !== 'Vegetarian') return false;
                                        if (veganChecked && !isVegan(meal)) return false;
                                        return true;
                                    });

                                if (filteredMeals.length) {
                                    filteredMeals.forEach(displayMealCard);
                                } else {
                                    mealsContainer.innerHTML = '<p>No meals match the selected filters.</p>';
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

        function isVegan(meal) {
            const nonVeganIngredients = ['Beef', 'Chicken', 'Pork', 'Fish', 'Cheese', 'Milk', 'Egg', 'Butter', 'Honey'];
            for (let i = 1; i <= 20; i++) {
                const ingredient = meal[`strIngredient${i}`];
                if (ingredient && nonVeganIngredients.some(item => ingredient.includes(item))) {
                    return false;
                }
            }
            return true;
        }

        function displayMealCard(meal) {
            const mealCard = document.createElement('div');
            mealCard.classList.add('meal-card');

            mealCard.innerHTML = `
                <img src="${meal.strMealThumb}" alt="${meal.strMeal}">
                <h2>${meal.strMeal}</h2>
                <p><strong>Category:</strong> ${meal.strCategory}</p>
                <p><strong>Area:</strong> ${meal.strArea}</p>
                <button onclick="fetchMealDetails(${meal.idMeal}, this)">View Details</button>
                <div class="meal-details"></div>
            `;

            mealsContainer.appendChild(mealCard);
        }

        function fetchMealDetails(idMeal, button) {
            const detailsDiv = button.nextElementSibling;

            if (detailsDiv.style.display === 'block') {
                detailsDiv.style.display = 'none';
                button.textContent = 'View Details';
                return;
            }

            fetch(`https://www.themealdb.com/api/json/v1/1/lookup.php?i=${idMeal}`)
                .then(response => response.json())
                .then(data => {
                    const mealDetails = data.meals[0];
                    const instructions = mealDetails.strInstructions.split(/(?<=\.)\s+/)
                        .map(step => `<li>${step.trim()}</li>`)
                        .join('');

                    detailsDiv.innerHTML = `
                        <p><strong>Instructions:</strong></p>
                        <ol>${instructions}</ol>
                    `;
                    detailsDiv.style.display = 'block';
                    button.textContent = 'Hide Details';
                })
                .catch(error => {
                    console.error('Error fetching meal details:', error);
                    alert('Sorry, there was an error fetching the meal details.');
                });
        }
    </script>
</body>
</html>
