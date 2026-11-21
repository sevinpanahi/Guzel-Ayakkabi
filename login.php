<?php
session_start();
include 'functions.php';
$pdo = pdo_connect_mysql();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = "";

if (isset($_POST['login'])) {
    if (!verify_csrf_token()) {
        $message = 'Your session has expired. Please try again.';
    } else {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if ($email != "" && $password != "") {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            header('Location: index.php');
            exit;
        } else {
            $message = "Email or password is incorrect.";
        }
    } else {
        $message = "Please fill in both fields.";
    }
    }
}

include 'header.php';
?>

<main>
    <div class="store-container">
        <div class="auth-box">
            <h2>Login to Your Account</h2>

            <?php if ($message != ""): ?>
                <div class="auth-msg"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php csrf_field(); ?>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" name="login" class="auth-btn">Login</button>
            </form>

            <p class="auth-switch">
                Don't have an account? <a href="register.php">Sign up here</a>
            </p>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>