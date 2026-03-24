<?php
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        session_start();
    }
}

$currentPage = basename($_SERVER['SCRIPT_NAME']);
$loggedIn    = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName    = $loggedIn ? ($_SESSION['username'] ?? 'Joueur') : 'Invité';
$isAdmin     = $loggedIn && !empty($_SESSION['is_admin']);

// Pull error/mode from session (flash — shown once then cleared)
$authError = $_SESSION['auth_error'] ?? '';
$authMode  = $_SESSION['auth_mode']  ?? 'signin';
unset($_SESSION['auth_error'], $_SESSION['auth_mode']);
?>
<header>
    <div class="headerLogo">
        <a href="index.php">
            <img style="height: 220px;" src="img/DarQuestTitle_WHITE.png" alt="DarQuest Logo" class="logo">
        </a>
    </div>
    <div class="separatorBox">
        <img src="img/sep/qq_01_01.png" alt="" class="sep">
        <?php for ($i = 0; $i < 13; $i++): ?>
            <img src="img/sep/qq_01_02.png" alt="" class="sep">
        <?php endfor; ?>
        <img src="img/sep/qq_01_03.png" alt="" class="sep">
    </div>
    <div class="headerBox">
        <nav class="headerNav">
            <div class="coins">
                <span class="bronze">0</span>
                <span class="silver">0</span>
                <span class="gold">0</span>
            </div>

            <?php if (!$loggedIn): ?>
                <button class="headerBtn" id="openAuthModalBtn" type="button">
                    Connexion / Inscription
                </button>
            <?php else: ?>
                <form action="auth.php" method="POST" style="display:inline;">
                    <input type="hidden" name="mode" value="logout">
                    <button type="submit" class="headerBtn">Déconnexion</button>
                </form>
                <?php if ($isAdmin): ?>
                    <a class="headerBtn" href="admin.php">Admin</a>
                <?php endif; ?>
            <?php endif; ?>

            <a class="user-profile" href="<?php echo $loggedIn ? 'profil.php' : '#'; ?>">
                <span class="user-name"><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span>
                <img class="user-avatar" src="img/placeholder.webp" alt="Avatar">
            </a>
        </nav>
    </div>
</header>

<?php if (!$loggedIn): ?>
<div class="modal-overlay" id="authModalOverlay" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="authModalTitle">
        <button class="modal-close" id="closeAuthModalBtn" aria-label="Fermer">x</button>

        <h2 id="authModalTitle">
            <?php echo $authMode === 'signup' ? 'Inscription' : 'Connexion'; ?>
        </h2>

        <?php if ($authError !== ''): ?>
            <p class="auth-error-banner">
                <?php echo htmlspecialchars($authError, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <!-- Sign-in form -->
        <form id="formSignin" action="auth.php" method="POST" class="auth-form"
              style="<?php echo ($authMode === 'signup') ? 'display:none;' : ''; ?>">
            <input type="hidden" name="mode" value="signin">
            <div class="form-field">
                <label for="authUser">Email ou alias</label>
                <input type="text" id="authUser" name="authUser" required autocomplete="username">
            </div>
            <div class="form-field">
                <label for="authPassword">Mot de passe</label>
                <input type="password" id="authPassword" name="authPassword" required autocomplete="current-password">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">Se connecter</button>
                <button type="button" class="link-button" id="forgotPasswordBtn">Mot de passe oublie ?</button>
            </div>
        </form>

        <!-- Sign-up form -->
        <form id="formSignup" action="auth.php" method="POST" class="auth-form"
              style="<?php echo ($authMode !== 'signup') ? 'display:none;' : ''; ?>">
            <input type="hidden" name="mode" value="signup">
            <div class="form-field">
                <label for="signupAlias">Alias</label>
                <input type="text" id="signupAlias" name="signupAlias" required autocomplete="username">
            </div>
            <div class="form-field">
                <label for="signupPrenom">Prenom</label>
                <input type="text" id="signupPrenom" name="signupPrenom" required autocomplete="given-name">
            </div>
            <div class="form-field">
                <label for="signupNom">Nom</label>
                <input type="text" id="signupNom" name="signupNom" required autocomplete="family-name">
            </div>
            <div class="form-field">
                <label for="signupEmail">Courriel</label>
                <input type="email" id="signupEmail" name="signupEmail" required autocomplete="email">
            </div>
            <div class="form-field">
                <label for="signupPassword">Mot de passe (min. 6 caracteres)</label>
                <input type="password" id="signupPassword" name="signupPassword" required autocomplete="new-password">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">S'inscrire</button>
            </div>
        </form>

        <p class="toggle-text" id="toggleText">
            <?php if ($authMode === 'signup'): ?>
                Deja un compte ?
                <button type="button" class="link-button" id="switchFormBtn">Connexion</button>
            <?php else: ?>
                Pas encore de compte ?
                <button type="button" class="link-button" id="switchFormBtn">Inscription</button>
            <?php endif; ?>
        </p>
    </div>
</div>
<?php endif; ?>