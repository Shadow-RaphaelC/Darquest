<?php
require_once 'BD/bd.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles_dark.css">
    <title>DarQuest</title>

    <?php require 'include/header.php'; ?>
</head>

<body>
    <div class="btnBox">
        <a class="btnEnigma" href="#"> Engima </a>
    </div>
    <div style="justify-content: space-evenly; padding-left: 100px; padding-right: 100px;" class="btnBox">
        <a class="btnAutre" href=""> Autre </a>
        <a class="btnAutre" href="#"> Autre </a>
    </div>
    <?php require 'include/footer.php'; ?>
</body>

</html>