<?php
// includes/header.php

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? $_SESSION['role'] : null;
$username = $isLoggedIn ? ($_SESSION['username'] ?? 'Utilisateur') : null;

// Fonction pour échapper les sorties
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Calculer le chemin de base
$current_dir = dirname($_SERVER['SCRIPT_NAME']);
if (strpos($current_dir, '/enseignant') !== false) {
    $root_path = '../';
} elseif (strpos($current_dir, '/etudiant') !== false) {
    $root_path = '../';
} elseif (strpos($current_dir, '/auth') !== false) {
    $root_path = '../';
} elseif (strpos($current_dir, '/includes') !== false) {
    $root_path = '../';
} else {
    $root_path = '';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qodex - Plateforme de Quiz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #00D9FF;
            --dark: #0A0E27;
            --light: #F8FAFC;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light);
            color: var(--dark);
            min-height: 100vh;
        }
        
        .navbar {
            background-color: white !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .brand {
            color: var(--primary);
            font-weight: 800;
            font-size: 1.5rem;
            text-decoration: none;
        }
        
        .brand:hover {
            color: var(--primary);
        }
        
        .btn-primary {
            background-color: var(--primary);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background-color: #0052cc;
        }
        
        .dropdown-menu {
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,.1);
            box-shadow: 0 5px 15px rgba(0,0,0,.1);
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm">
        <div class="container">
            <a class="navbar-brand brand" href="<?= $root_path ?>index.php">
                <i class="bi bi-puzzle-fill me-2"></i>Qodex
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarContent">
                <?php if ($isLoggedIn): ?>
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-speedometer2 me-1"></i>Dashboard
                            </a>
                        </li>
                        
                        <?php if ($userRole === 'enseignant'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="categories.php">
                                    <i class="bi bi-tags me-1"></i>Catégories
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="add_quiz.php">
                                    <i class="bi bi-plus-circle me-1"></i>Nouveau Quiz
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="manage_quizzes.php">
                                    <i class="bi bi-list-check me-1"></i>Gérer Quiz
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="available_quizzes.php">
                                    <i class="bi bi-collection-play me-1"></i>Quiz Disponibles
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="my_results.php">
                                    <i class="bi bi-graph-up me-1"></i>Mes Résultats
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" 
                                id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= escape($username) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item text-danger" href="<?= $root_path ?>logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $root_path ?>auth/login.php">Connexion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $root_path ?>auth/register.php">Inscription</a>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="py-4">
        <div class="container">