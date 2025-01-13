<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recipe Website</title>
</head>
<body>
    <?php
       echo nl2br("linked text from index.php. \n"); 

       // Include the database connection
       include_once 'link_recipe_website.php';

       // SQL query to insert data
       $sql = "INSERT INTO users (username, email, password)
               VALUES ('Krish', '123@gmail.com', 'qwerty')";
    
        // Execute the query
       if (mysqli_query($mysqli, $sql)) {
        echo "Record inserted successfully!";
    } else {
        echo "Error inserting record: " . mysqli_error($mysqli);
    }

    ?>
</body>
</html>













