<?php
// Démarrage de session sécurisé, avant toute sortie HTML
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
