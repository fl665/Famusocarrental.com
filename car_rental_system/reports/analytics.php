<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['admin', 'agent'])) {
    header("Location: ../login.php");
    exit();
}

// Analytics Queries
$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$totalCars = $conn->query("SELECT COUNT(*) AS total FROM cars")->fetch_assoc()['total'];
$totalBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings")->fetch_assoc()['total'];
$completedBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status = 'completed'")->fetch_assoc()['total'];
$pendingBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status = 'pending'")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Analytics - Famuso Rentals</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
  <header class="bg-white shadow p-4 flex justify-between items-center">
    <h1 class="text-xl font-bold text-orange-600">Analytics</h1>
    <div class="flex space-x-4">
      <a href="../dashboard/admin_dashboard.php" class="text-orange-600 hover:text-orange-800"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="../logout.php" class="text-red-500 hover:text-red-700"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-4 py-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="bg-white p-6 shadow rounded-lg text-center">
      <h2 class="text-gray-700 text-lg font-semibold">Total Users</h2>
      <p class="text-3xl font-bold text-orange-600 mt-2"><?= $totalUsers ?></p>
    </div>
    <div class="bg-white p-6 shadow rounded-lg text-center">
      <h2 class="text-gray-700 text-lg font-semibold">Total Cars</h2>
      <p class="text-3xl font-bold text-orange-600 mt-2"><?= $totalCars ?></p>
    </div>
    <div class="bg-white p-6 shadow rounded-lg text-center">
      <h2 class="text-gray-700 text-lg font-semibold">Total Bookings</h2>
      <p class="text-3xl font-bold text-orange-600 mt-2"><?= $totalBookings ?></p>
    </div>
    <div class="bg-white p-6 shadow rounded-lg text-center">
      <h2 class="text-gray-700 text-lg font-semibold">Completed Bookings</h2>
      <p class="text-3xl font-bold text-green-600 mt-2"><?= $completedBookings ?></p>
    </div>
    <div class="bg-white p-6 shadow rounded-lg text-center">
      <h2 class="text-gray-700 text-lg font-semibold">Pending Bookings</h2>
      <p class="text-3xl font-bold text-yellow-600 mt-2"><?= $pendingBookings ?></p>
    </div>
  </main>
</body>
</html>
