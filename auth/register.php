<?php
session_start();
require_once '../config/database.php';

// Vérifier que l'utilisateur est enseignant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'enseignant') {
    header('Location: ../auth/login.php');
    exit;
}

// Générer token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

// SUPPRESSION d'une catégorie (avec vérification CSRF)
if (isset($_POST['delete']) && isset($_POST['category_id'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Token de sécurité invalide.";
    } else {
        $delete_id = (int)$_POST['category_id'];
        
        // Vérifier si la catégorie est utilisée dans des quiz
        $stmt = $conn->prepare("SELECT COUNT(*) FROM quizzes WHERE category_id = ?");
        $stmt->execute([$delete_id]);
        
        if ($stmt->fetchColumn() > 0) {
            $error = "Cette catégorie est utilisée dans des quiz et ne peut être supprimée.";
        } else {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$delete_id])) {
                $success = "Catégorie supprimée avec succès.";
            } else {
                $error = "Erreur lors de la suppression.";
            }
        }
    }
}

// Ajouter une catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Token de sécurité invalide.";
    } else {
        $name = trim(htmlspecialchars($_POST['name']));
        $description = trim(htmlspecialchars($_POST['description'] ?? ''));
        
        if (empty($name)) {
            $error = "Le nom de la catégorie est obligatoire.";
        } elseif (strlen($name) > 100) {
            $error = "Le nom ne doit pas dépasser 100 caractères.";
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            if ($stmt->execute([$name, $description])) {
                $success = "Catégorie ajoutée avec succès.";
                // Redirection pour éviter la re-soumission
                header('Location: categories.php?success=1');
                exit();
            } else {
                $error = "Erreur lors de l'ajout de la catégorie.";
            }
        }
    }
}

// Récupérer toutes les catégories
$stmt = $conn->query("SELECT * FROM categories ORDER BY created_at DESC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-tags"></i> Gestion des Catégories</h1>
        <a href="dashboard.php" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>
    
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($success || isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $success ?: 'Opération effectuée avec succès.' ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Ajouter une catégorie</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom de la catégorie *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   required maxlength="100" placeholder="Ex: Mathématiques">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3" placeholder="Description optionnelle"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save"></i> Ajouter la catégorie
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-list"></i> Liste des catégories</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($categories)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Aucune catégorie n'a été créée.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><?= $cat['id'] ?></td>
                                        <td><?= htmlspecialchars($cat['name']) ?></td>
                                        <td><?= htmlspecialchars($cat['description']) ?></td>
                                        <td>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                                <button type="submit" name="delete" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>