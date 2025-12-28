<?php
session_start();
require_once '../config/database.php';

// Vérifier que l'utilisateur est enseignant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'enseignant') {
    header('Location: ../auth/login.php');
    exit;
}

$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;

// Vérifier que le quiz appartient à l'enseignant
$stmt = $conn->prepare("SELECT id, title FROM quizzes WHERE id = ? AND teacher_id = ?");
$stmt->execute([$quiz_id, $_SESSION['user_id']]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header('Location: manage_quizzes.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Récupérer les résultats avec pagination - CORRECTION ICI
$stmt = $conn->prepare("
    SELECT r.*, u.username, u.email
    FROM results r
    JOIN users u ON r.student_id = u.id
    WHERE r.quiz_id = :quiz_id
    ORDER BY r.percentage DESC, r.submitted_at DESC
    LIMIT :limit OFFSET :offset
");

// CORRECTION : Utiliser bindValue avec les types appropriés
$stmt->bindValue(':quiz_id', $quiz_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pour chaque résultat, compter les réponses correctes
foreach ($results as &$result) {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_answers,
            SUM(is_correct) as correct_answers
        FROM student_answers 
        WHERE result_id = ?
    ");
    $stmt->execute([$result['id']]);
    $answers_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $result['total_answers'] = $answers_stats['total_answers'] ?? 0;
    $result['correct_answers'] = $answers_stats['correct_answers'] ?? 0;
}
unset($result); // Détruire la référence

// Compter le total pour la pagination
$stmt = $conn->prepare("SELECT COUNT(*) FROM results WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$totalResults = $stmt->fetchColumn();
$totalPages = ceil($totalResults / $limit);

// Statistiques
$stmt = $conn->prepare("
    SELECT 
        AVG(percentage) as avg_percentage,
        MAX(percentage) as max_percentage,
        MIN(percentage) as min_percentage,
        COUNT(*) as total_attempts,
        COUNT(DISTINCT student_id) as unique_students
    FROM results 
    WHERE quiz_id = ?
");
$stmt->execute([$quiz_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1><i class="bi bi-graph-up"></i> Résultats du Quiz</h1>
            <p class="lead mb-0"><?= htmlspecialchars($quiz['title']) ?></p>
        </div>
        <div class="btn-group">
            <a href="view_quiz.php?id=<?= $quiz_id ?>" class="btn btn-outline-primary">
                <i class="bi bi-eye"></i> Voir questions
            </a>
            <a href="manage_quizzes.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>
    
    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-2 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="bi bi-people display-4 mb-2"></i>
                    <h3 class="mb-0"><?= $stats['unique_students'] ?? 0 ?></h3>
                    <p class="mb-0">Étudiants</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-2 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle display-4 mb-2"></i>
                    <h3 class="mb-0"><?= $stats['total_attempts'] ?? 0 ?></h3>
                    <p class="mb-0">Passages</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-2 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="bi bi-graph-up display-4 mb-2"></i>
                    <h3 class="mb-0"><?= number_format($stats['avg_percentage'] ?? 0, 1) ?>%</h3>
                    <p class="mb-0">Moyenne</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-2 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="bi bi-trophy display-4 mb-2"></i>
                    <h3 class="mb-0"><?= number_format($stats['max_percentage'] ?? 0, 1) ?>%</h3>
                    <p class="mb-0">Meilleur</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-2 mb-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <i class="bi bi-exclamation-triangle display-4 mb-2"></i>
                    <h3 class="mb-0"><?= number_format($stats['min_percentage'] ?? 0, 1) ?>%</h3>
                    <p class="mb-0">Plus bas</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-2 mb-3">
            <div class="card bg-dark text-white">
                <div class="card-body text-center">
                    <i class="bi bi-bar-chart display-4 mb-2"></i>
                    <h3 class="mb-0"><?= $totalResults ?></h3>
                    <p class="mb-0">Résultats</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tableau des résultats -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-list-check"></i> Détails des résultats</h5>
        </div>
        <div class="card-body">
            <?php if (empty($results)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-clipboard-data display-1 text-muted"></i>
                    <h4 class="mt-3">Aucun résultat pour ce quiz</h4>
                    <p class="text-muted">Aucun étudiant n'a encore passé ce quiz.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Étudiant</th>
                                <th>Email</th>
                                <th>Score</th>
                                <th>Réponses</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $index => $result): ?>
                            <tr>
                                <td><?= $offset + $index + 1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($result['username']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($result['email']) ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                            <div class="progress-bar 
                                                <?= $result['percentage'] >= 80 ? 'bg-success' : 
                                                   ($result['percentage'] >= 50 ? 'bg-warning' : 'bg-danger') ?>" 
                                                style="width: <?= $result['percentage'] ?>%;">
                                            </div>
                                        </div>
                                        <div class="text-nowrap">
                                            <span class="fw-bold"><?= number_format($result['percentage'], 1) ?>%</span><br>
                                            <small class="text-muted"><?= $result['score'] ?>/<?= $result['total_points'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <small>
                                        <?= $result['correct_answers'] ?> correctes / <?= $result['total_answers'] ?>
                                    </small><br>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-success" 
                                             style="width: <?= $result['total_answers'] > 0 ? ($result['correct_answers'] / $result['total_answers']) * 100 : 0 ?>%;">
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($result['submitted_at'])) ?><br>
                                    <small class="text-muted"><?= date('H:i', strtotime($result['submitted_at'])) ?></small>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#detailModal<?= $result['id'] ?>">
                                        <i class="bi bi-eye"></i> Détails
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?quiz_id=<?= $quiz_id ?>&page=<?= $page-1 ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php 
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        
                        for ($i = $start; $i <= $end; $i++): 
                        ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?quiz_id=<?= $quiz_id ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?quiz_id=<?= $quiz_id ?>&page=<?= $page+1 ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Détails -->
<?php foreach ($results as $result): ?>
<div class="modal fade" id="detailModal<?= $result['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails du résultat - <?= htmlspecialchars($result['username']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h3 class="mb-0"><?= $result['score'] ?></h3>
                                <small>Score</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h3 class="mb-0"><?= $result['total_points'] ?></h3>
                                <small>Total points</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h3 class="mb-0"><?= number_format($result['percentage'], 1) ?>%</h3>
                                <small>Pourcentage</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h3 class="mb-0"><?= $result['correct_answers'] ?>/<?= $result['total_answers'] ?></h3>
                                <small>Réponses correctes</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h6>Réponses détaillées:</h6>
                <?php
                // Récupérer les réponses détaillées
                $stmt = $conn->prepare("
                    SELECT sa.*, q.question_text, a.answer_text as correct_answer
                    FROM student_answers sa
                    JOIN questions q ON sa.question_id = q.id
                    LEFT JOIN answers a ON sa.answer_id = a.id
                    WHERE sa.result_id = ?
                    ORDER BY sa.question_id
                ");
                $stmt->execute([$result['id']]);
                $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($details)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Aucun détail de réponse disponible.
                    </div>
                <?php else: ?>
                    <?php foreach ($details as $detail): ?>
                    <div class="card mb-2 <?= $detail['is_correct'] ? 'border-success' : 'border-danger' ?>">
                        <div class="card-body">
                            <p class="mb-2"><strong>Q:</strong> <?= htmlspecialchars($detail['question_text']) ?></p>
                            <p class="mb-1">
                                <strong>Réponse donnée:</strong> 
                                <?= htmlspecialchars($detail['answer_text'] ?? 'Non répondue') ?>
                            </p>
                            <?php if (!empty($detail['correct_answer'])): ?>
                            <p class="mb-1">
                                <strong>Réponse correcte:</strong> 
                                <?= htmlspecialchars($detail['correct_answer']) ?>
                            </p>
                            <?php endif; ?>
                            <p class="mb-0">
                                <strong>Statut:</strong> 
                                <span class="badge bg-<?= $detail['is_correct'] ? 'success' : 'danger' ?>">
                                    <?= $detail['is_correct'] ? 'Correcte' : 'Incorrecte' ?>
                                </span>
                                <span class="ms-2">
                                    <strong>Points:</strong> 
                                    <span class="<?= $detail['is_correct'] ? 'text-success' : 'text-danger' ?>">
                                        <?= $detail['points_earned'] ?> point(s)
                                    </span>
                                </span>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include '../includes/footer.php'; ?>