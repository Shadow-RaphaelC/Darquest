// fichier JS initialisation
// Gestion du modal Connexion / Inscription

(function () {
    const openBtn = document.getElementById('openAuthModalBtn');
    const closeBtn = document.getElementById('closeAuthModalBtn');
    const overlay = document.getElementById('authModalOverlay');
    const authTitle = document.getElementById('authModalTitle');
    const authForm = document.getElementById('authForm');
    const switchToSignUp = document.getElementById('switchToSignUp');
    const forgotPasswordBtn = document.getElementById('forgotPasswordBtn');

    if (!overlay || !authForm || !authTitle || !switchToSignUp || !forgotPasswordBtn) {
        return;
    }

    function openModal() {
        overlay.classList.add('visible');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('blurred');
        setSignIn();
    }

    function closeModal() {
        overlay.classList.remove('visible');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('blurred');
    }

    function setSignIn() {
        authTitle.textContent = 'Connexion';
        authForm.innerHTML = `
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
        `;
        authForm.dataset.mode = 'signin';
        switchToSignUp.textContent = 'Inscription';
        document.querySelector('.toggle-text').innerHTML = 'Pas encore de compte ? <button type="button" class="link-button" id="switchToSignUp">Inscription</button>';
        bindSwitch();
        bindForgot();
    }

    function setSignUp() {
        authTitle.textContent = 'Inscription';
        authForm.innerHTML = `
            <div class="form-field">
                <label for="signupAlias">Alias</label>
                <input type="text" id="signupAlias" name="signupAlias" required>
            </div>
            <div class="form-field">
                <label for="signupPrenom">Prénom</label>
                <input type="text" id="signupPrenom" name="signupPrenom" required>
            </div>
            <div class="form-field">
                <label for="signupNom">Nom</label>
                <input type="text" id="signupNom" name="signupNom" required>
            </div>
            <div class="form-field">
                <label for="signupEmail">Courriel</label>
                <input type="email" id="signupEmail" name="signupEmail" required>
            </div>
            <div class="form-field">
                <label for="signupPassword">Mot de passe</label>
                <input type="password" id="signupPassword" name="signupPassword" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">S'inscrire</button>
            </div>
        `;
        authForm.dataset.mode = 'signup';
        switchToSignUp.textContent = 'Connexion';
        document.querySelector('.toggle-text').innerHTML = 'Déjà un compte ? <button type="button" class="link-button" id="switchToSignUp">Connexion</button>';
        bindSwitch();
    }

    function bindSwitch() {
        const btn = document.getElementById('switchToSignUp');
        if (!btn) return;
        btn.addEventListener('click', function () {
            if (authForm.dataset.mode === 'signup') {
                setSignIn();
            } else {
                setSignUp();
            }
        });
    }

    function bindForgot() {
        const btn = document.getElementById('forgotPasswordBtn');
        if (!btn) return;
        btn.addEventListener('click', function () {
            alert('Fonction de récupération de mot de passe non implémentée actuellement.');
        });
    }

    if (openBtn) {
        openBtn.addEventListener('click', openModal);
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && overlay.classList.contains('visible')) {
            closeModal();
        }
    });

    authForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const mode = authForm.dataset.mode || 'signin';
        alert(mode === 'signup' ? 'Inscription temporairement désactivée.' : 'Connexion temporairement désactivée.');

        // A remplacer par une requête AJAX / envoi réel.
    });

    setSignIn();
    bindSwitch();
    bindForgot();
})();
