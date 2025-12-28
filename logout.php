<?php
// logout.php à la racine
session_start();

// Détruire complètement la session
session_unset();
session_destroy();

// Rediriger vers la page de login
header('Location: auth/login.php');
exit();
?>