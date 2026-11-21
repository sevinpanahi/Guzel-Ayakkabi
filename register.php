<?php
session_start();
include 'functions.php';
$pdo = pdo_connect_mysql();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = "";

if (isset($_POST['register'])) {
    if (!verify_csrf_token()) {
        $message = 'Your session has expired. Please try again.';
    } else {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && $password !== '' && strlen($password) >= 8) {
        if ($password === $confirm_password) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $message = "This email is already registered.";
            } else {
                $hashed_pass = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'user', 'active')");
                
                if ($stmt->execute([$name, $email, $hashed_pass])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_role'] = 'user';
                    
                    header('Location: index.php');
                    exit;
                } else {
                    $message = "Something went wrong. Please try again.";
                }
            }
        } else {
            $message = "Passwords do not match.";
        }
    } else {
        $message = "Please enter a name, a valid email and a password of at least 8 characters.";
    }
    }
}

include 'header.php';
?>

<main>
    <div class="store-container">
        <div class="auth-box">
            <h2>Create an Account</h2>
            
            <?php if ($message != ""): ?>
                <div class="auth-msg"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php csrf_field(); ?>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" name="register" class="auth-btn">Register</button>
            </form>

            <p class="auth-switch">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>