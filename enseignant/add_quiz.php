<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'enseignant') {
    header('Location: ../auth/login.php');
    exit;
}

$stmt = $conn->query("SELECT id, name FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = (int) $_POST['category_id'];
    $teacher_id = $_SESSION['user_id'];

    if ($title === '' || $category_id <= 0) {
        $error = "Tous les champs obligatoires doivent être remplis.";
    } else {
        $sql = "INSERT INTO quizzes (title, description, category_id, teacher_id)
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$title, $description, $category_id, $teacher_id]);

        $success = "Quiz ajouté avec succès.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un Quiz</title>

    <style>
        body {
            font-family: Inter, sans-serif;
            background: #0A0E27;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .quiz-box {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            width: 100%;
            max-width: 500px;
        }

        h2 {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            font-weight: 600;
            display: block;
            margin-bottom: .5rem;
        }

        input, textarea, select {
            width: 100%;
            padding: 1rem;
            border-radius: 10px;
            border: 1px solid #ccc;
        }

        button {
            width: 100%;
            padding: 1rem;
            background: #0066FF;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
        }

        .error {
            color: red;
            margin-bottom: 1rem;
            text-align: center;
        }

        .success {
            color: green;
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="quiz-box">
    <h2>Ajouter un Quiz</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-group">
            <label>Titre du quiz</label>
            <input type="text" name="title" required>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description"></textarea>
        </div>

        <div class="form-group">
            <label>Catégorie</label>
            <select name="category_id" required>
                <option value="">Choisir</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit">Créer le quiz</button>
    </form>
</div>

</body>
</html>
