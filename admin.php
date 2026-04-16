<?php
require_once 'include/session.php';
require_once 'BD/bd.php';

if (empty($_SESSION['is_admin'])) {
    header('Location: index.php');
    exit;
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

            <form class="admin-form" action="#" method="POST">
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
                            <option value="G">G — Gargantuesque</option>
                        </select>
                    </div>

                    <div class="admin-form-field">
                        <label for="idCategorie">Catégorie</label>
                        <input type="text" id="idCategorie" name="idCategorie" maxlength="1" placeholder="ex: M" required>
                    </div>

                    <div class="admin-form-field admin-form-field--checkbox">
                        <label>
                            <input type="checkbox" name="estPigee" value="1">
                            Énigme piégée
                        </label>
                    </div>
                </div>

                <!-- Réponses -->
                <fieldset class="admin-responses">
                    <legend>Réponses <span class="admin-responses-hint">(cochez la bonne réponse)</span></legend>

                    <?php for ($i = 1; $i <= 4; $i++): ?>
                    <div class="admin-response-row">
                        <input type="radio" name="bonneReponse" value="<?= $i ?>" id="correct_<?= $i ?>" <?= $i === 1 ? 'required' : '' ?>>
                        <label for="correct_<?= $i ?>" class="admin-response-radio-label"></label>
                        <input
                            type="text"
                            name="reponse[<?= $i ?>]"
                            maxlength="45"
                            placeholder="Réponse <?= $i ?>"
                            required
                        >
                    </div>
                    <?php endfor; ?>
                </fieldset>

                <div class="admin-form-actions">
                    <button type="submit" class="btn-primary">Ajouter l'énigme</button>
                    <button type="reset" class="btn-reset">Réinitialiser</button>
                </div>
            </form>
        </div>
    </main>
    <?php require 'include/footer.php'; ?>
</body>

</html>
