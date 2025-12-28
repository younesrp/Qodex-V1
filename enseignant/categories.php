<?php
session_start();
require_once '../config/database.php';

// Vérifier que l'utilisateur est enseignant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'enseignant') {
    header('Location: ../auth/login.php');
    exit;
}

// SUPPRESSION d'une catégorie
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$delete_id]);
    header('Location: categories.php'); // Refresh page après suppression
    exit;
}

// Ajouter une catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    if ($name !== '') {
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        header('Location: categories.php'); // Refresh page
        exit;
    } else {
        $error = "Le nom de la catégorie est obligatoire.";
    }
}

// Récupérer toutes les catégories
$stmt = $conn->query("SELECT * FROM categories ORDER BY created_at DESC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Catégories</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #F8FAFC; padding: 2rem; color: #0A0E27; }
        h1 { text-align: center; margin-bottom: 2rem; }
        .container { max-width: 800px; margin: 45px auto; }
        form { background: #FFFFFF; padding: 2rem; border-radius: 20px; margin-bottom: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        input, textarea { width: 100%; padding: 1rem; border-radius: 10px; border: 1px solid #ccc; margin-bottom: 1rem; }
        button { padding: 1rem; width: 100%; border-radius: 10px; border: none; background: #0066FF; color: white; font-weight: 700; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; border-bottom: 1px solid #ccc; text-align: left; }
        th { background: #0066FF; color: white; }
        .error { color: red; margin-bottom: 1rem; }
        .delete-btn { color: red; text-decoration: none; font-weight: 700; }
    </style>
    <style>
/* ==================== RESET ==================== */
* { margin:0; padding:0; box-sizing:border-box; }
:root {
    --primary: #0066FF;
    --secondary: #00D9FF;
    --dark: #0A0E27;
    --light: #F8FAFC;
    --gray: #64748B;
    --white: #FFFFFF;
}
body { font-family:'Inter', sans-serif; background: var(--light); color: var(--dark); overflow-x:hidden; }

/* ==================== NAV ==================== */
nav { position:fixed; top:0; width:100%; background: rgba(255,255,255,0.9); backdrop-filter: blur(20px); border-bottom:1px solid rgba(0,102,255,0.1); z-index:1000; }
nav .container { max-width:1400px; margin:0 auto; padding:1rem 3rem; display:flex; justify-content:space-between; align-items:center; }
nav .logo { font-weight:700; font-size:1.5rem; color:var(--dark); text-decoration:none; }
nav ul { display:flex; list-style:none; gap:2rem; }
nav ul li a { text-decoration:none; color:var(--gray); font-weight:500; transition:0.3s; }
nav ul li a:hover { color:var(--primary); }
.burger-menu { display:none; flex-direction:column; gap:5px; cursor:pointer; }
.burger-menu span { width:28px; height:3px; background:var(--dark); border-radius:3px; transition:0.3s; }

/* ==================== HERO ==================== */
#hero { padding-top:100px; background: linear-gradient(135deg, #0A0E27 0%, #1a1f3a 100%); color:var(--white); text-align:center; padding:4rem 2rem; }
#hero h1 { font-size:2.5rem; margin-bottom:0.5rem; }
#hero p { font-size:1.1rem; color: rgba(255,255,255,0.7); }

/* ==================== DASHBOARD ==================== */
.dashboard { max-width:1200px; margin:3rem auto; display:grid; grid-template-columns: 1fr 1fr; gap:2rem; padding:0 1.5rem; }
.card { background: var(--white); border-radius:24px; padding:2rem; box-shadow:0 15px 40px rgba(0,0,0,0.05); transition:0.3s; }
.card:hover { transform: translateY(-5px); box-shadow:0 20px 60px rgba(0,0,0,0.1); }
.card h2 { font-size:1.5rem; font-weight:700; margin-bottom:1rem; }
.card p { color: var(--gray); line-height:1.5; margin-bottom:1rem; }
.card table { width:100%; border-collapse:collapse; }
.card th, .card td { padding:0.8rem; border-bottom:1px solid #e0e0e0; text-align:left; }
.card th { background: var(--primary); color: var(--white); }
.card a { color:var(--primary); font-weight:600; text-decoration:none; }

/* ==================== FOOTER ==================== */
footer { text-align:center; padding:3rem 1rem; color: rgba(0,0,0,0.6); }

/* ==================== RESPONSIVE ==================== */
@media(max-width:768px){ .dashboard{grid-template-columns:1fr;} nav ul{display:none;} .burger-menu{display:flex;} }
</style>
</head>
<body>
    <nav>
    <div class="container">
        <a href="#" class="logo">Qo<span>dex</span></a>
        <div class="burger-menu" onclick="toggleMenu()"><span></span><span></span><span></span></div>
        <ul id="navMenu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="#">Quiz</a></li>
            <li><a href="?action=logout">Déconnexion</a></li>
        </ul>
    </div>
</nav>
    <div class="container">
        <h1>Gestion des Catégories</h1>

        <?php if(!empty($error)) echo "<div class='error'>$error</div>"; ?>

        <form method="POST" action="">
            <h2>Ajouter une catégorie</h2>
            <input type="text" name="name" placeholder="Nom de la catégorie" required>
            <textarea name="description" placeholder="Description (facultatif)"></textarea>
            <button type="submit">Ajouter</button>
        </form>

        <h2>Liste des catégories</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Description</th>
                <th>Créé le</th>
                <th>Actions</th>
            </tr>
            <?php foreach($categories as $cat): ?>
            <tr>
                <td><?= $cat['id'] ?></td>
                <td><?= htmlspecialchars($cat['name']) ?></td>
                <td><?= htmlspecialchars($cat['description']) ?></td>
                <td><?= $cat['created_at'] ?></td>
                <td><a class="delete-btn" href="?delete=<?= $cat['id'] ?>" onclick="return confirm('Voulez-vous vraiment supprimer cette catégorie ?');">Supprimer</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
