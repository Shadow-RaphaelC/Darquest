<?php
require_once 'include/session.php';
require_once 'BD/bd.php';

if (empty($_SESSION['is_admin'])) {
    header('Location: index.php');
    exit;
}

$adminFeedback = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_enigma') {
    $enigme      = trim($_POST['enigme']      ?? '');
    $difficulte  = trim($_POST['difficulte']  ?? '');
    $idCategorie = trim($_POST['idCategorie'] ?? '');
    $reponses    = $_POST['reponse']          ?? [];
    $bonneRep    = (int) ($_POST['bonneReponse'] ?? 0);

    $typeEnigme = ($_POST['type_enigme'] ?? '') === 'vrai_faux' ? 'vrai_faux' : 'choix_multiple';
    $expectedCount = $typeEnigme === 'vrai_faux' ? 2 : 4;

    $validDiff  = in_array($difficulte,  ['F', 'M', 'D'], true);
    $validCat   = in_array($idCategorie, ['F', 'M', 'D', 'G'], true);
    $validRep   = count($reponses) === $expectedCount && !in_array('', array_map('trim', $reponses), true);
    $validBonne = $bonneRep >= 1 && $bonneRep <= $expectedCount;

    if (!$enigme || !$validDiff || !$validCat || !$validRep || !$validBonne) {
        $_SESSION['admin_feedback'] = ['type' => 'error', 'message' => 'Veuillez remplir tous les champs correctement.'];
    } else {
        $result = AjouterEnigme($enigme, $difficulte, $idCategorie, $reponses, $bonneRep);
        $_SESSION['admin_feedback'] = $result['success']
            ? ['type' => 'success', 'message' => 'Énigme #' . $result['idEnigma'] . ' ajoutée avec succès.']
            : ['type' => 'error',   'message' => $result['message']];
    }

    header('Location: admin.php');
    exit;
}

