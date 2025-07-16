<?php
session_start();
require_once 'includes/db.php';

// Fetch available cars
$cars = $conn->query("SELECT * FROM cars WHERE availability='available' LIMIT 6");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Famuso Car Rentals - Ndola</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-800">

  <!-- Navigation -->
  <nav class="bg-white shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center py-4">
      <h1 class="text-2xl font-bold text-orange-600">Famuso Car Rentals</h1>
      <ul class="flex space-x-6 text-lg font-medium">
        <li><a href="index.php" class="hover:text-orange-500"><i class="ti ti-home"></i> Home</a></li>
        <li><a href="bookings/create_booking.php" class="hover:text-orange-500"><i class="ti ti-car"></i> Book</a></li>
        <li><a href="register.php" class="hover:text-orange-500"><i class="ti ti-user-plus"></i> Register</a></li>
        <li><a href="login.php" class="hover:text-orange-500"><i class="ti ti-login"></i> Login</a></li>
      </ul>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="bg-orange-100 py-16">
    <div class="max-w-6xl mx-auto px-6 text-center">
      <h2 class="text-4xl md:text-5xl font-extrabold mb-4 text-gray-800">Your Journey Begins with Famuso</h2>
      <p class="text-lg md:text-xl max-w-3xl mx-auto text-gray-700">
        Famuso Car Rentals is a trusted Zambian-based car rental service located in Ndola. We provide top-quality vehicles to meet the needs of individuals and businesses across the Copperbelt. Book with confidence and enjoy a safe, smooth ride today!
      </p>
      <div class="mt-6">
        <a href="register.php" class="inline-block bg-orange-600 text-white py-3 px-6 rounded-full shadow hover:bg-orange-700 transition">Get Started</a>
      </div>
    </div>
  </section>

  <!-- Vehicles Section -->
  <section class="py-14 bg-white">
    <div class="max-w-7xl mx-auto px-6">
      <h3 class="text-3xl font-bold text-gray-800 mb-10 text-center"><i class="ti ti-steering-wheel text-orange-600"></i> Available Vehicles</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
        <?php while ($car = $cars->fetch_assoc()): ?>
          <?php
            $imageFile = "assets/images/" . $car['image'];
            $defaultImage = "assets/images/default-car.jpg";
            $serverImagePath = __DIR__ . '/' . $imageFile;

            if (!file_exists($serverImagePath) || empty($car['image'])) {
                $imageFile = $defaultImage;
            }
          ?>
          <div class="bg-white rounded-2xl shadow hover:shadow-xl transition p-4 flex flex-col">
            <img src="<?php echo $imageFile; ?>" alt="<?php echo htmlspecialchars($car['car_name']); ?>" class="w-full h-48 object-cover rounded-xl mb-4">
            <h4 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($car['car_name']); ?></h4>
            <p class="text-sm text-gray-600">
              Brand: <?php echo htmlspecialchars($car['brand']); ?> |
              Model: <?php echo htmlspecialchars($car['model']); ?> |
              Year: <?php echo htmlspecialchars($car['year']); ?>
            </p>
            <p class="text-lg font-bold text-orange-600 mt-2">ZMW <?php echo number_format($car['price_per_day'], 2); ?> / day</p>
            <div class="mt-4 flex justify-between items-center">
              <a href="cars/view_car.php?id=<?php echo $car['id']; ?>" class="text-sm bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded">View More</a>
              <?php if (isset($_SESSION['id'])): ?>
                <a href="bookings/create_booking.php?car_id=<?php echo $car['id']; ?>" class="text-sm bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded">Book Now</a>
              <?php else: ?>
                <button disabled class="text-sm bg-gray-400 text-white px-4 py-2 rounded cursor-not-allowed" title="Login required">Login to Book</button>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-gray-900 text-white py-8 mt-16">
    <div class="max-w-6xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-6">
      <div>
        <h4 class="text-lg font-semibold mb-2">Famuso Car Rentals</h4>
        <p class="text-sm">Your trusted rental partner in Ndola and beyond. Safe. Reliable. Affordable.</p>
      </div>
      <div>
        <h4 class="text-lg font-semibold mb-2">Quick Links</h4>
        <ul class="text-sm space-y-1">
          <li><a href="index.php" class="hover:text-orange-500">Home</a></li>
          <li><a href="bookings/create_booking.php" class="hover:text-orange-500">Book</a></li>
          <li><a href="register.php" class="hover:text-orange-500">Register</a></li>
          <li><a href="login.php" class="hover:text-orange-500">Login</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-lg font-semibold mb-2">Contact</h4>
        <p class="text-sm">📍 Ndola, Zambia</p>
        <p class="text-sm">📞 +260 97 000 0000</p>
        <p class="text-sm">📧 info@famuso.com</p>
      </div>
    </div>
    <div class="text-center text-xs mt-6 border-t border-gray-700 pt-4">© <?php echo date('Y'); ?> Famuso Car Rentals. All rights reserved.</div>
  </footer>

</body>
</html>
