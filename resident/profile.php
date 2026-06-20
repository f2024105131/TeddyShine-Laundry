<?php
/**
 * -resident-Resident Profile - Teddy Shine Laundry Management System
 * 
 * Edit profile information and change password
 */

require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../config/functions.php';

$resident_id = $_SESSION['resident_id'];

// Get resident data
$query = "SELECT * FROM Resident WHERE Resident_ID = $resident_id";
$result = mysqli_query($conn, $query);
$resident = mysqli_fetch_assoc($result);

// Handle profile update
if(isset($_POST['update_profile'])) {
    $f_name = sanitize($_POST['f_name']);
    $l_name = sanitize($_POST['l_name']);
    $phone = sanitize($_POST['phone']);
    $city = sanitize($_POST['city']);
    $street = sanitize($_POST['street']);
    $area = sanitize($_POST['area']);
    $house_no = sanitize($_POST['house_no']);
    
    $update_query = "UPDATE Resident SET 
                     F_Name = '$f_name',
                     L_Name = '$l_name',
                     Phone_No = '$phone',
                     City = '$city',
                     Street = '$street',
                     Area = '$area',
                     House_No = '$house_no'
                     WHERE Resident_ID = $resident_id";
    
    if(mysqli_query($conn, $update_query)) {
        $_SESSION['user_name'] = "$f_name $l_name";
        setFlashMessage("Profile updated successfully!", "success");
        redirect(BASE_URL . "/resident/profile.php");
    } else {
        $error = "Update failed: " . mysqli_error($conn);
    }
}

// Handle password change
if(isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $pass_query = "SELECT Password FROM SignUp WHERE Resident_ID = $resident_id";
    $pass_result = mysqli_query($conn, $pass_query);
    $pass_row = mysqli_fetch_assoc($pass_result);
    
    if(password_verify($current_password, $pass_row['Password']) || $current_password == $pass_row['Password']) {
        if(strlen($new_password) < 6) {
            $pass_error = "New password must be at least 6 characters";
        } elseif($new_password != $confirm_password) {
            $pass_error = "New passwords do not match";
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update_pass = "UPDATE SignUp SET Password = '$hashed' WHERE Resident_ID = $resident_id";
            mysqli_query($conn, $update_pass);
            
            $update_login = "UPDATE Login SET Password = '$hashed' WHERE Resident_ID = $resident_id";
            mysqli_query($conn, $update_login);
            
            setFlashMessage("Password changed successfully!", "success");
            redirect(BASE_URL . "/resident/profile.php");
        }
    } else {
        $pass_error = "Current password is incorrect";
    }
}

$custom_title = "My Profile - Teddy Shine";
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="profile-sidebar">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle fa-4x"></i>
                </div>
                <h5><?php echo htmlspecialchars($resident['F_Name'] . ' ' . $resident['L_Name']); ?></h5>
                <p class="small mb-0"><?php echo htmlspecialchars($resident['Email']); ?></p>
                <p class="small text-muted">Member since <?php echo date('M Y', strtotime($resident['Created_At'])); ?></p>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#profileInfo">Profile Information</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#changePassword">Change Password</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- Profile Information Tab -->
                        <div class="tab-pane fade show active" id="profileInfo">
                            <?php if(isset($error)): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name</label>
                                        <input type="text" name="f_name" class="form-control" value="<?php echo htmlspecialchars($resident['F_Name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" name="l_name" class="form-control" value="<?php echo htmlspecialchars($resident['L_Name']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($resident['Phone_No']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($resident['Email']); ?>" disabled>
                                    <small class="text-muted">Email cannot be changed</small>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">City</label>
                                        <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($resident['City']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Area</label>
                                        <input type="text" name="area" class="form-control" value="<?php echo htmlspecialchars($resident['Area']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">House No</label>
                                        <input type="text" name="house_no" class="form-control" value="<?php echo htmlspecialchars($resident['House_No']); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Street</label>
                                    <input type="text" name="street" class="form-control" value="<?php echo htmlspecialchars($resident['Street']); ?>">
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Change Password Tab -->
                        <div class="tab-pane fade" id="changePassword">
                            <?php if(isset($pass_error)): ?>
                                <div class="alert alert-danger"><?php echo $pass_error; ?></div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" id="new_password" class="form-control" required>
                                    <div id="passwordStrength" class="password-strength mt-1"></div>
                                    <small class="text-muted">Minimum 6 characters with letters and numbers</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                    <div id="matchMessage" class="small mt-1"></div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" name="change_password" class="btn btn-warning">
                                        <i class="fas fa-lock"></i> Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password strength meter
const newPassword = document.getElementById('new_password');
const confirmPassword = document.getElementById('confirm_password');
const strengthDiv = document.getElementById('passwordStrength');
const matchDiv = document.getElementById('matchMessage');

function checkStrength(password) {
    let strength = 0;
    if(password.length >= 6) strength++;
    if(/[A-Z]/.test(password)) strength++;
    if(/[0-9]/.test(password)) strength++;
    if(/[!@#$%^&*]/.test(password)) strength++;
    
    const colors = ['', '#dc3545', '#fd7e14', '#ffc107', '#198754'];
    const texts = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    
    if(password.length > 0) {
        strengthDiv.innerHTML = `<div class="progress" style="height: 4px;">
                                    <div class="progress-bar bg-${strength >= 4 ? 'success' : strength >= 3 ? 'warning' : 'danger'}" style="width: ${strength * 25}%"></div>
                                  </div>
                                  <small class="text-muted">Password strength: ${texts[strength]}</small>`;
    } else {
        strengthDiv.innerHTML = '';
    }
    return strength;
}

function checkMatch() {
    if(confirmPassword.value === '') {
        matchDiv.innerHTML = '';
        return;
    }
    if(newPassword.value === confirmPassword.value) {
        matchDiv.innerHTML = '<i class="fas fa-check-circle text-success"></i> Passwords match';
    } else {
        matchDiv.innerHTML = '<i class="fas fa-times-circle text-danger"></i> Passwords do not match';
    }
}

newPassword.addEventListener('input', () => checkStrength(newPassword.value));
confirmPassword.addEventListener('input', checkMatch);
</script>

<?php include_once '../includes/footer.php'; ?>