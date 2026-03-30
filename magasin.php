<?php
require_once 'include/session.php';
require_once 'BD/bd.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles_dark.css">
    <title>DarQuest Magasin</title>
</head>

<body>
    <?php require 'include/header.php'; ?>
    <main>
        <h1>Magasin</h1>
        <p>Bienvenue dans le magasin.</p>

        <div class="shop-filters">
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Rechercher un item" class="search-input">
                <button class="search-button" onclick="applyFilters()">Search</button>
            </div>
            <div class="checkboxes">
                <label class="filter-checkbox"><input type="checkbox" name="type" value="Arme"
                        onchange="applyFilters()"> Arme</label>
                <label class="filter-checkbox"><input type="checkbox" name="type" value="Armure"
                        onchange="applyFilters()"> Armure</label>
                <label class="filter-checkbox"><input type="checkbox" name="type" value="Potion"
                        onchange="applyFilters()"> Potions</label>
                <label class="filter-checkbox"><input type="checkbox" name="type" value="Sort"
                        onchange="applyFilters()"> Sorts</label>
            </div>
            <div class="radioButtons">
                <label class="filter-radio"><input type="radio" name="sort" value="price_asc" onchange="applyFilters()">
                    Prix croissant
                </label>
                <label class="filter-radio"><input type="radio" name="sort" value="price_desc"
                        onchange="applyFilters()"> Prix décroissant
                </label>
            </div>

        </div>

        <div class="item-grid-4" id="itemGrid" style="display:flex; flex-wrap:wrap; gap:16px; justify-content:space-around;">
            <?php
            $products = AfficherItems();
            if (!is_array($products))
                $products = [];

            foreach ($products as $p):
                $id = $p[0] ?? null;
                $nom = $p[1] ?? '';
                $quantity = (int) ($p[2] ?? 0);
                $typeItem = $p[3] ?? '';
                $price = (int) ($p[4] ?? 0);
                $image = $p[5] ?? '';
                $isDisponible = $p[6] ?? true;

                if (filter_var($isDisponible) === false)
                    continue;

                $typeCode = strtoupper(trim((string) $typeItem));
                if ($typeCode === 'A' || $typeCode === 'ARME')
                    $typeLabel = 'Arme';
                else if ($typeCode === 'R' || $typeCode === 'ARMURE')
                    $typeLabel = 'Armure';
                else if ($typeCode === 'P' || $typeCode === 'POTION')
                    $typeLabel = 'Potion';
                else if ($typeCode === 'S' || $typeCode === 'SORT')
                    $typeLabel = 'Sort';
                else if ($typeCode !== '')
                    $typeLabel = ucfirst(strtolower($typeCode));
                else
                    $typeLabel = 'Autre';
                ?>
                <div class="itemBox" data-type="<?= htmlspecialchars($typeLabel) ?>" data-price="<?= $price ?>"
                    data-name="<?= htmlspecialchars(strtolower($nom)) ?>">
                    <div class="item-img-wrapper" style="cursor:pointer;" onclick="openItemModal(
        '<?= htmlspecialchars($image, ENT_QUOTES) ?>',
        '<?= htmlspecialchars($nom, ENT_QUOTES) ?>',
        '<?= htmlspecialchars($typeLabel, ENT_QUOTES) ?>',
        <?= $quantity ?>,
        <?= $price ?>,
        <?= intval($id) ?>
    )">
                        <img class="item-img" src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($nom) ?>">
                    </div>
                    <div class="item-info">
                        <h3 class="titre"><?= htmlspecialchars($nom) ?></h3>
                        <p class="item-type">Type : <?= htmlspecialchars($typeLabel) ?></p>
                        <p class="description">Quantité : <?= $quantity ?></p>
                        <p class="prixOr"><?= number_format($price, 0, '', '') ?> gold</p>
                        <div class="btnPanier">
                            <?php if ($quantity > 0): ?>
                                <form method="GET" action="panier.php" target="panier-frame">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="id" value="<?= intval($id) ?>">
                                    <button type="submit" class="btnPanierImg-btn">
                                        <img src="img/addToCart.png" class="btnPanierImg" alt="Ajouter au panier">
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="btnPanierImg--disabled">Rupture de stock</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <p id="noResults" style="display:none; opacity:0.5; margin-top:2rem;">Aucun objet trouvé.</p>
    </main>

    <?php require 'include/footer.php'; ?>
    <iframe name="panier-frame" style="display:none;"></iframe>

    <!-- Item detail modal -->
    <div id="itemModalOverlay" class="modal-overlay" aria-hidden="true">
        <div class="modal">
            <button id="closeItemModalBtn" class="modal-close" type="button">&times;</button>
            <div id="itemModalContent" style="text-align:center;">
                <div class="item-img-wrapper" style="border-radius:10px; overflow:hidden; margin-bottom:16px;">
                    <img id="modalImg" src="" alt="" class="item-img" style="transform:scale(1);">
                </div>
                <h2 id="modalNom" class="titre" style="margin-bottom:8px;"></h2>
                <p id="modalType" class="item-type"></p>
                <p id="modalQte" class="description"></p>
                <p id="modalPrix" class="prixOr"></p>
                <div id="modalBtn" class="btnPanier" style="margin-top:16px;"></div>
            </div>
        </div>
    </div>

    <script>
        // ---- CART FEEDBACK ----
        document.querySelectorAll('.btnPanier form').forEach(form => {
            form.addEventListener('submit', function () {
                const btn = this.querySelector('.btnPanierImg-btn');
                const img = this.querySelector('.btnPanierImg');

                img.style.display = 'none';

                const msg = document.createElement('span');
                msg.textContent = 'Ajouté !';
                msg.className = 'cart-feedback-msg';
                btn.appendChild(msg);

                setTimeout(() => {
                    img.style.display = '';
                    msg.remove();
                }, 1500);
            });
        });

        // ---- FILTERS & SORTING ----
        function applyFilters() {
            const query = document.getElementById('searchInput').value.toLowerCase().trim();
            const checkedTypes = [...document.querySelectorAll('input[name="type"]:checked')].map(cb => cb.value);
            const sortValue = document.querySelector('input[name="sort"]:checked')?.value ?? null;

            const grid = document.getElementById('itemGrid');
            let cards = [...grid.querySelectorAll('.itemBox')];

            cards.forEach(card => {
                const matchesSearch = !query || card.dataset.name.includes(query);
                const matchesType = checkedTypes.length === 0 || checkedTypes.includes(card.dataset.type);
                card.style.display = (matchesSearch && matchesType) ? '' : 'none';
            });

            if (sortValue) {
                cards
                    .filter(c => c.style.display !== 'none')
                    .sort((a, b) => sortValue === 'price_asc'
                        ? a.dataset.price - b.dataset.price
                        : b.dataset.price - a.dataset.price)
                    .forEach(card => grid.appendChild(card));
            }

            document.getElementById('noResults').style.display =
                cards.some(c => c.style.display !== 'none') ? 'none' : 'block';
        }

        document.getElementById('searchInput').addEventListener('input', applyFilters);

        // ---- ITEM MODAL ----
        const itemOverlay = document.getElementById('itemModalOverlay');
        document.getElementById('closeItemModalBtn').addEventListener('click', () => {
            itemOverlay.classList.remove('visible');
            itemOverlay.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('blurred');
        });
        itemOverlay.addEventListener('click', function (e) {
            if (e.target === itemOverlay) {
                itemOverlay.classList.remove('visible');
                itemOverlay.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('blurred');
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && itemOverlay.classList.contains('visible')) {
                itemOverlay.classList.remove('visible');
                itemOverlay.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('blurred');
            }
        });

        function openItemModal(image, nom, type, qte, prix, id) {
            document.getElementById('modalImg').src = image;
            document.getElementById('modalImg').alt = nom;
            document.getElementById('modalImg').style.cssText = 'height:50px; object-fit:contain; background:#151515; border-radius:10px; margin-bottom:16px;';
            document.getElementById('modalNom').textContent = nom;
            document.getElementById('modalType').textContent = 'Type : ' + type;
            document.getElementById('modalQte').textContent = 'Quantité : ' + qte;
            document.getElementById('modalPrix').textContent = prix.toLocaleString() + ' gold';

            const btnDiv = document.getElementById('modalBtn');
            if (qte > 0) {
                btnDiv.innerHTML = `
            <form method="GET" action="panier.php" target="panier-frame">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id" value="${id}">
                <button type="submit" class="btnPanierImg-btn">
                    <img src="img/addToCart.png" class="btnPanierImg" alt="Ajouter au panier">
                </button>
            </form>`;
                btnDiv.querySelector('form').addEventListener('submit', function () {
                    const img = this.querySelector('.btnPanierImg');
                    const btn = this.querySelector('.btnPanierImg-btn');
                    img.style.display = 'none';
                    const msg = document.createElement('span');
                    msg.textContent = 'Ajouté !';
                    msg.className = 'cart-feedback-msg';
                    btn.appendChild(msg);
                    setTimeout(() => { img.style.display = ''; msg.remove(); }, 1500);
                });
            } else {
                btnDiv.innerHTML = `<span class="btnPanierImg--disabled">Rupture de stock</span>`;
            }

            itemOverlay.classList.add('visible');
            itemOverlay.setAttribute('aria-hidden', 'false');
            document.body.classList.add('blurred');
        }
    </script>
</body>

</html>