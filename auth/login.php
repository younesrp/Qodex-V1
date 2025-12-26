<?php

require_once '../config/database.php';
session_start();
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    header('Location: ' . '../' . $role . '/dashboard.php');
    exit();
}
if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header('Location: ' . '../' . $user['role'] . '/dashboard.php');
        exit();
    } else {
        $error = "Invalid email or password.";
    }
} else {
    $error = "";
}

?>

<div id="form" class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12 col-md-6">
            <h2 class="text-center mb-4">Login</h2>
            <form id="loginForm" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
            <div class="text-center mt-3">
                <a href="../auth/register.php  ">Don't have an account? Register here.</a>
            </div>
        </div>
        </div>
</div>
</body>
</html>
