<?php
/**
 * Login Page - Teddy Shine Laundry Management System
 * 
 * Authenticates users and redirects based on role
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . '/' . strtolower(getUserRole()) . '/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $role = sanitize($_POST['role']);
    $remember_me = isset($_POST['remember_me']) ? true : false;
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        // Prepare login query
        $stmt = mysqli_prepare($conn, "SELECT * FROM Login WHERE Email = ? AND Role = ? AND Is_Active = 1");
        mysqli_stmt_bind_param($stmt, "ss", $email, $role);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password
            $password_valid = false;
            if (password_verify($password, $user['Password'])) {
                $password_valid = true;
            } elseif ($password == $user['Password']) {
                // For demo data with plain text passwords
                $password_valid = true;
            }
            
            if ($password_valid) {
                // Set session variables
                $_SESSION['user_id'] = $user['Login_ID'];
                $_SESSION['resident_id'] = $user['Resident_ID'];
                $_SESSION['email'] = $user['Email'];
                $_SESSION['role'] = $user['Role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                
                // Get user name
                $stmt2 = mysqli_prepare($conn, "SELECT CONCAT(F_Name, ' ', L_Name) as name FROM Resident WHERE Resident_ID = ?");
                mysqli_stmt_bind_param($stmt2, "i", $user['Resident_ID']);
                mysqli_stmt_execute($stmt2);
                $result2 = mysqli_stmt_get_result($stmt2);
                $name_row = mysqli_fetch_assoc($result2);
                $_SESSION['user_name'] = $name_row['name'] ?? 'User';
                
                // Update last login
                $stmt3 = mysqli_prepare($conn, "UPDATE Login SET Last_Login = NOW() WHERE Login_ID = ?");
                mysqli_stmt_bind_param($stmt3, "i", $user['Login_ID']);
                mysqli_stmt_execute($stmt3);
                
                // Handle remember me
                if ($remember_me) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + 86400 * 30, '/');
                }
                
                // Clear redirect URL
                unset($_SESSION['redirect_after_login']);
                
                // Redirect based on role
                switch ($role) {
                    case 'admin':
                        redirect(BASE_URL . "/admin/dashboard.php");
                        break;
                    case 'staff':
                        redirect(BASE_URL . "/staff/dashboard.php");
                        break;
                    default:
                        redirect(BASE_URL . "/resident/dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid password. Please try again.";
            }
        } else {
            $error = "No account found with these credentials. Please check your email and role.";
        }
        mysqli_stmt_close($stmt);
    }
}

$custom_title = "Login - Teddy Shine";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $custom_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card">
                <div class="card-header text-center">
                    <h3><i class="fas fa-tshirt"></i> Teddy Shine</h3>
                    <p class="mb-0">Login to your account</p>
                </div>
                <div class="card-body">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <!-- Role Selection -->
                        <div class="mb-3">
                            <label class="form-label">Login As</label>
                            <select name="role" class="form-select">
                                <option value="resident">Resident (Customer)</option>
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>
                        
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input type="checkbox" name="remember_me" id="remember_me" class="form-check-input">
                                <label class="form-check-label" for="remember_me">Remember me</label>
                            </div>
                            <a href="#" class="text-decoration-none small">Forgot Password?</a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p class="mb-0">Don't have an account? <a href="register.php">Sign Up</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>

</body>
</html>