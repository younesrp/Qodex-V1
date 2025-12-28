<?php
session_start();
require_once '../config/database.php';

// Vérifier que l'utilisateur est étudiant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'etudiant') {
    header('Location: ../auth/login.php');
    exit;
}

// Récupérer les informations de l'étudiant
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Statistiques de l'étudiant
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT r.quiz_id) as quizzes_taken,
        COUNT(r.id) as total_attempts,
        AVG(r.percentage) as avg_score,
        MAX(r.percentage) as best_score
    FROM results r
    WHERE r.student_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Derniers résultats
$stmt = $conn->prepare("
    SELECT r.*, q.title, c.name as category_name
    FROM results r
    JOIN quizzes q ON r.quiz_id = q.id
    LEFT JOIN categories c ON q.category_id = c.id
    WHERE r.student_id = ?
    ORDER BY r.submitted_at DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Bonjour, <?= htmlspecialchars($student['username']) ?> !</h1>
            <p class="lead">Bienvenue sur votre tableau de bord étudiant.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="available_quizzes.php" class="btn btn-primary btn-lg">
                <i class="bi bi-play-circle"></i> Commencer un quiz
            </a>
        </div>
    </div>
    
    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="bi bi-puzzle display-4 mb-2"></i>
                    <h2 class="mb-0"><?= $stats['quizzes_taken'] ?? 0 ?></h2>
                    <p class="mb-0">Quiz passés</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle display-4 mb-2"></i>
                    <h2 class="mb-0"><?= $stats['total_attempts'] ?? 0 ?></h2>
                    <p class="mb-0">Total passages</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="bi bi-graph-up display-4 mb-2"></i>
                    <h2 class="mb-0"><?= number_format($stats['avg_score'] ?? 0, 1) ?>%</h2>
                    <p class="mb-0">Score moyen</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="bi bi-trophy display-4 mb-2"></i>
                    <h2 class="mb-0"><?= number_format($stats['best_score'] ?? 0, 1) ?>%</h2>
                    <p class="mb-0">Meilleur score</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Derniers résultats -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Derniers résultats</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_results)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-clipboard-data display-1 text-muted"></i>
                            <h4 class="mt-3">Aucun résultat</h4>
                            <p class="text-muted">Vous n'avez pas encore passé de quiz.</p>
                            <a href="available_quizzes.php" class="btn btn-primary">
                                <i class="bi bi-play-circle"></i> Commencer votre premier quiz
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Quiz</th>
                                        <th>Catégorie</th>
                                        <th>Score</th>
                                        <th>Date</th>
                                        <th>Détails</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_results as $result): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($result['title']) ?></td>
                                        <td><?= htmlspecialchars($result['category_name']) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                                    <div class="progress-bar 
                                                        <?= $result['percentage'] >= 80 ? 'bg-success' : 
                                                           ($result['percentage'] >= 50 ? 'bg-warning' : 'bg-danger') ?>" 
                                                        style="width: <?= $result['percentage'] ?>%;">
                                                    </div>
                                                </div>
                                                <span class="fw-bold"><?= number_format($result['percentage'], 1) ?>%</span>
                                            </div>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($result['submitted_at'])) ?></td>
                                        <td>
                                            <a href="my_results.php?result_id=<?= $result['id'] ?>" 
                                               class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="my_results.php" class="btn btn-outline-primary">
                                <i class="bi bi-list-ul"></i> Voir tous mes résultats
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Actions rapides -->
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-lightning"></i> Actions rapides</h5>
                    <div class="list-group">
                        <a href="available_quizzes.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-play-circle"></i> Quiz disponibles
                            <span class="badge bg-primary float-end">
                                <?php
                                $stmt = $conn->prepare("
                                    SELECT COUNT(*) 
                                    FROM quizzes q
                                    WHERE q.is_active = TRUE
                                    AND q.id NOT IN (
                                        SELECT quiz_id FROM results WHERE student_id = ?
                                    )
                                ");
                                $stmt->execute([$_SESSION['user_id']]);
                                $available = $stmt->fetchColumn();
                                echo $available;
                                ?>
                            </span>
                        </a>
                        <a href="my_results.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-graph-up"></i> Mes résultats
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Progression -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-award"></i> Vos progrès</h5>
                    <div class="text-center py-3">
                        <div class="display-4 fw-bold text-primary">
                            <?= number_format($stats['avg_score'] ?? 0, 1) ?>%
                        </div>
                        <p class="text-muted">Score moyen</p>
                        
                        <div class="mt-4">
                            <p class="mb-1">Meilleur score</p>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-success" 
                                     style="width: <?= $stats['best_score'] ?? 0 ?>%;">
                                    <?= number_format($stats['best_score'] ?? 0, 1) ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>