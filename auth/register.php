<?php
// auth/register.php - Version corrigée

session_start();
require_once '../config/database.php';

// Redirection si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: ../' . $_SESSION['role'] . '/dashboard.php');
    exit();
}

$error = '';
$success = '';

// Générer token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Erreur de sécurité. Veuillez rafraîchir la page.";
    } else {
        $name = trim(htmlspecialchars($_POST['name']));
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $role = $_POST['role'];

        // Validation
        if (strlen($name) < 2) {
            $error = "Le nom doit contenir au moins 2 caractères.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format d'email invalide.";
        } elseif (strlen($password) < 8) {
            $error = "Le mot de passe doit contenir au moins 8 caractères.";
        } elseif (!in_array($role, ['enseignant', 'etudiant'])) {
            $error = "Rôle invalide.";
        } else {
            // Vérifier si l'email existe déjà
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = "Cet email est déjà utilisé.";
            } else {
                // Hachage du mot de passe
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                
                // Insertion
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                
                if ($stmt->execute([$name, $email, $hashedPassword, $role])) {
                    $success = "Inscription réussie !";
                    header('Location: login.php?success=1');
                    exit();
                } else {
                    $error = "Erreur lors de l'inscription. Veuillez réessayer.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Qodex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0A0E27 0%, #1a1f3a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background: #0066FF;
            border: none;
            padding: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-6">
                <div class="register-card p-4">
                    <div class="text-center mb-4">
                        <h1 class="h3 fw-bold">Qodex</h1>
                        <p class="text-muted">Plateforme de quiz sécurisée</p>
                    </div>
                    
                    <h2 class="text-center mb-4">Inscription</h2>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        Inscription réussie ! Vous pouvez maintenant vous connecter.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nom complet *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       required minlength="2" placeholder="Votre nom">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       required placeholder="votre@email.com">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe *</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   required minlength="8" placeholder="Minimum 8 caractères">
                            <small class="form-text text-muted">Le mot de passe doit contenir au moins 8 caractères.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Rôle *</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="" selected disabled>Sélectionnez votre rôle</option>
                                <option value="enseignant">Enseignant</option>
                                <option value="etudiant">Étudiant</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            S'inscrire
                        </button>
                        
                        <div class="text-center">
                            <p class="mb-0">
                                Déjà un compte ? 
                                <a href="login.php" class="text-decoration-none fw-bold">
                                    Connectez-vous ici
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>