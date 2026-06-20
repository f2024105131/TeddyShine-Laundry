        <!-- Page Content Ends Here -->
    </div>
</main>

<!-- Footer -->
<footer>
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="mb-3">
                    <i class="fas fa-tshirt fa-2x mb-2"></i>
                    <h4 class="mb-0">Teddy Shine</h4>
                    <p class="text-white-50">Premium Laundry Service</p>
                </div>
                <p class="text-white-50 small">Your trusted partner for professional laundry and dry cleaning services. Quality care for your clothes.</p>
                <div class="mt-3">
                    <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            
            <div class="col-md-2">
                <h5 class="mb-3">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>/public/index.php" class="text-white-50 text-decoration-none">Home</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>/public/index.php#services" class="text-white-50 text-decoration-none">Services</a></li>
                    <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">About Us</a></li>
                    <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Contact</a></li>
                </ul>
            </div>
            
            <div class="col-md-3">
                <h5 class="mb-3">Our Services</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Wash & Fold</a></li>
                    <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Dry Cleaning</a></li>
                    <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Ironing Only</a></li>
                    <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Wash & Iron</a></li>
                </ul>
            </div>
            
            <div class="col-md-3">
                <h5 class="mb-3">Contact Info</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> <span class="text-white-50">123 Main Street, Lahore</span></li>
                    <li class="mb-2"><i class="fas fa-phone me-2"></i> <span class="text-white-50">+92 300 1234567</span></li>
                    <li class="mb-2"><i class="fas fa-envelope me-2"></i> <span class="text-white-50">info@teddyshine.com</span></li>
                    <li class="mb-2"><i class="fas fa-clock me-2"></i> <span class="text-white-50">Mon-Sat: 9AM - 8PM</span></li>
                </ul>
            </div>
        </div>
        
        <hr class="my-4 bg-light opacity-25">
        
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0 small text-white-50">&copy; <?php echo date('Y'); ?> Teddy Shine Laundry Management System. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="mb-0 small text-white-50">
                    <a href="#" class="text-white-50 text-decoration-none">Privacy Policy</a> | 
                    <a href="#" class="text-white-50 text-decoration-none">Terms of Service</a>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery (optional for some features) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Custom JavaScript -->
<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>

<script>
// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>

</body>
</html>