<?php
require_once '../includes/db.php';

// Enhanced input validation and sanitization
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    header("Location: ../cars/index.php");
    exit();
}

// Fetch car details with better error handling
try {
    $stmt = $conn->prepare("SELECT * FROM cars WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $car = $result->fetch_assoc();

    if (!$car) {
        header("Location: ../cars/index.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Database error in view_car.php: " . $e->getMessage());
    header("Location: ../cars/index.php");
    exit();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['id']);

// Get additional car features (assuming they exist in database)
$features = [
    'Air Conditioning',
    'GPS Navigation',
    'Bluetooth',
    'Automatic Transmission',
    'Power Windows',
    'Safety Features'
];

// Calculate savings for longer rentals
$weeklyDiscount = 0.15; // 15% discount for weekly rentals
$monthlyDiscount = 0.25; // 25% discount for monthly rentals
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Rent <?= htmlspecialchars($car['car_name']); ?> from Famuso Rentals. Premium car rental service in Zambia.">
    <meta name="keywords" content="car rental, <?= htmlspecialchars($car['brand']); ?>, <?= htmlspecialchars($car['model']); ?>, Zambia, Famuso Rentals">
    <title><?= htmlspecialchars($car['car_name']); ?> - Premium Car Rental | Famuso Rentals</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#ea580c',
                        secondary: '#fb923c',
                        accent: '#fed7aa'
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-gradient {
            background: linear-gradient(135deg, #ea580c 0%, #fb923c 100%);
        }
        .car-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        .feature-card {
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .price-highlight {
            background: linear-gradient(45deg, #ea580c, #fb923c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Navigation Breadcrumb -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center space-x-2 py-4 text-sm text-gray-600">
                <a href="../index.php" class="hover:text-primary transition-colors">Home</a>
                <i class="fas fa-chevron-right text-gray-400"></i>
                <a href="../cars/index.php" class="hover:text-primary transition-colors">Cars</a>
                <i class="fas fa-chevron-right text-gray-400"></i>
                <span class="text-gray-900 font-medium"><?= htmlspecialchars($car['car_name']); ?></span>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Car Details Section -->
            <div class="lg:col-span-2">
                <!-- Car Image Gallery -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-8">
                    <div class="relative">
                        <img src="../assets/images/<?= htmlspecialchars($car['image'] ?: 'default-car.jpg'); ?>" 
                             class="w-full h-96 object-cover" 
                             alt="<?= htmlspecialchars($car['car_name']); ?>"
                             onerror="this.src='../assets/images/default-car.jpg'">
                        <div class="absolute top-4 left-4">
                            <span class="bg-primary text-white px-3 py-1 rounded-full text-sm font-medium">
                                Available
                            </span>
                        </div>
                        <div class="absolute top-4 right-4">
                            <button class="bg-white bg-opacity-90 hover:bg-opacity-100 rounded-full p-2 transition-all">
                                <i class="fas fa-heart text-gray-600 hover:text-red-500"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Car Title and Basic Info -->
                    <div class="p-6">
                        <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($car['car_name']); ?></h1>
                        <div class="flex flex-wrap items-center gap-4 text-gray-600 mb-4">
                            <div class="flex items-center">
                                <i class="fas fa-car text-primary mr-2"></i>
                                <span><?= htmlspecialchars($car['brand']); ?></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-cogs text-primary mr-2"></i>
                                <span><?= htmlspecialchars($car['model']); ?></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-calendar text-primary mr-2"></i>
                                <span><?= htmlspecialchars($car['year']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Car Features -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Features & Amenities</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php foreach ($features as $feature): ?>
                            <div class="feature-card bg-gray-50 rounded-lg p-4 text-center">
                                <i class="fas fa-check-circle text-primary text-xl mb-2"></i>
                                <p class="text-gray-700 font-medium"><?= htmlspecialchars($feature); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Car Specifications -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Specifications</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                                <span class="text-gray-600">Brand</span>
                                <span class="font-medium"><?= htmlspecialchars($car['brand']); ?></span>
                            </div>
                            <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                                <span class="text-gray-600">Model</span>
                                <span class="font-medium"><?= htmlspecialchars($car['model']); ?></span>
                            </div>
                            <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                                <span class="text-gray-600">Year</span>
                                <span class="font-medium"><?= htmlspecialchars($car['year']); ?></span>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                                <span class="text-gray-600">Fuel Type</span>
                                <span class="font-medium">Petrol</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                                <span class="text-gray-600">Transmission</span>
                                <span class="font-medium">Automatic</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                                <span class="text-gray-600">Seats</span>
                                <span class="font-medium">5 Passengers</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Sidebar -->
            <div class="lg:col-span-1">
                <div class="sticky top-8">
                    <!-- Pricing Card -->
                    <div class="car-card rounded-2xl shadow-lg p-6 mb-6">
                        <div class="text-center mb-6">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">Rental Pricing</h3>
                            <div class="price-highlight text-4xl font-bold mb-2">
                                ZMW <?= number_format($car['price_per_day'], 2); ?>
                            </div>
                            <p class="text-gray-600">per day</p>
                        </div>

                        <!-- Pricing Options -->
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                <span class="text-gray-600">Daily Rate</span>
                                <span class="font-bold">ZMW <?= number_format($car['price_per_day'], 2); ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                <span class="text-gray-600">Weekly Rate</span>
                                <div class="text-right">
                                    <span class="font-bold">ZMW <?= number_format($car['price_per_day'] * 7 * (1 - $weeklyDiscount), 2); ?></span>
                                    <p class="text-sm text-green-600">Save 15%</p>
                                </div>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                <span class="text-gray-600">Monthly Rate</span>
                                <div class="text-right">
                                    <span class="font-bold">ZMW <?= number_format($car['price_per_day'] * 30 * (1 - $monthlyDiscount), 2); ?></span>
                                    <p class="text-sm text-green-600">Save 25%</p>
                                </div>
                            </div>
                        </div>

                        <!-- Booking Button -->
                        <div class="space-y-3">
                            <?php if ($isLoggedIn): ?>
                                <a href="../bookings/create_booking.php?car_id=<?= $car['id']; ?>" 
                                   class="block w-full bg-primary hover:bg-orange-700 text-white text-center py-3 rounded-lg font-medium transition-colors transform hover:scale-105">
                                    <i class="fas fa-calendar-check mr-2"></i>
                                    Book This Car
                                </a>
                            <?php else: ?>
                                <a href="../login.php" 
                                   class="block w-full bg-gray-400 hover:bg-gray-500 text-white text-center py-3 rounded-lg font-medium transition-colors">
                                    <i class="fas fa-sign-in-alt mr-2"></i>
                                    Login to Book
                                </a>
                            <?php endif; ?>
                            
                            <button class="w-full bg-white border-2 border-primary text-primary hover:bg-primary hover:text-white py-3 rounded-lg font-medium transition-colors">
                                <i class="fas fa-phone mr-2"></i>
                                Call for Inquiry
                            </button>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Need Help?</h3>
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <i class="fas fa-phone text-primary mr-3"></i>
                                <div>
                                    <p class="font-medium">Phone</p>
                                    <p class="text-gray-600">+260 123 456 789</p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-envelope text-primary mr-3"></i>
                                <div>
                                    <p class="font-medium">Email</p>
                                    <p class="text-gray-600">info@famusorentals.com</p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-clock text-primary mr-3"></i>
                                <div>
                                    <p class="font-medium">Hours</p>
                                    <p class="text-gray-600">24/7 Support</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification (Hidden by default) -->
    <div id="toast" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300">
        <i class="fas fa-check-circle mr-2"></i>
        <span id="toast-message">Success!</span>
    </div>

    <script>
        // Enhanced user interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });

            // Add loading state to booking button
            const bookingButton = document.querySelector('a[href*="create_booking"]');
            if (bookingButton) {
                bookingButton.addEventListener('click', function() {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
                    this.classList.add('opacity-75', 'cursor-not-allowed');
                });
            }

            // Add favorite functionality (placeholder)
            const favoriteButton = document.querySelector('.fa-heart').parentElement;
            favoriteButton.addEventListener('click', function() {
                const icon = this.querySelector('i');
                icon.classList.toggle('text-red-500');
                icon.classList.toggle('text-gray-600');
                
                // Show toast notification
                showToast('Added to favorites!');
            });

            // Toast notification function
            function showToast(message) {
                const toast = document.getElementById('toast');
                const toastMessage = document.getElementById('toast-message');
                toastMessage.textContent = message;
                toast.classList.remove('translate-x-full');
                
                setTimeout(() => {
                    toast.classList.add('translate-x-full');
                }, 3000);
            }

            // Image error handling
            const carImage = document.querySelector('img[alt*="<?= htmlspecialchars($car['car_name']); ?>"]');
            if (carImage) {
                carImage.addEventListener('error', function() {
                    this.src = '../assets/images/default-car.jpg';
                });
            }
        });
    </script>
</body>
</html>