if (!empty($_SESSION['admin_feedback'])) {
    $adminFeedback = $_SESSION['admin_feedback'];
    unset($_SESSION['admin_feedback']);
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles_dark.css">
    <title>DarQuest Admin</title>
</head>

<body>
    <?php require 'include/header.php'; ?>
    <main>
        <h1>Panneau d'administration</h1>
        <div class="admin-section">
            <h2>Ajouter une Énigme</h2>

            <?php if ($adminFeedback): ?>
                <p class="auth-error-banner" <?= $adminFeedback['type'] === 'success' ? 'style="background:rgba(43,143,43,0.25);border-color:rgba(100,220,100,0.5);color:#adfaad;"' : '' ?>>
                    <?= htmlspecialchars($adminFeedback['message']) ?>
                </p>
            <?php endif; ?>

            <form class="admin-form" action="admin.php" method="POST">
                <input type="hidden" name="action" value="add_enigma">

                <!-- Énigme -->
                <div class="admin-form-field">
                    <label for="enigme">Texte de l'énigme</label>
                    <textarea id="enigme" name="enigme" maxlength="300" rows="4" placeholder="Entrez l'énigme..." required></textarea>
                </div>

                <!-- Difficulté + Catégorie -->
                <div class="admin-form-row">
                    <div class="admin-form-field">
                        <label for="difficulte">Difficulté</label>
                        <select id="difficulte" name="difficulte" required>
                            <option value="" disabled selected>-- Choisir --</option>
                            <option value="F">F — Facile</option>
                            <option value="M">M — Moyen</option>
                            <option value="D">D — Difficile</option>
                        </select>
                    </div>

                    <div class="admin-form-field">
                        <label for="idCategorie">Catégorie</label>
                        <select id="idCategorie" name="idCategorie" required>
                            <option value="" disabled selected>-- Choisir --</option>
                            <option value="F">F — Facile</option>
                            <option value="M">M — Moyen</option>
                            <option value="D">D — Difficile</option>
                            <option value="G">G — Mage</option>
                        </select>
                    </div>
                </div>

                <!-- Réponses -->
                <input type="hidden" name="type_enigme" id="type_enigme" value="choix_multiple">

                <fieldset class="admin-responses">
                    <legend>Réponses <span class="admin-responses-hint">(cochez la bonne réponse)</span></legend>

                    <div class="admin-type-toggle">
                        <button type="button" class="admin-toggle-btn active" id="btnChoix">Choix multiples</button>
                        <button type="button" class="admin-toggle-btn" id="btnVraiFaux">Vrai / Faux</button>
                    </div>

                    <div class="admin-response-row" id="response-row-1">
                        <input type="radio" name="bonneReponse" value="1" id="correct_1" required>
                        <label for="correct_1" class="admin-response-radio-label"></label>
                        <input type="text" name="reponse[1]" id="reponse_1" maxlength="45" placeholder="Réponse 1" required>
                    </div>
                    <div class="admin-response-row" id="response-row-2">
                        <input type="radio" name="bonneReponse" value="2" id="correct_2">
                        <label for="correct_2" class="admin-response-radio-label"></label>
                        <input type="text" name="reponse[2]" id="reponse_2" maxlength="45" placeholder="Réponse 2" required>
                    </div>
                    <div class="admin-response-row" id="response-row-3">
                        <input type="radio" name="bonneReponse" value="3" id="correct_3">
                        <label for="correct_3" class="admin-response-radio-label"></label>
                        <input type="text" name="reponse[3]" id="reponse_3" maxlength="45" placeholder="Réponse 3" required>
                    </div>
                    <div class="admin-response-row" id="response-row-4">
                        <input type="radio" name="bonneReponse" value="4" id="correct_4">
                        <label for="correct_4" class="admin-response-radio-label"></label>
                        <input type="text" name="reponse[4]" id="reponse_4" maxlength="45" placeholder="Réponse 4" required>
                    </div>
                </fieldset>

                <div class="admin-form-actions">
                    <button type="submit" class="btn-primary">Ajouter l'énigme</button>
                    <button type="reset" class="btn-reset">Réinitialiser</button>
                </div>

                <p id="adminFormError" class="auth-error-banner" style="display:none;margin-top:12px;"></p>
            </form>
        </div>
    </main>
    <?php require 'include/footer.php'; ?>

    <script>
    (function () {
        const btnChoix    = document.getElementById('btnChoix');
        const btnVraiFaux = document.getElementById('btnVraiFaux');
        const typeInput   = document.getElementById('type_enigme');
        const row3        = document.getElementById('response-row-3');
        const row4        = document.getElementById('response-row-4');
        const rep1        = document.getElementById('reponse_1');
        const rep2        = document.getElementById('reponse_2');
        const rep3        = document.getElementById('reponse_3');
        const rep4        = document.getElementById('reponse_4');

        function setChoixMultiples() {
            typeInput.value = 'choix_multiple';
            btnChoix.classList.add('active');
            btnVraiFaux.classList.remove('active');

            row3.style.display = '';
            row4.style.display = '';

            [rep1, rep2, rep3, rep4].forEach(function (r) {
                r.readOnly = false;
                r.required = true;
                r.placeholder = 'Réponse ' + r.name.match(/\[(\d)\]/)[1];
                r.value = '';
            });
        }

        function setVraiFaux() {
            typeInput.value = 'vrai_faux';
            btnVraiFaux.classList.add('active');
            btnChoix.classList.remove('active');

            row3.style.display = 'none';
            row4.style.display = 'none';

            // Lock rows 3 & 4 out of submission
            rep3.required = false;
            rep3.removeAttribute('name');
            rep4.required = false;
            rep4.removeAttribute('name');

            rep1.value    = 'Vrai';
            rep1.readOnly = true;
            rep1.required = true;
            rep2.value    = 'Faux';
            rep2.readOnly = true;
            rep2.required = true;

            // Reset correct-answer radio to row 1 if rows 3/4 were checked
            const checked = document.querySelector('input[name="bonneReponse"]:checked');
            if (checked && (checked.value === '3' || checked.value === '4')) {
                document.getElementById('correct_1').checked = true;
            }
        }

        btnChoix.addEventListener('click', setChoixMultiples);
        btnVraiFaux.addEventListener('click', setVraiFaux);

        // Restore names if switching back to choix multiples
        btnChoix.addEventListener('click', function () {
            rep3.name = 'reponse[3]';
            rep4.name = 'reponse[4]';
        });

        // ── Validation on submit ──────────────────────────────────────────
        document.querySelector('.admin-form').addEventListener('submit', function (e) {
            const errorBox = document.getElementById('adminFormError');
            const errors   = [];
            const isVraiFaux = typeInput.value === 'vrai_faux';

            const enigme = document.getElementById('enigme').value;
            if (enigme.length > 300) {
                errors.push('Le texte de l\'énigme dépasse 300 caractères (' + enigme.length + '/300).');
            }

            const activeReps = isVraiFaux ? [rep1, rep2] : [rep1, rep2, rep3, rep4];
            activeReps.forEach(function (input, idx) {
                if (input.value.trim() === '') {
                    errors.push('La réponse ' + (idx + 1) + ' est vide.');
                } else if (input.value.length > 45) {
                    errors.push('La réponse ' + (idx + 1) + ' dépasse 45 caractères (' + input.value.length + '/45).');
                }
            });

            const bonneReponse = document.querySelector('input[name="bonneReponse"]:checked');
            if (!bonneReponse) {
                errors.push('Veuillez cocher la bonne réponse.');
            }

            if (errors.length > 0) {
                e.preventDefault();
                errorBox.innerHTML = errors.join('<br>');
                errorBox.style.display = '';
                errorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                errorBox.style.display = 'none';
            }
        });
    })();
    </script>
</body>

</html>
