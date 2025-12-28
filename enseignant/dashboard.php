<?php
session_start();
require_once '../config/database.php';

// Vérifier que l'utilisateur est enseignant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'enseignant') {
    header('Location: ../auth/login.php');
    exit();
}

// Récupérer les informations de l'enseignant
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

// Statistiques de l'enseignant
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT q.id) as total_quizzes,
        COUNT(DISTINCT q2.id) as active_quizzes,
        COUNT(DISTINCT r.id) as total_attempts,
        COUNT(DISTINCT r.student_id) as total_students,
        AVG(r.percentage) as avg_success_rate
    FROM quizzes q
    LEFT JOIN quizzes q2 ON q.id = q2.id AND q2.is_active = TRUE
    LEFT JOIN results r ON q.id = r.quiz_id
    WHERE q.teacher_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Quiz avec nombre de questions et résultats
$stmt = $conn->prepare("
    SELECT 
        q.id,
        q.title,
        q.description,
        q.is_active,
        q.created_at,
        c.name as category_name,
        COUNT(DISTINCT qu.id) as question_count,
        COUNT(DISTINCT r.id) as attempt_count,
        COUNT(DISTINCT r.student_id) as student_count,
        AVG(r.percentage) as avg_score
    FROM quizzes q
    LEFT JOIN categories c ON q.category_id = c.id
    LEFT JOIN questions qu ON q.id = qu.quiz_id
    LEFT JOIN results r ON q.id = r.quiz_id
    WHERE q.teacher_id = ?
    GROUP BY q.id
    ORDER BY q.created_at DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Catégories récentes
$stmt = $conn->query("SELECT * FROM categories ORDER BY created_at DESC LIMIT 5");
$recent_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Bonjour, <?= htmlspecialchars($teacher['username']) ?> !</h1>
            <p class="lead">Bienvenue sur votre tableau de bord enseignant.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="add_quiz.php" class="btn btn-primary btn-lg">
                <i class="bi bi-plus-circle"></i> Créer un quiz
            </a>
        </div>
    </div>
    
    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="bi bi-puzzle display-4 mb-2"></i>
                    <h2 class="mb-0"><?= $stats['total_quizzes'] ?? 0 ?></h2>
                    <p class="mb-0">Quiz créés</p>
                    <small><?= $stats['active_quizzes'] ?? 0 ?> actifs</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <i class="bi bi-people display-4 mb-2"></i>
                    <h2 class="mb-0"><?= $stats['total_students'] ?? 0 ?></h2>
                    <p class="mb-0">Étudiants uniques</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle display-4 mb-2"></i>
                    <h2 class="mb-0"><?= $stats['total_attempts'] ?? 0 ?></h2>
                    <p class="mb-0">Passages total</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="bi bi-graph-up display-4 mb-2"></i>
                    <h2 class="mb-0"><?= number_format($stats['avg_success_rate'] ?? 0, 1) ?>%</h2>
                    <p class="mb-0">Taux de réussite</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contenu principal -->
    <div class="row">
        <!-- Quiz récents -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Vos quiz récents</h5>
                    <a href="manage_quizzes.php" class="btn btn-sm btn-light">Voir tous</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_quizzes)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-puzzle display-1 text-muted mb-3"></i>
                            <h4 class="mt-3">Aucun quiz créé</h4>
                            <p class="text-muted">Commencez par créer votre premier quiz.</p>
                            <a href="add_quiz.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Créer un quiz
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Titre</th>
                                        <th>Questions</th>
                                        <th>Passages</th>
                                        <th>Score moyen</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_quizzes as $quiz): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($quiz['title']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($quiz['category_name']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?= $quiz['question_count'] ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= $quiz['attempt_count'] ?></span>
                                        </td>
                                        <td>
                                            <?php if ($quiz['attempt_count'] > 0): ?>
                                                <div class="progress" style="height: 10px;">
                                                    <div class="progress-bar 
                                                        <?= $quiz['avg_score'] >= 80 ? 'bg-success' : 
                                                           ($quiz['avg_score'] >= 50 ? 'bg-warning' : 'bg-danger') ?>" 
                                                        style="width: <?= $quiz['avg_score'] ?>%;">
                                                    </div>
                                                </div>
                                                <small><?= number_format($quiz['avg_score'], 1) ?>%</small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $quiz['is_active'] ? 'success' : 'danger' ?>">
                                                <?= $quiz['is_active'] ? 'Actif' : 'Inactif' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view_quiz.php?id=<?= $quiz['id'] ?>" 
                                                   class="btn btn-outline-primary" title="Voir les questions">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="view_results.php?quiz_id=<?= $quiz['id'] ?>" 
                                                   class="btn btn-outline-info" title="Voir résultats">
                                                    <i class="bi bi-graph-up"></i>
                                                </a>
                                                <a href="manage_quizzes.php" 
                                                   class="btn btn-outline-secondary" title="Gérer">
                                                    <i class="bi bi-gear"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Catégories récentes -->
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-tags"></i> Catégories disponibles</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_categories)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Aucune catégorie n'a été créée.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Description</th>
                                        <th>Créée le</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_categories as $cat): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($cat['name']) ?></td>
                                        <td><?= htmlspecialchars($cat['description']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($cat['created_at'])) ?></td>
                                        <td>
                                            <a href="categories.php" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="categories.php" class="btn btn-outline-primary">
                                <i class="bi bi-tags"></i> Gérer les catégories
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
                        <a href="add_quiz.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-plus-circle"></i> Créer un quiz
                        </a>
                        <a href="categories.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-tags"></i> Gérer les catégories
                        </a>
                        <a href="manage_quizzes.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-list-check"></i> Gérer les quiz
                        </a>
                        <a href="statistics.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-bar-chart"></i> Statistiques
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Statistiques rapides -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-award"></i> Vos meilleurs quiz</h5>
                    <?php
                    // Meilleurs quiz par score moyen
                    $stmt = $conn->prepare("
                        SELECT q.title, AVG(r.percentage) as avg_score, COUNT(r.id) as attempts
                        FROM quizzes q
                        LEFT JOIN results r ON q.id = r.quiz_id
                        WHERE q.teacher_id = ?
                        GROUP BY q.id
                        HAVING attempts > 0
                        ORDER BY avg_score DESC
                        LIMIT 3
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    $top_quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <?php if (empty($top_quizzes)): ?>
                        <div class="text-center py-3">
                            <i class="bi bi-emoji-smile text-muted display-4"></i>
                            <p class="text-muted mt-2 mb-0">Aucun résultat encore</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($top_quizzes as $index => $quiz): ?>
                            <div class="list-group-item border-0 px-0 py-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-circle me-2"><?= $index + 1 ?></span>
                                        <span><?= htmlspecialchars(substr($quiz['title'], 0, 20)) ?><?= strlen($quiz['title']) > 20 ? '...' : '' ?></span>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold"><?= number_format($quiz['avg_score'], 1) ?>%</div>
                                        <small class="text-muted"><?= $quiz['attempts'] ?> passages</small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>