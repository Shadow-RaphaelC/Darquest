<?php
require_once 'include/session.php';
require_once 'BD/bd.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles_dark.css">
    <title>DarQuest</title>
</head>

<body>
    <?php require 'include/header.php'; ?>
    <main>
        <div class="btnBox">
            <a class="btnEnigma" href="magasin.php">Magasin</a>
        </div>
        <div class="btnBox" style="margin-top: -80px;">
            <a class="btnEnigma" href="enigma.php">Enigma</a>
        </div>
        <div class="btnBox">
            <?php
            $locked = (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) ? ' headerBtn locked' : '';
            ?>
            <a class="btnAutre<?php echo $locked; ?>" href="panier.php">Panier</a>
            <a class="btnAutre<?php echo $locked; ?>" href="inventaire.php">Inventaire</a>
        </div>
    </main>
    <?php require 'include/footer.php'; ?>
</body>

</html>