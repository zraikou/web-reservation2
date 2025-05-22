<?php
require_once 'config.php';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Get the action from the form data
    $action = isset($_POST['action']) ? $_POST['action'] : 'register';
    
    if ($action === 'logout') {
        // Check if it's an admin logout
        if (isset($_POST['admin']) && $_POST['admin'] === 'true') {
            $result = logoutAdmin();
        } else {
            $result = logoutUser();
        }
        echo json_encode($result);
        exit;
    } else if ($action === 'login') {
        // Handle login
        if (!isset($_POST['username']) || !isset($_POST['password'])) {
            echo json_encode(['success' => false, 'message' => 'Username and password are required']);
            exit;
        }
        
        $result = loginUser($_POST['username'], $_POST['password']);
        echo json_encode($result);
        exit;
    } else {
        // Handle registration
        if (!isset($_POST['first_name']) || !isset($_POST['last_name']) || !isset($_POST['email']) || 
            !isset($_POST['username']) || !isset($_POST['password'])) {
            echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
            exit;
        }
        
        $result = registerUser(
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['username'],
            $_POST['password'],
            $_POST['phone'] ?? null,
            $_POST['address'] ?? null
        );
        echo json_encode($result);
        exit;
    }
}

class Auth {
    private $conn;
    
    public function __construct() {
        $this->conn = connectDB();
    }
    
    // Login function
    public function login($username, $password) {
        // Sanitize inputs
        $username = sanitizeInput($username);
        
        // Prepare statement to prevent SQL injection
        $stmt = $this->conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Check if user exists
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                // Log the login activity
                $this->logActivity($user['id'], 'login');
                
                return true;
            }
        }
        
        return false;
    }
    
    // Logout function
    public function logout() {
        // Log the logout activity if user is logged in
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout');
        }
        
        // Destroy session
        session_unset();
        session_destroy();
        
        return true;
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    // Check if user has admin role
    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['role'] === 'admin';
    }
    
    // Register new user (admin only function)
    public function registerUser($username, $password, $role = 'staff') {
        // Check if user is admin
        if (!$this->isAdmin()) {
            return ['success' => false, 'message' => 'Unauthorized action'];
        }
        
        // Sanitize inputs
        $username = sanitizeInput($username);
        $role = sanitizeInput($role);
        
        // Check if username already exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $this->conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashedPassword, $role);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'User registered successfully'];
        } else {
            return ['success' => false, 'message' => 'Registration failed: ' . $this->conn->error];
        }
    }
    
    // Change password
    public function changePassword($userId, $currentPassword, $newPassword) {
        // Check if user is logged in and either changing their own password or is admin
        if (!$this->isLoggedIn() || ($_SESSION['user_id'] != $userId && !$this->isAdmin())) {
            return ['success' => false, 'message' => 'Unauthorized action'];
        }
        
        // Get current user data
        $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows !== 1) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        $user = $result->fetch_assoc();
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password']) && !$this->isAdmin()) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update the password
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Password changed successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to change password: ' . $this->conn->error];
        }
    }
    
    // Log user activity for auditing
    private function logActivity($userId, $action) {
        $stmt = $this->conn->prepare("INSERT INTO user_activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $stmt->bind_param("iss", $userId, $action, $ipAddress);
        $stmt->execute();
    }
    
    // Close connection
    public function __destruct() {
        $this->conn->close();
    }
}

// Register a new user
function registerUser($firstName, $lastName, $email, $username, $password, $phone = null, $address = null) {
    $conn = getDBConnection();
    
    try {
        $conn->beginTransaction();
        
        // Check if email or username already exists
        $stmt = $conn->prepare("SELECT guest_id FROM Guest WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        $stmt = $conn->prepare("SELECT guest_id FROM Authentication WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username already taken'];
        }
        
        // Insert into Guest table
        $stmt = $conn->prepare("INSERT INTO Guest (first_name, last_name, email, phone, address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$firstName, $lastName, $email, $phone, $address]);
        $guestId = $conn->lastInsertId();
        
        // Insert into Authentication table
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO Authentication (guest_id, username, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$guestId, $username, $passwordHash]);
        
        $conn->commit();
        return ['success' => true, 'message' => 'Registration successful! Please login to continue.'];
        
    } catch (Exception $e) {
        $conn->rollBack();
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

// Login user
function loginUser($username, $password) {
    $conn = getDBConnection();
    
    try {
        // Get user authentication data
        $stmt = $conn->prepare("
            SELECT a.guest_id, a.password_hash, a.status, g.first_name, g.email 
            FROM Authentication a 
            JOIN Guest g ON a.guest_id = g.guest_id 
            WHERE a.username = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account is ' . $user['status']];
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        // Update last login
        $stmt = $conn->prepare("UPDATE Authentication SET last_login = CURRENT_TIMESTAMP WHERE guest_id = ?");
        $stmt->execute([$user['guest_id']]);
        
        // Set session
        $_SESSION['guest_id'] = $user['guest_id'];
        $_SESSION['username'] = $username;
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['email'] = $user['email'];
        
        return ['success' => true, 'message' => 'Login successful! Redirecting...'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
    }
}

// Logout user
function logoutUser() {
    // Clear only user-specific session data
    unset($_SESSION['guest_id']);
    unset($_SESSION['username']);
    unset($_SESSION['first_name']);
    unset($_SESSION['email']);
    
    // Keep the session alive but remove user data
    session_regenerate_id(true);
    
    return [
        'success' => true, 
        'message' => 'Logged out successfully',
        'redirect' => false
    ];
}

// Logout admin function
function logoutAdmin() {
    // Clear admin-specific session data
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_role']);
    
    // Completely destroy session for admin logout
    session_destroy();
    
    return [
        'success' => true, 
        'message' => 'Admin logged out successfully',
        'redirect' => true,
        'redirect_url' => '/admin/login.html'
    ];
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT g.*, a.username, a.last_login, a.status
        FROM Guest g
        JOIN Authentication a ON g.guest_id = a.guest_id
        WHERE g.guest_id = ?
    ");
    $stmt->execute([$_SESSION['guest_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
