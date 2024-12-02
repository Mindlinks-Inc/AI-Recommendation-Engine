<?php
$firstName = $_GET['first_name'];
$lastName = $_GET['last_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeuralRoots Assessment</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($firstName . ' ' . $lastName); ?>! Let's begin with the assessment.</h1>
        <div id="assessment-container">
            <!-- Your assessment form goes here -->
        </div>
    </div>
</body>
</html>
