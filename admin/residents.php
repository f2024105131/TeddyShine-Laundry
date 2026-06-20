<?php
/**
 * Manage Residents - Teddy Shine Laundry Management System
 * 
 * View, search, filter, and manage all registered residents
 */

require_once '../includes/admin_check.php';
require_once '../config/database.php';
require_once '../config/functions.php';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$where = "1=1";
if(!empty($search)) {
    $where .= " AND (r.F_Name LIKE '%$search%' OR r.L_Name LIKE '%$search%' OR r.Email LIKE '%$search%' OR r.Phone_No LIKE '%$search%')";
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM Resident r WHERE $where";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

// Get residents
$residents_query = "SELECT r.*, s.Role, s.Created_At as registered_at,
                    (SELECT COUNT(*) FROM Orders WHERE Resident_ID = r.Resident_ID) as total_orders,
                    (SELECT SUM(Amount) FROM Orders WHERE Resident_ID = r.Resident_ID) as total_spent
                    FROM Resident r
                    JOIN SignUp s ON r.Resident_ID = s.Resident_ID
                    WHERE $where
                    ORDER BY r.Created_At DESC
                    LIMIT $offset, $limit";
$residents = mysqli_query($conn, $residents_query);

// Handle delete
if(isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $check = mysqli_query($conn, "SELECT * FROM Orders WHERE Resident_ID = $id");
    if(mysqli_num_rows($check) > 0) {
        setFlashMessage("Cannot delete resident with existing orders. Archive instead.", "error");
    } else {
        mysqli_query($conn, "DELETE FROM SignUp WHERE Resident_ID = $id");
        mysqli_query($conn, "DELETE FROM Resident WHERE Resident_ID = $id");
        setFlashMessage("Resident deleted successfully.", "success");
    }
    redirect(BASE_URL . "/admin/residents.php");
}

$custom_title = "Manage Residents - Teddy Shine";
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users"></i> Manage Residents</h2>
        <div>
            <span class="badge bg-primary">Total: <?php echo $total_rows; ?> residents</span>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section mb-4">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-8">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, email, or phone..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                    <?php if(!empty($search)): ?>
                    <a href="residents.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Residents Grid -->
    <?php if(mysqli_num_rows($residents) > 0): ?>
        <div class="row">
            <?php while($resident = mysqli_fetch_assoc($residents)): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="resident-card">
                    <div class="d-flex align-items-start mb-3">
                        <div class="resident-avatar">
                            <?php echo strtoupper(substr($resident['F_Name'], 0, 1)) . strtoupper(substr($resident['L_Name'], 0, 1)); ?>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <h6 class="mb-0"><?php echo htmlspecialchars($resident['F_Name'] . ' ' . $resident['L_Name']); ?></h6>
                            <div class="small text-muted">
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($resident['Email']); ?>
                            </div>
                            <div class="small text-muted">
                                <i class="fas fa-phone"></i> <?php echo $resident['Phone_No'] ?? 'Not provided'; ?>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-link" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item text-danger" href="?delete=<?php echo $resident['Resident_ID']; ?>" onclick="return confirm('Delete this resident?')"><i class="fas fa-trash"></i> Delete</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="row text-center pt-2 border-top">
                        <div class="col-6">
                            <div class="small text-muted">Orders</div>
                            <strong><?php echo $resident['total_orders']; ?></strong>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Total Spent</div>
                            <strong>Rs. <?php echo number_format($resident['total_spent'] ?? 0, 2); ?></strong>
                        </div>
                    </div>
                    
                    <div class="mt-2">
                        <small class="text-muted"><i class="fas fa-calendar"></i> Joined: <?php echo date('d M Y', strtotime($resident['registered_at'])); ?></small>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if($page > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">Previous</a></li>
                <?php endif; ?>
                <?php for($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>
                <?php if($page < $total_pages): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">Next</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-users fa-4x text-muted mb-3"></i>
                <h4>No Residents Found</h4>
                <p class="text-muted">No residents match your search criteria.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>