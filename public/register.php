<?php
/**
 * Registration Page - Teddy Shine Laundry Management System
 * 
 * Allows new residents to create an account
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . '/' . strtolower(getUserRole()) . '/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs
    $f_name = sanitize($_POST['f_name']);
    $l_name = sanitize($_POST['l_name']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);
    $city = sanitize($_POST['city']);
    $street = sanitize($_POST['street']);
    $area = sanitize($_POST['area']);
    $house_no = sanitize($_POST['house_no']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $terms = isset($_POST['terms']) ? true : false;
    
    // Validation
    if (empty($f_name) || empty($l_name) || empty($email) || empty($password)) {
        $error = "Please fill all required fields marked with *";
    } elseif (!validateEmail($email)) {
        $error = "Please enter a valid email address";
    } elseif (!empty($phone) && !validatePhone($phone)) {
        $error = "Please enter a valid phone number (03xxxxxxxxx format)";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif ($password != $confirm_password) {
        $error = "Passwords do not match";
    } elseif (!$terms) {
        $error = "You must agree to the Terms & Conditions";
    } else {
        // Check if email exists
        $stmt = mysqli_prepare($conn, "SELECT Email FROM SignUp WHERE Email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error = "Email already registered. Please login or use a different email.";
        } else {
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Insert Resident
                $stmt = mysqli_prepare($conn, "INSERT INTO Resident (F_Name, L_Name, Phone_No, Email, City, Street, Area, House_No) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssssssss", $f_name, $l_name, $phone, $email, $city, $street, $area, $house_no);
                mysqli_stmt_execute($stmt);
                $resident_id = mysqli_insert_id($conn);
                
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert SignUp
                $stmt = mysqli_prepare($conn, "INSERT INTO SignUp (Resident_ID, Email, Password, Role) VALUES (?, ?, ?, 'resident')");
                mysqli_stmt_bind_param($stmt, "iss", $resident_id, $email, $hashed_password);
                mysqli_stmt_execute($stmt);
                
                // Insert Login
                $stmt = mysqli_prepare($conn, "INSERT INTO Login (Resident_ID, Email, Password, Role, Is_Active) VALUES (?, ?, ?, 'resident', 1)");
                mysqli_stmt_bind_param($stmt, "iss", $resident_id, $email, $hashed_password);
                mysqli_stmt_execute($stmt);
                
                mysqli_commit($conn);
                
                setFlashMessage("Registration successful! Please login to continue.", "success");
                redirect(BASE_URL . "/public/login.php");
                
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = "Registration failed: " . $e->getMessage();
                error_log("Registration error: " . $e->getMessage());
            }
        }
        mysqli_stmt_close($stmt);
    }
}

$custom_title = "Create Account - Teddy Shine";
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
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header text-center">
                    <h3><i class="fas fa-user-plus"></i> Create Account</h3>
                    <p class="mb-0">Join Teddy Shine for premium laundry service</p>
                </div>
                <div class="card-body">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="registerForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="f_name" class="form-control" value="<?php echo htmlspecialchars($_POST['f_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="l_name" class="form-control" value="<?php echo htmlspecialchars($_POST['l_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" placeholder="03xxxxxxxxx">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <input type="text" name="house_no" class="form-control" placeholder="House #">
                                </div>
                                <div class="col-md-5">
                                    <input type="text" name="street" class="form-control" placeholder="Street">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="area" class="form-control" placeholder="Area">
                                </div>
                                <div class="col-md-12 mt-2">
                                    <input type="text" name="city" class="form-control" placeholder="City">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" id="password" class="form-control" required>
                                <div id="passwordStrength" class="password-strength mt-1"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required>
                                <div id="passwordMatch" class="small mt-1"></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="terms" id="terms" class="form-check-input" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the Terms & Conditions and Privacy Policy
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-user-plus"></i> Create Account
                        </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>

<script>
// Password strength checker
const password = document.getElementById('password');
const confirmPassword = document.getElementById('confirmPassword');
const strengthDiv = document.getElementById('passwordStrength');
const matchDiv = document.getElementById('passwordMatch');

function checkStrength(val) {
    let strength = 0;
    if (val.length >= 6) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[!@#$%^&*]/.test(val)) strength++;
    
    const colors = ['', 'danger', 'warning', 'info', 'success'];
    const texts = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    
    if (val.length > 0) {
        strengthDiv.innerHTML = `<div class="progress" style="height: 4px;">
                                    <div class="progress-bar bg-${colors[strength]}" style="width: ${strength * 25}%"></div>
                                  </div>
                                  <small class="text-muted">Password strength: ${texts[strength]}</small>`;
    } else {
        strengthDiv.innerHTML = '';
    }
}

function checkMatch() {
    if (confirmPassword.value === '') {
        matchDiv.innerHTML = '';
        return;
    }
    if (password.value === confirmPassword.value) {
        matchDiv.innerHTML = '<i class="fas fa-check-circle text-success"></i> Passwords match';
    } else {
        matchDiv.innerHTML = '<i class="fas fa-times-circle text-danger"></i> Passwords do not match';
    }
}

password.addEventListener('input', () => { checkStrength(password.value); checkMatch(); });
confirmPassword.addEventListener('input', checkMatch);

// Form validation
document.getElementById('registerForm').addEventListener('submit', function(e) {
    if (password.value !== confirmPassword.value) {
        e.preventDefault();
        alert('Passwords do not match!');
    }
});
</script>

</body>
</html>