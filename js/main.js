// DarQuest — Modal open/close + sign-in/sign-up toggle

(function () {
    const overlay      = document.getElementById('authModalOverlay');
    const openBtn      = document.getElementById('openAuthModalBtn');
    const closeBtn     = document.getElementById('closeAuthModalBtn');
    const formSignin   = document.getElementById('formSignin');
    const formSignup   = document.getElementById('formSignup');
    const switchBtn    = document.getElementById('switchFormBtn');
    const modalTitle   = document.getElementById('authModalTitle');
    const toggleText   = document.getElementById('toggleText');
    const forgotBtn    = document.getElementById('forgotPasswordBtn');

    if (!overlay) return;

    // ── Open / Close ──────────────────────────────────────────────────────────
    function openModal() {
        overlay.classList.add('visible');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('blurred');
    }

    function closeModal() {
        overlay.classList.remove('visible');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('blurred');
    }

    if (openBtn)  openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    // Click outside modal box closes it
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeModal();
    });

    // Escape key closes it
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('visible')) closeModal();
    });

    // ── Switch between sign-in and sign-up ────────────────────────────────────
    function showSignin() {
        formSignin.style.display = '';
        formSignup.style.display = 'none';
        modalTitle.textContent   = 'Connexion';
        toggleText.innerHTML     = 'Pas encore de compte ? <button type="button" class="link-button" id="switchFormBtn">Inscription</button>';
        bindSwitch();
    }

    function showSignup() {
        formSignin.style.display = 'none';
        formSignup.style.display = '';
        modalTitle.textContent   = 'Inscription';
        toggleText.innerHTML     = 'Deja un compte ? <button type="button" class="link-button" id="switchFormBtn">Connexion</button>';
        bindSwitch();
    }

    function bindSwitch() {
        const btn = document.getElementById('switchFormBtn');
        if (!btn) return;
        btn.addEventListener('click', function () {
            if (formSignup.style.display === 'none') {
                showSignup();
            } else {
                showSignin();
            }
        });
    }

    if (switchBtn) switchBtn.addEventListener('click', function () {
        if (formSignup.style.display === 'none') {
            showSignup();
        } else {
            showSignin();
        }
    });

    if (forgotBtn) forgotBtn.addEventListener('click', function () {
        alert('Recuperation de mot de passe non implementee.');
    });

    // ── Auto-open modal if PHP sent back an error ─────────────────────────────
    if (document.querySelector('.auth-error-banner')) {
        openModal();
    }

})();