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

// Activer/Désactiver un quiz
if (isset($_POST['toggle_status'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Token de sécurité invalide.";
    } else {
        $quiz_id = (int)$_POST['quiz_id'];
        
        // Vérifier que le quiz appartient à l'enseignant
        $stmt = $conn->prepare("SELECT id, is_active FROM quizzes WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$quiz_id, $_SESSION['user_id']]);
        $quiz = $stmt->fetch();
        
        if ($quiz) {
            $new_status = $quiz['is_active'] ? 0 : 1;
            $stmt = $conn->prepare("UPDATE quizzes SET is_active = ? WHERE id = ?");
            
            if ($stmt->execute([$new_status, $quiz_id])) {
                $success = "Statut du quiz mis à jour.";
            } else {
                $error = "Erreur lors de la mise à jour.";
            }
        } else {
            $error = "Quiz non trouvé ou non autorisé.";
        }
    }
}

// Supprimer un quiz
if (isset($_POST['delete_quiz'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Token de sécurité invalide.";
    } else {
        $quiz_id = (int)$_POST['quiz_id'];
        
        // Vérifier que le quiz appartient à l'enseignant
        $stmt = $conn->prepare("SELECT id FROM quizzes WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$quiz_id, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            // La suppression en cascade est gérée par les foreign keys
            $stmt = $conn->prepare("DELETE FROM quizzes WHERE id = ?");
            
            if ($stmt->execute([$quiz_id])) {
                $success = "Quiz supprimé avec succès.";
            } else {
                $error = "Erreur lors de la suppression.";
            }
        } else {
            $error = "Quiz non trouvé ou non autorisé.";
        }
    }
}

// Récupérer les quiz de l'enseignant
$stmt = $conn->prepare("
    SELECT q.*, c.name as category_name, 
           COUNT(DISTINCT qu.id) as question_count,
           COUNT(DISTINCT r.id) as result_count
    FROM quizzes q
    LEFT JOIN categories c ON q.category_id = c.id
    LEFT JOIN questions qu ON q.id = qu.quiz_id
    LEFT JOIN results r ON q.id = r.quiz_id
    WHERE q.teacher_id = ?
    GROUP BY q.id
    ORDER BY q.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-gear"></i> Gestion des Quiz</h1>
        <a href="add_quiz.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nouveau Quiz
        </a>
    </div>
    
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if (empty($quizzes)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-puzzle display-1 text-muted mb-3"></i>
                <h3>Aucun quiz créé</h3>
                <p class="text-muted">Commencez par créer votre premier quiz.</p>
                <a href="add_quiz.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Créer un quiz
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($quizzes as $quiz): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($quiz['title']) ?></h5>
                            <span class="badge bg-<?= $quiz['is_active'] ? 'success' : 'danger' ?>">
                                <?= $quiz['is_active'] ? 'Actif' : 'Inactif' ?>
                            </span>
                        </div>
                        
                        <p class="card-text text-muted"><?= htmlspecialchars($quiz['description']) ?></p>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted">
                                    <i class="bi bi-tag"></i> <?= htmlspecialchars($quiz['category_name']) ?>
                                </small>
                            </div>
                            <div class="col-6 text-end">
                                <small class="text-muted">
                                    <i class="bi bi-calendar"></i> <?= date('d/m/Y', strtotime($quiz['created_at'])) ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <div class="h5 mb-0"><?= $quiz['question_count'] ?></div>
                                    <small>Questions</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <div class="h5 mb-0"><?= $quiz['result_count'] ?></div>
                                    <small>Passages</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <div class="h5 mb-0"><?= $quiz['points'] ?? 0 ?></div>
                                    <small>Points</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
                                <button type="submit" name="toggle_status" class="btn btn-sm btn-<?= $quiz['is_active'] ? 'warning' : 'success' ?>">
                                    <i class="bi bi-power"></i> <?= $quiz['is_active'] ? 'Désactiver' : 'Activer' ?>
                                </button>
                            </form>
                            
                            <div>
                                <a href="view_results.php?quiz_id=<?= $quiz['id'] ?>" class="btn btn-sm btn-info">
                                    <i class="bi bi-graph-up"></i> Résultats
                                </a>
                                
                                <button type="button" class="btn btn-sm btn-danger" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteModal<?= $quiz['id'] ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal de confirmation pour suppression -->
                <div class="modal fade" id="deleteModal<?= $quiz['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirmer la suppression</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Êtes-vous sûr de vouloir supprimer le quiz "<strong><?= htmlspecialchars($quiz['title']) ?></strong>" ?</p>
                                <p class="text-danger">
                                    <i class="bi bi-exclamation-triangle"></i> Cette action supprimera également toutes les questions, réponses et résultats associés.
                                </p>
                            </div>
                            <div class="modal-footer">
                                <form method="POST" action="">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" name="delete_quiz" class="btn btn-danger">Supprimer définitivement</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>