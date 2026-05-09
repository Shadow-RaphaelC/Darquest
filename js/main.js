// DarQuest — Scroll to top button
(function () {
    const btn = document.getElementById('scrollTopBtn');
    if (!btn) return;

    window.addEventListener('scroll', function () {
        if (window.scrollY > 300) {
            btn.classList.add('visible');
        } else {
            btn.classList.remove('visible');
        }
    }, { passive: true });

    btn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
})();

// DarQuest — Auth modal, inscription, mot de passe oublié
(function () {
    const overlay            = document.getElementById('authModalOverlay');
    const openBtn            = document.getElementById('openAuthModalBtn');
    const closeBtn           = document.getElementById('closeAuthModalBtn');
    const formSignin         = document.getElementById('formSignin');
    const formSignup         = document.getElementById('formSignup');
    const switchBtn          = document.getElementById('switchFormBtn');
    const modalTitle         = document.getElementById('authModalTitle');
    const toggleText         = document.getElementById('toggleText');
    const forgotBtn          = document.getElementById('forgotPasswordBtn');
    const forgotStep1Panel   = document.getElementById('forgotStep1Panel');
    const forgotStep2Panel   = document.getElementById('forgotStep2Panel');
    const forgotSuccessPanel = document.getElementById('forgotSuccessPanel');
    const signupVerifyPanel  = document.getElementById('signupVerifyPanel');

    if (!overlay) return;

    // ── Ouvrir / Fermer ────────────────────────────────────────────────────────
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

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('visible')) closeModal();
    });

    // ── Gestion des panneaux ───────────────────────────────────────────────────
    function hideAll() {
        if (formSignin)         formSignin.style.display         = 'none';
        if (formSignup)         formSignup.style.display         = 'none';
        if (signupVerifyPanel)  signupVerifyPanel.style.display  = 'none';
        if (forgotStep1Panel)   forgotStep1Panel.style.display   = 'none';
        if (forgotStep2Panel)   forgotStep2Panel.style.display   = 'none';
        if (forgotSuccessPanel) forgotSuccessPanel.style.display = 'none';
        if (toggleText)         toggleText.innerHTML             = '';
    }

    function showSignin() {
        hideAll();
        if (formSignin) formSignin.style.display = '';
        if (modalTitle) modalTitle.textContent   = 'Connexion';
        if (toggleText) toggleText.innerHTML     =
            'Pas encore de compte ? <button type="button" class="link-button" id="switchFormBtn">Inscription</button>';
        bindSwitch();
    }

    function showSignup() {
        hideAll();
        if (formSignup) formSignup.style.display = '';
        if (modalTitle) modalTitle.textContent   = 'Inscription';
        if (toggleText) toggleText.innerHTML     =
            'Déjà un compte ? <button type="button" class="link-button" id="switchFormBtn">Connexion</button>';
        bindSwitch();
    }

    function showSignupVerify() {
        hideAll();
        if (signupVerifyPanel) signupVerifyPanel.style.display = '';
        if (modalTitle) modalTitle.textContent = 'Vérification du courriel';
        const codeEl = document.getElementById('signupVerifyCode');
        if (codeEl) codeEl.value = '';
        const msgEl = document.getElementById('signupVerifyMsg');
        if (msgEl) { msgEl.textContent = ''; msgEl.style.display = 'none'; }
    }

    function showForgotStep1() {
        hideAll();
        if (forgotStep1Panel) forgotStep1Panel.style.display = '';
        if (modalTitle) modalTitle.textContent = 'Mot de passe oublié';
        const emailInput = document.getElementById('forgotEmail');
        if (emailInput) emailInput.value = '';
        const msgEl = document.getElementById('forgotStep1Msg');
        if (msgEl) { msgEl.textContent = ''; msgEl.style.display = 'none'; }
    }

    function showForgotStep2() {
        hideAll();
        if (forgotStep2Panel) forgotStep2Panel.style.display = '';
        if (modalTitle) modalTitle.textContent = 'Vérification du code';
        ['forgotCodeInput', 'forgotNewPw', 'forgotConfirmPw'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        const msgEl = document.getElementById('forgotStep2Msg');
        if (msgEl) { msgEl.textContent = ''; msgEl.style.display = 'none'; }
    }

    function showForgotSuccess() {
        hideAll();
        if (forgotSuccessPanel) forgotSuccessPanel.style.display = '';
        if (modalTitle) modalTitle.textContent = 'Succès';
    }

    function bindSwitch() {
        const btn = document.getElementById('switchFormBtn');
        if (!btn) return;
        btn.addEventListener('click', function () {
            if (formSignup && formSignup.style.display === 'none') {
                showSignup();
            } else {
                showSignin();
            }
        });
    }

    if (switchBtn) switchBtn.addEventListener('click', function () {
        if (formSignup && formSignup.style.display === 'none') {
            showSignup();
        } else {
            showSignin();
        }
    });

    // ── Inscription : intercepter le formulaire et envoyer le code ────────────
    if (formSignup) {
        formSignup.addEventListener('submit', function (e) {
            e.preventDefault();

            const fd = new FormData(formSignup);
            fd.append('action', 'request');

            const submitBtn = formSignup.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            // Show any existing error inside the signup form
            let msgEl = document.getElementById('signupFormMsg');
            if (!msgEl) {
                msgEl = document.createElement('div');
                msgEl.id = 'signupFormMsg';
                msgEl.className = 'auth-error-banner';
                msgEl.style.display = 'none';
                formSignup.insertBefore(msgEl, formSignup.querySelector('.form-actions'));
            }

            fetch('signup_verify.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (submitBtn) submitBtn.disabled = false;
                    if (data.success) {
                        showSignupVerify();
                    } else {
                        msgEl.textContent   = data.message || 'Erreur inconnue.';
                        msgEl.style.display = '';
                    }
                })
                .catch(function () {
                    if (submitBtn) submitBtn.disabled = false;
                    msgEl.textContent   = 'Erreur réseau.';
                    msgEl.style.display = '';
                });
        });
    }

    // ── Inscription : valider le code ─────────────────────────────────────────
    const signupVerifyBtn = document.getElementById('signupVerifyBtn');
    if (signupVerifyBtn) {
        signupVerifyBtn.addEventListener('click', function () {
            const codeEl = document.getElementById('signupVerifyCode');
            const code   = codeEl ? codeEl.value.trim().toUpperCase() : '';
            const msgEl  = document.getElementById('signupVerifyMsg');

            if (!code) {
                msgEl.textContent   = 'Veuillez entrer le code reçu par courriel.';
                msgEl.style.display = '';
                return;
            }

            signupVerifyBtn.disabled = true;
            const fd = new FormData();
            fd.append('action', 'confirm');
            fd.append('code',   code);

            fetch('signup_verify.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    signupVerifyBtn.disabled = false;
                    if (data.success) {
                        // Account created and logged in — reload the page
                        window.location.reload();
                    } else {
                        msgEl.textContent   = data.message || 'Erreur inconnue.';
                        msgEl.style.display = '';
                    }
                })
                .catch(function () {
                    signupVerifyBtn.disabled = false;
                    msgEl.textContent   = 'Erreur réseau.';
                    msgEl.style.display = '';
                });
        });
    }

    // ── Inscription : annuler la vérification ─────────────────────────────────
    const signupVerifyBackBtn = document.getElementById('signupVerifyBackBtn');
    if (signupVerifyBackBtn) signupVerifyBackBtn.addEventListener('click', showSignup);

    // ── Mot de passe oublié – bouton d'entrée ─────────────────────────────────
    if (forgotBtn) forgotBtn.addEventListener('click', showForgotStep1);

    // ── Mot de passe oublié – étape 1 : envoi du courriel ────────────────────
    const forgotSubmitBtn = document.getElementById('forgotSubmitBtn');
    if (forgotSubmitBtn) {
        forgotSubmitBtn.addEventListener('click', function () {
            const emailEl = document.getElementById('forgotEmail');
            const msgEl   = document.getElementById('forgotStep1Msg');
            const email   = emailEl ? emailEl.value.trim() : '';

            if (!email) {
                msgEl.textContent   = 'Veuillez entrer votre courriel.';
                msgEl.style.display = '';
                return;
            }

            forgotSubmitBtn.disabled = true;
            const fd = new FormData();
            fd.append('action', 'request_reset');
            fd.append('email',  email);

            fetch('reset_password.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    forgotSubmitBtn.disabled = false;
                    if (data.success) {
                        showForgotStep2();
                    } else {
                        msgEl.textContent   = data.message || 'Erreur inconnue.';
                        msgEl.style.display = '';
                    }
                })
                .catch(function () {
                    forgotSubmitBtn.disabled = false;
                    msgEl.textContent   = 'Erreur réseau.';
                    msgEl.style.display = '';
                });
        });
    }

    // ── Mot de passe oublié – retour depuis étape 1 ───────────────────────────
    const backToSigninBtn = document.getElementById('backToSigninBtn');
    if (backToSigninBtn) backToSigninBtn.addEventListener('click', showSignin);

    // ── Mot de passe oublié – étape 2 : validation du code ───────────────────
    const forgotValidateBtn = document.getElementById('forgotValidateBtn');
    if (forgotValidateBtn) {
        forgotValidateBtn.addEventListener('click', function () {
            const codeInput = document.getElementById('forgotCodeInput');
            const code      = codeInput ? codeInput.value.trim().toUpperCase() : '';
            const newPw     = (document.getElementById('forgotNewPw')     || {}).value || '';
            const confirm   = (document.getElementById('forgotConfirmPw') || {}).value || '';
            const msgEl     = document.getElementById('forgotStep2Msg');

            if (!code || !newPw || !confirm) {
                msgEl.textContent   = 'Veuillez remplir tous les champs.';
                msgEl.style.display = '';
                return;
            }

            forgotValidateBtn.disabled = true;
            const fd = new FormData();
            fd.append('action',   'validate_reset');
            fd.append('code',     code);
            fd.append('password', newPw);
            fd.append('confirm',  confirm);

            fetch('reset_password.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    forgotValidateBtn.disabled = false;
                    if (data.success) {
                        showForgotSuccess();
                    } else {
                        msgEl.textContent   = data.message || 'Erreur inconnue.';
                        msgEl.style.display = '';
                    }
                })
                .catch(function () {
                    forgotValidateBtn.disabled = false;
                    msgEl.textContent   = 'Erreur réseau.';
                    msgEl.style.display = '';
                });
        });
    }

    // ── Mot de passe oublié – retour après succès ─────────────────────────────
    const backAfterReset = document.getElementById('backToSigninAfterResetBtn');
    if (backAfterReset) backAfterReset.addEventListener('click', showSignin);

    // ── Ouvrir automatiquement si PHP a renvoyé une erreur ────────────────────
    if (document.querySelector('.auth-error-banner:not([style*="display:none"])')) {
        openModal();
    }
})();
