<?php
require_once 'BD/bd.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles_dark.css">
    <title>DarQuest Profil</title>

    <?php require 'include/header.php'; ?>
</head>

<body>
    <main>
        <h1>Profil</h1>
        <p>Votre profil.</p>
        <div class="btnBox" style="justify-content: center; gap: 20px;">
            <a class="btnAutre" href="index.php">Retour à l'accueil</a>
        </div>
    </main>
    <?php require 'include/footer.php'; ?>
</body>

</html>