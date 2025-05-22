<?php
require_once 'php/config.php';
require_once 'php/auth.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = registerUser(
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['email'],
        $_POST['username'],
        $_POST['password'],
        $_POST['phone'] ?? null,
        $_POST['address'] ?? null
    );
    
    if ($result['success']) {
        redirectWith('login.php', $result['message'], 'success');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - White Lotus</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>Create an Account</h2>
        
        <?php if (isset($result) && !$result['success']): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($result['message']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="auth-form">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone (optional)</label>
                <input type="tel" id="phone" name="phone">
            </div>
            
            <div class="form-group">
                <label for="address">Address (optional)</label>
                <textarea id="address" name="address"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        
        <p class="auth-links">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </div>
</body>
</html> 