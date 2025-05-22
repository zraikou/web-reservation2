<?php
require_once 'php/config.php';
require_once 'php/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectWith('index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = loginUser($_POST['username'], $_POST['password']);
    
    if ($result['success']) {
        redirectWith('index.php', 'Welcome back, ' . $_SESSION['first_name'] . '!', 'success');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - White Lotus</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>Login to Your Account</h2>
        
        <?php if (isset($result) && !$result['success']): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($result['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php 
                echo htmlspecialchars($_SESSION['message']);
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="auth-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        
        <p class="auth-links">
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </div>
</body>
</html> 