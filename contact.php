<?php
session_start();
include 'functions.php';
include 'header.php';

$message = "";

if (isset($_POST['send'])) {
    if (!verify_csrf_token()) {
        $message = 'Your session has expired. Please try again.';
    } else {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $msg = trim($_POST['message'] ?? '');

    if ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && $msg !== '') {
        $pdo = pdo_connect_mysql();

        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");

        if ($stmt->execute([$name, $email, $msg])) {
            $message = "Thank you! Your message has been sent.";
        } else {
            $message = "Something went wrong. Please try again.";
        }
    } else {
        $message = "Please fill in all fields.";
    }
    }
}
?>

<main>
    <div class="store-container">

        <div class="contact-page">
            <h2>Contact Us</h2>
            <p>If you have any questions or need help, please fill out the form below.</p>

            <?php if ($message != ""): ?>
                <div class="contact-msg"><?php echo $message; ?></div>
            <?php endif; ?>

            <form class="contact-form" method="POST">
                <?php csrf_field(); ?>
                <div class="form-group">
                    <label>Your Name</label>
                    <input type="text" name="name" placeholder="Enter your name">
                </div>

                <div class="form-group">
                    <label>Your Email</label>
                    <input type="email" name="email" placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label>Your Message</label>
                    <textarea name="message" rows="5" placeholder="Write your message here..."></textarea>
                </div>

                <button type="submit" name="send" class="contact-btn">Send Message</button>
            </form>
        </div>

    </div>
</main>

<?php include 'footer.php'; ?>