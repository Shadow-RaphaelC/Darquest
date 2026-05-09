<?php
checkPotionRestock();
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$loggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $loggedIn ? ($_SESSION['username'] ?? 'Joueur') : 'Invité';
$isAdmin = $loggedIn && !empty($_SESSION['is_admin']);

$authError = $_SESSION['auth_error'] ?? '';
$authMode = $_SESSION['auth_mode'] ?? 'signin';
unset($_SESSION['auth_error'], $_SESSION['auth_mode']);

// Define nav buttons per page
$navLinks = [];
if ($currentPage === 'panier.php') {
    $navLinks = [
        'magasin.php' => 'Magasin',
        'inventaire.php' => 'Inventaire',
        'enigma.php' => 'Enigma',
    ];
} elseif ($currentPage === 'magasin.php') {
    $navLinks = [
        'inventaire.php' => 'Inventaire',
        'enigma.php' => 'Enigma',
    ];
} elseif ($currentPage === 'inventaire.php') {
    $navLinks = [
        'magasin.php' => 'Magasin',
    ];
} elseif ($currentPage === 'enigma.php') {
    $navLinks = [
        'magasin.php' => 'Magasin',
    ];
} elseif ($currentPage === 'profil.php') {
    $navLinks = [
        'magasin.php' => 'Magasin',
        'inventaire.php' => 'Inventaire',
    ];
} elseif ($currentPage === 'admin.php') {
    $navLinks = [
        'index.php' => 'Accueil',
    ];
}
?>
<header>
    <div class="headerLogo">
        <a href="index.php">
            <img style="height: 220px;" src="img/DarQuestTitle_WHITE.png" alt="DarQuest Logo" class="logo">
        </a>
    </div>
    <div class="separatorBox">
        <img src="img/sep/qq_01_01.png" alt="" class="sep">
        <?php for ($i = 0; $i < 6; $i++): ?>
            <img src="img/sep/qq_01_02.png" alt="" class="sep">
        <?php endfor; ?>
        <img src="img/sep/qq_01_03.png" alt="" class="sep">
    </div>

    <div class="headerBox">

        <!-- Left: page nav buttons -->
        <nav class="headerNavLeft">
            <?php if ($loggedIn && !empty($navLinks)): ?>
                <div>
                    <?php foreach ($navLinks as $href => $label): ?>
                        <a class="headerBtn" href="<?= $href ?>">
                            <?= $label ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </nav>

        <!-- Right: coins, auth, profile -->
        <nav class="headerNav">
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

                <a class="user-profile" href="profil.php">

                    <nav class="headerNavMiddle">
                        <?php
                        $hp    = GetJoueurHP((int)$_SESSION['user_id']);
                        $_SESSION['pointDeVie'] = $hp['pointDeVie'];
                        $_SESSION['maxHP']      = $hp['maxHP'];
                        $pv    = $hp['pointDeVie'];
                        $maxHP = $hp['maxHP'];
                        $pvPct = $maxHP > 0 ? (int)round($pv / $maxHP * 100) : 0;
                        $pvPct = min(100, max(0, $pvPct));
                        ?>
                        <div class="hpBarBox">
                            <div class="hpBar" style="width: <?= $pvPct ?>%;">
                                <div class="hpText">PV: <?= $pv ?>/<?= $maxHP ?></div>
                            </div>
                        </div>
                    </nav>
                        <span class="user-profile-divider"></span>
                    <div class="coins">
                        <?php
                        $gold = (int) ($_SESSION['gold'] ?? 0);
                        $argent = (int) ($_SESSION['argent'] ?? 0);
                        $bronze = (int) ($_SESSION['bronze'] ?? 0);
                        ?>
                        <span class="gold"><?= $gold ?></span>
                        <span class="silver"><?= $argent ?></span>
                        <span class="bronze"><?= $bronze ?></span>
                    </div>
                    <span class="user-profile-divider"></span>
                    <span class="user-name"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
                    <img class="user-avatar" src="img/placeholder.webp" alt="Avatar">
                </a>
                <?php if ($loggedIn): ?>
                    <?php $cartCount = GetCartCount((int)$_SESSION['user_id']); ?>
                    <a class="cart-btn" href="panier.php">
                        🛒
                        <?php if ($cartCount > 0): ?>
                            <span class="user-profile-divider"></span>
                            <span class="cart-count"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php if (!$loggedIn): ?>
    <div id="authModalOverlay" class="modal-overlay" aria-hidden="true">
        <div class="modal">
            <button id="closeAuthModalBtn" class="modal-close" type="button">&times;</button>
            <h2 id="authModalTitle">Connexion</h2>

            <?php if ($authError !== ''): ?>
                <div class="auth-error-banner"><?= htmlspecialchars($authError, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <!-- Sign-in form -->
            <form id="formSignin" class="auth-form" action="auth.php" method="POST"
                style="<?= $authMode === 'signup' ? 'display:none;' : '' ?>">
                <input type="hidden" name="mode" value="signin">
                <div class="form-field">
                    <label>Alias ou courriel</label>
                    <input type="text" name="authUser" required>
                </div>
                <div class="form-field">
                    <label>Mot de passe</label>
                    <input type="password" name="authPassword" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Se connecter</button>
                    <button type="button" id="forgotPasswordBtn" class="link-button">Mot de passe oublié ?</button>
                </div>
            </form>

            <!-- Sign-up form -->
            <form id="formSignup" class="auth-form" action="#" method="POST"
                style="<?= $authMode === 'signup' ? '' : 'display:none;' ?>">
                <input type="hidden" name="mode" value="signup">
                <div class="form-field">
                    <label>Alias</label>
                    <input type="text" name="signupAlias" required>
                </div>
                <div class="form-field">
                    <label>Prénom</label>
                    <input type="text" name="signupPrenom" required>
                </div>
                <div class="form-field">
                    <label>Nom</label>
                    <input type="text" name="signupNom" required>
                </div>
                <div class="form-field">
                    <label>Courriel</label>
                    <input type="email" name="signupEmail" required>
                </div>
                <div class="form-field">
                    <label>Mot de passe</label>
                    <input type="password" name="signupPassword" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Créer un compte</button>
                </div>
            </form>

            <p id="toggleText" class="toggle-text">
                <?php if ($authMode === 'signup'): ?>
                    Déjà un compte ? <button type="button" class="link-button" id="switchFormBtn">Connexion</button>
                <?php else: ?>
                    Pas encore de compte ? <button type="button" class="link-button" id="switchFormBtn">Inscription</button>
                <?php endif; ?>
            </p>

            <!-- Inscription – vérification du courriel -->
            <div id="signupVerifyPanel" class="auth-form" style="display:none;">
                <p style="font-size:0.9rem; color:#ccc; margin:0 0 14px;">
                    Un code à 6 caractères a été envoyé à votre adresse courriel. Vérifiez aussi vos spams.
                </p>
                <div class="form-field">
                    <label>Code de vérification</label>
                    <input type="text" id="signupVerifyCode" maxlength="6" autocomplete="off"
                           placeholder="XXXXXX" style="letter-spacing:4px; text-transform:uppercase; font-family:monospace;">
                </div>
                <div id="signupVerifyMsg" class="auth-error-banner" style="display:none;"></div>
                <div class="form-actions">
                    <button type="button" class="btn-primary" id="signupVerifyBtn">Créer mon compte</button>
                    <button type="button" class="link-button" id="signupVerifyBackBtn">Annuler</button>
                </div>
            </div>

            <!-- Mot de passe oublié – étape 1 : saisie du courriel -->
            <div id="forgotStep1Panel" class="auth-form" style="display:none;">
                <div class="form-field">
                    <label>Courriel associé au compte</label>
                    <input type="email" id="forgotEmail" autocomplete="email">
                </div>
                <div id="forgotStep1Msg" class="auth-error-banner" style="display:none;"></div>
                <div class="form-actions">
                    <button type="button" class="btn-primary" id="forgotSubmitBtn">Envoyer le code</button>
                    <button type="button" class="link-button" id="backToSigninBtn">Retour</button>
                </div>
            </div>

            <!-- Mot de passe oublié – étape 2 : saisie du code et nouveau mot de passe -->
            <div id="forgotStep2Panel" class="auth-form" style="display:none;">
                <p style="font-size:0.9rem; color:#ccc; margin:0 0 14px;">
                    Un code à 6 caractères a été envoyé à votre adresse courriel. Vérifiez aussi vos spams.
                </p>
                <div class="form-field">
                    <label>Entrez le code</label>
                    <input type="text" id="forgotCodeInput" maxlength="6" autocomplete="off"
                           placeholder="XXXXXX" style="letter-spacing:4px; text-transform:uppercase; font-family:monospace;">
                </div>
                <div class="form-field">
                    <label>Nouveau mot de passe</label>
                    <input type="password" id="forgotNewPw" autocomplete="new-password">
                </div>
                <div class="form-field">
                    <label>Confirmer le mot de passe</label>
                    <input type="password" id="forgotConfirmPw" autocomplete="new-password">
                </div>
                <div id="forgotStep2Msg" class="auth-error-banner" style="display:none;"></div>
                <div class="form-actions">
                    <button type="button" class="btn-primary" id="forgotValidateBtn">Valider</button>
                </div>
            </div>

            <!-- Mot de passe oublié – succès -->
            <div id="forgotSuccessPanel" style="display:none; text-align:center;">
                <div class="auth-error-banner"
                     style="background:rgba(50,150,50,0.25); border-color:rgba(80,200,80,0.5); color:#adf3ad;">
                    Mot de passe réinitialisé avec succès !
                </div>
                <div class="form-actions" style="margin-top:16px; justify-content:center;">
                    <button type="button" class="btn-primary" id="backToSigninAfterResetBtn">Se connecter</button>
                </div>
            </div>

        </div>
    </div>
<?php endif; ?>