<?php
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        session_start();
    }
}
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$loggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $loggedIn ? ($_SESSION['username'] ?? 'Joueur') : 'Invité';

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
        <nav class="headerNav">
            <div class="coins">
                <a class="bronze">1000</a>
                <a class="silver">1000</a>
                <a class="gold">1000</a>
            </div>
            <?php if (in_array($currentPage, ['index.php', 'magasin.php'])): ?>
                <button class="headerBtn" id="openAuthModalBtn" type="button">Connexion / Inscription</button>
            <?php endif; ?>
            <div class="user-profile">
                <span class="user-name"><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span>
                <img class="user-avatar" src="img/placeholder.webp" alt="Avatar" />
            </div>
        </nav>
    </div>
</header>
<div class="modal-overlay" id="authModalOverlay" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="authModalTitle">
        <button class="modal-close" id="closeAuthModalBtn" aria-label="Fermer la fenêtre">×</button>
        <h2 id="authModalTitle">Connexion</h2>

        <form id="authForm" class="auth-form" novalidate>
            <div class="form-field">
                <label for="authUser">Email ou alias</label>
                <input type="text" id="authUser" name="authUser" required>
            </div>
            <div class="form-field">
                <label for="authPassword">Mot de passe</label>
                <input type="password" id="authPassword" name="authPassword" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">Se connecter</button>
                <button type="button" class="link-button" id="forgotPasswordBtn">Mot de passe oublié ?</button>
            </div>
        </form>

        <p class="toggle-text">Pas encore de compte ? <button type="button" class="link-button" id="switchToSignUp">Inscription</button></p>
    </div>
</div>
