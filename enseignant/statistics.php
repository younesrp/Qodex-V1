<?php
session_start();
require_once '../config/database.php';

// Vérifier que l'utilisateur est enseignant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'enseignant') {
    header('Location: ../auth/login.php');
    exit;
}

// Statistiques globales
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT q.id) as total_quizzes,
        COUNT(DISTINCT q2.id) as active_quizzes,
        COUNT(DISTINCT r.id) as total_results,
        COUNT(DISTINCT u.id) as total_students,
        AVG(r.percentage) as avg_success_rate
    FROM users u
    LEFT JOIN quizzes q ON u.id = q.teacher_id
    LEFT JOIN quizzes q2 ON u.id = q2.teacher_id AND q2.is_active = TRUE
    LEFT JOIN results r ON q.id = r.quiz_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Quiz les plus populaires
$stmt = $conn->prepare("
    SELECT q.title, COUNT(r.id) as attempts, AVG(r.percentage) as avg_score
    FROM quizzes q
    LEFT JOIN results r ON q.id = r.quiz_id
    WHERE q.teacher_id = ?
    GROUP BY q.id
    ORDER BY attempts DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$popular_quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Performances par catégorie
$stmt = $conn->prepare("
    SELECT c.name, COUNT(r.id) as attempts, AVG(r.percentage) as avg_score
    FROM categories c
    JOIN quizzes q ON c.id = q.category_id
    LEFT JOIN results r ON q.id = r.quiz_id
    WHERE q.teacher_id = ?
    GROUP BY c.id
    ORDER BY attempts DESC
");
$stmt->execute([$_SESSION['user_id']]);
$category_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-bar-chart"></i> Statistiques</h1>
        <div class="btn-group">
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <button onclick="window.print()" class="btn btn-outline-secondary">
                <i class="bi bi-printer"></i> Imprimer
            </button>
        </div>
    </div>
    
    <!-- Statistiques principales -->
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
                    <h2 class="mb-0"><?= $stats['total_results'] ?? 0 ?></h2>
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
    
    <!-- Quiz les plus populaires -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-trophy"></i> Quiz les plus populaires</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($popular_quizzes)): ?>
                        <div class="alert alert-info">
                            Aucune donnée disponible.
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($popular_quizzes as $quiz): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($quiz['title']) ?></h6>
                                        <small class="text-muted">Score moyen: <?= number_format($quiz['avg_score'], 1) ?>%</small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill"><?= $quiz['attempts'] ?> passages</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Performances par catégorie -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-tags"></i> Performances par catégorie</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($category_stats)): ?>
                        <div class="alert alert-info">
                            Aucune donnée disponible.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Catégorie</th>
                                        <th>Passages</th>
                                        <th>Score moyen</th>
                                        <th>Performance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($category_stats as $cat): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($cat['name']) ?></td>
                                        <td><?= $cat['attempts'] ?></td>
                                        <td><?= number_format($cat['avg_score'], 1) ?>%</td>
                                        <td>
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar 
                                                    <?= $cat['avg_score'] >= 80 ? 'bg-success' : 
                                                       ($cat['avg_score'] >= 50 ? 'bg-warning' : 'bg-danger') ?>" 
                                                    style="width: <?= $cat['avg_score'] ?>%;">
                                                </div>
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
        </div>
    </div>
    
    <!-- Graphique des performances mensuelles -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-calendar-month"></i> Activité mensuelle</h5>
        </div>
        <div class="card-body">
            <?php
            // Récupérer les données mensuelles
            $stmt = $conn->prepare("
                SELECT 
                    DATE_FORMAT(r.submitted_at, '%Y-%m') as month,
                    COUNT(r.id) as attempts,
                    AVG(r.percentage) as avg_score
                FROM results r
                JOIN quizzes q ON r.quiz_id = q.id
                WHERE q.teacher_id = ?
                    AND r.submitted_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(r.submitted_at, '%Y-%m')
                ORDER BY month
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $monthly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <?php if (empty($monthly_data)): ?>
                <div class="alert alert-info">
                    Aucune activité enregistrée ces 6 derniers mois.
                </div>
            <?php else: ?>
                <canvas id="monthlyChart" height="100"></canvas>
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                const ctx = document.getElementById('monthlyChart').getContext('2d');
                const monthlyChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode(array_column($monthly_data, 'month')) ?>,
                        datasets: [{
                            label: 'Passages',
                            data: <?= json_encode(array_column($monthly_data, 'attempts')) ?>,
                            borderColor: '#0066FF',
                            backgroundColor: 'rgba(0, 102, 255, 0.1)',
                            tension: 0.4,
                            yAxisID: 'y'
                        }, {
                            label: 'Score moyen %',
                            data: <?= json_encode(array_column($monthly_data, 'avg_score')) ?>,
                            borderColor: '#00D9FF',
                            backgroundColor: 'rgba(0, 217, 255, 0.1)',
                            tension: 0.4,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Passages'
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Score moyen %'
                                },
                                grid: {
                                    drawOnChartArea: false,
                                },
                                min: 0,
                                max: 100
                            }
                        }
                    }
                });
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>