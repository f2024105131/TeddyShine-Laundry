<?php
/**
 * resident-Services - Teddy Shine Laundry Management System
 * 
 * Display all available laundry services with prices and details
 */

require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../config/functions.php';

// Get all active services
$services_query = "SELECT * FROM Services WHERE Status = 'Active' ORDER BY Service_Price";
$services = mysqli_query($conn, $services_query);

$custom_title = "Our Services - Teddy Shine";
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="text-center mb-5">
        <h2><i class="fas fa-tags"></i> Our Laundry Services</h2>
        <p class="text-muted">Professional care for all your laundry needs</p>
    </div>
    
    <div class="row g-4">
        <?php while($service = mysqli_fetch_assoc($services)): ?>
        <div class="col-md-6 col-lg-4">
            <div class="service-card">
                <div class="service-image">
                    <?php
                    $icons = [
                        'Wash' => 'fa-tshirt',
                        'Dry Cleaning' => 'fa-dryer',
                        'Ironing' => 'fa-iron',
                        'Stain' => 'fa-spray-can',
                        'Bulk' => 'fa-boxes'
                    ];
                    $icon = 'fa-tshirt';
                    foreach($icons as $key => $ic) {
                        if(strpos($service['Service_Name'], $key) !== false) {
                            $icon = $ic;
                            break;
                        }
                    }
                    ?>
                    <i class="fas <?php echo $icon; ?>"></i>
                </div>
                <div class="service-body">
                    <h4><?php echo htmlspecialchars($service['Service_Name']); ?></h4>
                    <div class="service-price">
                        Rs. <?php echo number_format($service['Service_Price'], 2); ?>
                        <small>/per item</small>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-clock"></i> Est. <?php echo $service['Estimate_Time']; ?> mins
                        </span>
                    </div>
                    <a href="place_order.php?service=<?php echo $service['Service_ID']; ?>" class="btn btn-primary w-100">
                        <i class="fas fa-shopping-cart"></i> Select Service
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    
    <!-- Why Choose Us -->
    <div class="row mt-5 g-4">
        <div class="col-12">
            <h3 class="text-center mb-4">Why Choose Teddy Shine?</h3>
        </div>
        <div class="col-md-3 text-center">
            <i class="fas fa-truck fa-3x text-primary mb-3"></i>
            <h6>Free Pickup & Delivery</h6>
            <p class="small text-muted">We come to your doorstep</p>
        </div>
        <div class="col-md-3 text-center">
            <i class="fas fa-leaf fa-3x text-primary mb-3"></i>
            <h6>Eco-Friendly</h6>
            <p class="small text-muted">Safe for you and environment</p>
        </div>
        <div class="col-md-3 text-center">
            <i class="fas fa-clock fa-3x text-primary mb-3"></i>
            <h6>24/7 Support</h6>
            <p class="small text-muted">Always here to help</p>
        </div>
        <div class="col-md-3 text-center">
            <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
            <h6>Quality Guarantee</h6>
            <p class="small text-muted">100% satisfaction assured</p>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>