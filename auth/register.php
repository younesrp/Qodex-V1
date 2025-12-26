<?php
require_once '../config/database.php';
session_start();
if (!$conn) {
    die("Database connection failed!");
}

if (isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}
if (isset($_POST['name'], $_POST['email'], $_POST['password'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role) ");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':role', $role);

    if ($stmt->execute()) {
        header('Location: ../auth/login.php');
        exit();
    } else {
        $error = "Registration failed. Please try again.";
    }
} else {
    $error = "";
}

?>
<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div id="form" method="POST" class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12 col-md-6">
            <h2 class="text-center mb-4">Register</h2>
            <form method="POST" action="register.php">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <select name="role" class="form-select" required>
                        <option value="" disabled selected>Select Role</option>
                        <option value="enseignant">enseignant</option>
                        <option value="etudiant">etudiant</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">Register</button>
            </form>
            <div class="text-center mt-3">
                Already have an account? <a href="/auth/login.php">Login here.</a>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/bootstrap.bundle.min.js"></script>