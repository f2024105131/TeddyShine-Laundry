<?php
/**
 * Landing Page - Teddy Shine Laundry Management System
 * 
 * Main entry point for visitors
 */

require_once '../config/database.php';
require_once '../config/session.php';

// Get featured services
$services_query = "SELECT * FROM Services WHERE Status = 'Active' LIMIT 6";
$services_result = mysqli_query($conn, $services_query);

// Get statistics
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM Orders"))['count'];
$happy_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT Resident_ID) as count FROM Orders"))['count'];
$total_staff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM Staff"))['count'];

$custom_title = "Premium Laundry Service - Teddy Shine";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Teddy Shine - Professional laundry and dry cleaning services. Free pickup & delivery, quality guarantee, affordable prices.">
    <title>Teddy Shine - Premium Laundry & Dry Cleaning Service</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-tshirt"></i> Teddy Shine
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
            </ul>
            <div class="ms-lg-3 mt-3 mt-lg-0">
                <a href="login.php" class="btn btn-outline-primary me-2">Login</a>
                <a href="register.php" class="btn btn-primary">Sign Up</a>
            </div>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero" id="home">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <span class="badge bg-primary bg-opacity-10 text-primary mb-3 py-2 px-3 rounded-pill">
                    <i class="fas fa-star"></i> Premium Laundry Service
                </span>
                <h1>Fresh & Clean<br>Laundry Delivered</h1>
                <p class="lead">Professional laundry and dry cleaning services with free pickup & delivery. Quality guaranteed, affordable prices.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="register.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-calendar-alt"></i> Book Now
                    </a>
                    <a href="#services" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-info-circle"></i> Learn More
                    </a>
                </div>
                <div class="mt-4">
                    <div class="d-flex flex-wrap gap-4">
                        <div><i class="fas fa-check-circle text-success"></i> Free Pickup</div>
                        <div><i class="fas fa-check-circle text-success"></i> Quality Guarantee</div>
                        <div><i class="fas fa-check-circle text-success"></i> 24/7 Support</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mt-5 mt-lg-0 text-center">
                <i class="fas fa-tshirt fa-10x text-primary opacity-25"></i>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<div class="container">
    <div class="stats-section">
        <div class="row">
            <div class="col-md-3 col-6 mb-4 mb-md-0">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($happy_customers); ?>+</div>
                    <div class="stat-label">Happy Customers</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4 mb-md-0">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($total_orders); ?>+</div>
                    <div class="stat-label">Orders Completed</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($total_staff); ?>+</div>
                    <div class="stat-label">Professional Staff</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number">5+</div>
                    <div class="stat-label">Years Experience</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Services Section -->
<section class="container py-5" id="services">
    <div class="section-title">
        <h2>Our Premium Services</h2>
        <p>Choose from our wide range of professional laundry services</p>
    </div>
    <div class="row g-4">
        <?php while($service = mysqli_fetch_assoc($services_result)): ?>
        <div class="col-lg-4 col-md-6">
            <div class="service-card">
                <div class="service-image">
                    <i class="fas fa-shirt"></i>
                </div>
                <div class="service-body">
                    <h4><?php echo htmlspecialchars($service['Service_Name']); ?></h4>
                    <p class="text-muted">Professional cleaning with care</p>
                    <div class="service-price">Rs. <?php echo number_format($service['Service_Price'], 2); ?></div>
                    <p class="small text-muted">
                        <i class="fas fa-clock"></i> Est. <?php echo $service['Estimate_Time']; ?> mins
                    </p>
                    <a href="register.php" class="btn btn-primary w-100 mt-3">Select Service</a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</section>

<!-- How It Works -->
<section class="how-it-works py-5" id="how-it-works">
    <div class="container">
        <div class="section-title">
            <h2>How It Works</h2>
            <p>Simple 4-step process to get your laundry done</p>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h5>Register Account</h5>
                    <p class="text-muted">Create your free account in minutes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h5>Place Order</h5>
                    <p class="text-muted">Select services and schedule pickup</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h5>We Clean</h5>
                    <p class="text-muted">Professional cleaning with care</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="step-card">
                    <div class="step-number">4</div>
                    <h5>Free Delivery</h5>
                    <p class="text-muted">Fresh laundry delivered to your door</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5" id="contact">
    <div class="container">
        <h2>Ready for Fresh & Clean Laundry?</h2>
        <p class="mb-4">Join thousands of happy customers who trust Teddy Shine</p>
        <a href="register.php" class="btn btn-light btn-lg rounded-pill px-5">
            <i class="fas fa-user-plus"></i> Sign Up Now
        </a>
    </div>
</section>

<!-- Footer -->
<footer>
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h4 class="mb-3"><i class="fas fa-tshirt"></i> Teddy Shine</h4>
                <p>Professional laundry and dry cleaning services with quality guarantee and free pickup & delivery.</p>
                <div class="mt-3">
                    <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="#home" class="text-white-50 text-decoration-none">Home</a></li>
                    <li><a href="#services" class="text-white-50 text-decoration-none">Services</a></li>
                    <li><a href="#how-it-works" class="text-white-50 text-decoration-none">How It Works</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <h5>Contact Info</h5>
                <ul class="list-unstyled">
                    <li><i class="fas fa-map-marker-alt me-2"></i> 123 Main Street, Lahore</li>
                    <li><i class="fas fa-phone me-2"></i> +92 300 1234567</li>
                    <li><i class="fas fa-envelope me-2"></i> info@teddyshine.com</li>
                    <li><i class="fas fa-clock me-2"></i> Mon-Sat: 9AM - 8PM</li>
                </ul>
            </div>
            <div class="col-lg-3 mb-4">
                <h5>Newsletter</h5>
                <p class="text-white-50">Subscribe for offers & updates</p>
                <div class="input-group">
                    <input type="email" class="form-control" placeholder="Your email">
                    <button class="btn btn-primary" type="button">Subscribe</button>
                </div>
            </div>
        </div>
        <hr class="bg-light opacity-25">
        <div class="text-center">
            <p class="mb-0 text-white-50">&copy; <?php echo date('Y'); ?> Teddy Shine. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>

<script>
// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// Counter animation for stats
const counters = document.querySelectorAll('.stat-number');
counters.forEach(counter => {
    const target = parseInt(counter.innerText);
    let count = 0;
    const increment = Math.ceil(target / 50);
    const updateCount = () => {
        count += increment;
        if (count < target) {
            counter.innerText = count + '+';
            setTimeout(updateCount, 30);
        } else {
            counter.innerText = target + '+';
        }
    };
    updateCount();
});
</script>

</body>
</html>