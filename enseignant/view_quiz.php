<?php
session_start();
require_once '../config/database.php';

// Vérifier que l'utilisateur est enseignant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'enseignant') {
    header('Location: ../auth/login.php');
    exit;
}

$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Récupérer les informations du quiz
$stmt = $conn->prepare("
    SELECT q.*, c.name as category_name, u.username as teacher_name
    FROM quizzes q
    LEFT JOIN categories c ON q.category_id = c.id
    JOIN users u ON q.teacher_id = u.id
    WHERE q.id = ? AND q.teacher_id = ?
");
$stmt->execute([$quiz_id, $_SESSION['user_id']]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header('Location: manage_quizzes.php');
    exit();
}

// Récupérer les questions
$stmt = $conn->prepare("
    SELECT * FROM questions 
    WHERE quiz_id = ?
    ORDER BY id
");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les réponses pour chaque question
foreach ($questions as &$question) {
    $stmt = $conn->prepare("
        SELECT * FROM answers 
        WHERE question_id = ?
        ORDER BY id
    ");
    $stmt->execute([$question['id']]);
    $question['answers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($question); // Détruire la référence

// Statistiques du quiz
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT r.id) as total_attempts,
        COUNT(DISTINCT r.student_id) as total_students,
        AVG(r.percentage) as avg_score,
        MAX(r.percentage) as best_score,
        MIN(r.percentage) as worst_score
    FROM results r
    WHERE r.quiz_id = ?
");
$stmt->execute([$quiz_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1><i class="bi bi-eye"></i> Détails du Quiz</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="manage_quizzes.php">Quiz</a></li>
                    <li class="breadcrumb-item active">Détails</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="manage_quizzes.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <a href="view_results.php?quiz_id=<?= $quiz_id ?>" class="btn btn-info">
                <i class="bi bi-graph-up"></i> Voir résultats
            </a>
        </div>
    </div>
    
    <!-- Informations du quiz -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informations du quiz</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h3><?= htmlspecialchars($quiz['title']) ?></h3>
                    <p class="lead"><?= htmlspecialchars($quiz['description']) ?></p>
                    
                    <div class="mb-3">
                        <span class="badge bg-secondary me-2">
                            <i class="bi bi-tag"></i> <?= htmlspecialchars($quiz['category_name']) ?>
                        </span>
                        <span class="badge bg-<?= $quiz['is_active'] ? 'success' : 'danger' ?> me-2">
                            <i class="bi bi-power"></i> <?= $quiz['is_active'] ? 'Actif' : 'Inactif' ?>
                        </span>
                        <span class="badge bg-info">
                            <i class="bi bi-calendar"></i> <?= date('d/m/Y', strtotime($quiz['created_at'])) ?>
                        </span>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h2 class="mb-0"><?= count($questions) ?></h2>
                            <p class="text-muted mb-0">Questions</p>
                            <hr class="my-2">
                            <h4 class="mb-0">
                                <?= array_sum(array_column($questions, 'points')) ?> points
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistiques -->
    <?php if ($stats['total_attempts'] > 0): ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="bi bi-people display-4 mb-2"></i>
                    <h3 class="mb-0"><?= $stats['total_students'] ?></h3>
                    <p class="mb-0">Étudiants</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle display-4 mb-2"></i>
                    <h3 class="mb-0"><?= $stats['total_attempts'] ?></h3>
                    <p class="mb-0">Passages</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="bi bi-graph-up display-4 mb-2"></i>
                    <h3 class="mb-0"><?= number_format($stats['avg_score'], 1) ?>%</h3>
                    <p class="mb-0">Moyenne</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="bi bi-trophy display-4 mb-2"></i>
                    <h3 class="mb-0"><?= number_format($stats['best_score'], 1) ?>%</h3>
                    <p class="mb-0">Meilleur</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Questions -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-question-circle"></i> Questions du quiz (<?= count($questions) ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($questions)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-question-lg display-1 text-muted"></i>
                    <h4 class="mt-3">Aucune question</h4>
                    <p class="text-muted">Ce quiz ne contient pas encore de questions.</p>
                    <a href="manage_quizzes.php" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Modifier le quiz
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($questions as $index => $question): ?>
                <div class="question-container card mb-4">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                Question <?= $index + 1 ?> 
                                <span class="badge bg-secondary ms-2"><?= $question['points'] ?> point(s)</span>
                                <span class="badge bg-<?= $question['question_type'] === 'single' ? 'primary' : 'info' ?> ms-1">
                                    <?= $question['question_type'] === 'single' ? 'Choix unique' : 'Choix multiple' ?>
                                </span>
                            </h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="lead"><?= htmlspecialchars($question['question_text']) ?></p>
                        
                        <div class="answers-section">
                            <h6>Réponses :</h6>
                            
                            <div class="row">
                                <?php if (!empty($question['answers'])): ?>
                                    <?php foreach ($question['answers'] as $answer): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="card <?= $answer['is_correct'] == 1 ? 'border-success bg-success-subtle' : 'border-secondary' ?>">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <?php if ($answer['is_correct'] == 1): ?>
                                                        <span class="badge bg-success me-2">
                                                            <i class="bi bi-check-circle"></i> Correcte
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary me-2">
                                                            <i class="bi bi-x-circle"></i> Incorrecte
                                                        </span>
                                                    <?php endif; ?>
                                                    <span><?= htmlspecialchars($answer['answer_text']) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="col-12">
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle"></i> 
                                            Aucune réponse définie pour cette question.
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="manage_quizzes.php" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i> Retour à la liste des quiz
                </a>
                <a href="view_results.php?quiz_id=<?= $quiz_id ?>" class="btn btn-info">
                    <i class="bi bi-graph-up"></i> Voir les résultats
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.question-container {
    border-left: 4px solid #0066FF;
}

.question-container .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.answers-section .card {
    transition: transform 0.2s;
}

.answers-section .card:hover {
    transform: translateY(-2px);
}

.border-success {
    border-color: #198754 !important;
}

.bg-success-subtle {
    background-color: #d1e7dd !important;
}

.border-secondary {
    border-color: #6c757d !important;
}
</style>

<?php include '../includes/footer.php'; ?>