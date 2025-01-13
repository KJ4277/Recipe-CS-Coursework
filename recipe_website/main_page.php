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

        /* Container for the layout (searchbar on left, meals on right) */
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
            height: 60px; /* Fixed height to prevent scaling */
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
        </div>

        <!-- Meals on the right side -->
        <div id="mealsContainer" class="meals-container"></div>
    </div>

    <script>
        // Function to fetch meals based on an ingredient and apply filters
        function fetchFilterMeal() {
            const userInput = document.getElementById('userInput').value;

            if (!userInput) {
                alert('Please enter an ingredient');
                return;
            }

            fetch(`https://www.themealdb.com/api/json/v1/1/filter.php?i=${userInput}`)
                .then(response => response.json())
                .then(data => {
                    if (data.meals && data.meals.length > 0) {
                        const mealsContainer = document.getElementById('mealsContainer');
                        mealsContainer.innerHTML = ''; // Clear previous results

                        data.meals.forEach(meal => {
                            const mealCard = document.createElement('div');
                            mealCard.classList.add('meal-card');

                            mealCard.innerHTML = `
                                <img src="${meal.strMealThumb}" alt="${meal.strMeal}">
                                <h2>${meal.strMeal}</h2>
                                <button onclick="fetchMealDetails(${meal.idMeal}, this)">View Details</button>
                                <div class="meal-details"></div>
                            `;

                            mealsContainer.appendChild(mealCard);
                        });
                    } else {
                        document.getElementById('mealsContainer').innerHTML = '<p>No meals found for this ingredient.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching meals:', error);
                    document.getElementById('mealsContainer').innerHTML = '<p>Sorry, there was an error fetching the meals.</p>';
                });
        }

        // Function to fetch detailed meal information and display in the card
        function fetchMealDetails(idMeal, button) {
            const detailsDiv = button.nextElementSibling;

            // Toggle visibility of the meal details section
            if (detailsDiv.style.display === 'block') {
                detailsDiv.style.display = 'none';
                button.textContent = 'View Details';
                return;
            }

            fetch(`https://www.themealdb.com/api/json/v1/1/lookup.php?i=${idMeal}`)
                .then(response => response.json())
                .then(data => {
                    const mealDetails = data.meals[0];

                    // Split instructions into steps and create a list
                    const steps = mealDetails.strInstructions
                        .split(/(?<=\.)\s+/)  // Split by period followed by whitespace
                        .map(step => step.trim())
                        .filter(Boolean);

                    // Create list HTML from steps
                    const formattedInstructions = `
                        <ol>
                            ${steps.map(step => `<li>${step}</li>`).join('')}
                        </ol>
                    `;

                    detailsDiv.innerHTML = `
                        <p><strong>Category:</strong> ${mealDetails.strCategory}</p>
                        <p><strong>Area:</strong> ${mealDetails.strArea}</p>
                        <p><strong>Instructions:</strong><br>${formattedInstructions}</p>
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
