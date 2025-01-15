<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filter Meals</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #d4b18b;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: flex-start;
        }

        /* Container layout (search bar on left, meals on right) */
        .container {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }

        /* Left side for search and filters */
        .filters-container {
            width: 30%;
            padding: 20px;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            margin: 20px;
        }

        .search-container {
            display: flex;
            align-items: center;
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

        .search-container input[type="submit"]:hover,
        .search-container input[type="submit"]:focus {
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
            text-align: center;
            padding: 20px;
        }

        .meal-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .meal-card h2 {
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
        }

        .meal-card button:hover {
            background-color: #0056b3;
        }

        .meal-details {
            text-align: left;
            display: none;
        }

        .meal-card button:focus {
            outline: 2px solid #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Filters and search bar on the left -->
        <aside class="filters-container">
            <form class="search-container" onsubmit="fetchFilterMeal(); return false;">
                <input type="text" id="userInput" name="userInput" placeholder="Enter an ingredient" aria-label="Ingredient search">
                <input type="submit" value="Search">
            </form>
        </aside>

        <!-- Meals display on the right -->
        <section id="mealsContainer" class="meals-container"></section>
    </div>

    <script>
        const mealsContainer = document.getElementById('mealsContainer');

        // Function to fetch meals based on an ingredient
        function fetchFilterMeal() {
            const userInput = document.getElementById('userInput').value.trim();

            if (!userInput) {
                alert('Please enter an ingredient.');
                return;
            }

            fetch(`https://www.themealdb.com/api/json/v1/1/filter.php?i=${encodeURIComponent(userInput)}`)
                .then(response => response.json())
                .then(data => {
                    mealsContainer.innerHTML = ''; // Clear previous results

                    if (data.meals) {
                        data.meals.forEach(meal => displayMealCard(meal));
                    } else {
                        mealsContainer.innerHTML = '<p>No meals found for this ingredient.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching meals:', error);
                    mealsContainer.innerHTML = '<p>Sorry, there was an error fetching the meals.</p>';
                });
        }

        // Function to display each meal card
        function displayMealCard(meal) {
            const mealCard = document.createElement('div');
            mealCard.classList.add('meal-card');

            mealCard.innerHTML = `
                <img src="${meal.strMealThumb}" alt="${meal.strMeal}">
                <h2>${meal.strMeal}</h2>
                <button onclick="fetchMealDetails(${meal.idMeal}, this)">View Details</button>
                <div class="meal-details"></div>
            `;

            mealsContainer.appendChild(mealCard);
        }

        // Function to fetch and toggle detailed meal information
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
                    const instructions = mealDetails.strInstructions
                        .split(/(?<=\.)\s+/)
                        .map(step => `<li>${step.trim()}</li>`)
                        .join('');

                    detailsDiv.innerHTML = `
                        <p><strong>Category:</strong> ${mealDetails.strCategory}</p>
                        <p><strong>Area:</strong> ${mealDetails.strArea}</p>
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
