<?php
// includes/header.php
session_start();
require_once 'security.php';

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? $_SESSION['role'] : null;
$username = $isLoggedIn ? $_SESSION['username'] ?? 'Utilisateur' : null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qodex - Plateforme Quiz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0066FF;
            --secondary: #00D9FF;
            --dark: #0A0E27;
            --light: #F8FAFC;
            --success: #10B981;
            --danger: #EF4444;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--light);
            min-height: 100vh;
        }
        .nav-link.active {
            color: var(--primary) !important;
            font-weight: 600;
        }
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .btn-primary {
            background: var(--primary);
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
        }
        .btn-primary:hover {
            background: #0052CC;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">
                <span style="color: var(--primary);">Qo</span>dex
            </a>
            
            <?php if ($isLoggedIn): ?>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../<?= $userRole ?>/dashboard.php">Dashboard</a>
                    </li>
                    <?php if ($userRole === 'enseignant'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../enseignant/categories.php">Catégories</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../enseignant/add_quiz.php">Nouveau Quiz</a>
                        </li>
                    <?php elseif ($userRole === 'etudiant'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../etudiant/available_quizzes.php">Quiz Disponibles</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <?= Security::xssProtect($username) ?>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../<?= $userRole ?>/dashboard.php">Profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../logout.php">Déconnexion</a></li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </nav>