<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$loggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $loggedIn ? ($_SESSION['username'] ?? 'Joueur') : 'Invité';

function navLink($href, $label, $enabled = true) {
    $class = $enabled ? 'headerBtn' : 'headerBtn locked';
    $hrefOut = $enabled ? $href : '#';
    return "<a class=\"$class\" href=\"$hrefOut\">$label</a>";
}
?>
<header>
    <div class="headerLogo">
        <a href="index.php"><img style="height: 220px;" src="img/DarQuestTitle_WHITE.png" alt="DarQuest Logo" class="logo"></a>
    </div>
    <div class="separatorBox">
        <img src="img/sep/qq_01_01.png" alt="logoLeft" class="sep">
        <img src="img/sep/qq_01_02.png" alt="logoCenter" class="sep">
        <img src="img/sep/qq_01_02.png" alt="logoCenter" class="sep">
        <img src="img/sep/qq_01_02.png" alt="logoCenter" class="sep">
        <img src="img/sep/qq_01_02.png" alt="logoCenter" class="sep">
        <img src="img/sep/qq_01_02.png" alt="logoCenter" class="sep">
        <img src="img/sep/qq_01_02.png" alt="logoCenter" class="sep">
        <img src="img/sep/qq_01_02.png" alt="logoCenter" class="sep">
        <img src="img/sep/qq_01_02.png" alt="logoCenter" class="sep">
        <img src="img/sep/qq_01_02.png" alt="logoCenter" class="sep">
        <img src="img/sep/qq_01_02.png" alt="logoCenter" class="sep">
        <img src="img/sep/qq_01_02.png" alt="logoCenter" class="sep">
        <img src="img/sep/qq_01_02.png" alt="logoCenter" class="sep">
        <img src="img/sep/qq_01_02.png" alt="logoCenter" class="sep">
        <img src="img/sep/qq_01_02.png" alt="logoCenter" class="sep">
        <img src="img/sep/qq_01_03.png" alt="logoRight" class="sep">
    </div>
    <div class="headerBox">
        <nav class="headerNavButSpecificallyForHomeButton">
            <?php echo navLink('index.php', 'Accueil'); ?>
            <?php echo navLink('magasin.php', 'Magasin'); ?>
            <?php echo navLink('enigma.php', 'Enigma'); ?>
            <?php echo navLink('panier.php', 'Panier', $loggedIn); ?>
            <?php echo navLink('inventaire.php', 'Inventaire', $loggedIn); ?>
            <?php echo navLink('profil.php', 'Profil', $loggedIn); ?>
            <?php echo navLink('admin.php', 'Admin', $loggedIn); ?>
        </nav>
        <nav class="headerNav">
            <div class="coins">
                <a class="bronze">1000</a>
                <a class="silver">1000</a>
                <a class="gold">1000</a>
            </div>
            <?php if (in_array($currentPage, ['index.php', 'magasin.php'])): ?>
                <a class="headerBtn" href="#">Connexion / Inscription</a>
            <?php endif; ?>
            <div class="user-profile">
                <span class="user-name"><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span>
                <img class="user-avatar" src="img/placeholder.webp" alt="Avatar" />
            </div>
        </nav>
    </div>
</header>