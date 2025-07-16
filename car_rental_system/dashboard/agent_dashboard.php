<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'agent') {
    header("Location: ../login.php");
    exit();
}
require_once '../includes/db.php';

// Fetch recent bookings for agent overview (all statuses)
$sql = "SELECT b.*, c.car_name, c.brand, u.full_name AS customer_name 
        FROM bookings b
        JOIN cars c ON b.car_id = c.id
        JOIN users u ON b.user_id = u.id
        ORDER BY b.booking_date DESC
        LIMIT 10";

$result = $conn->query($sql);

// Fetch car fleet overview: total, available, rented
$sql_cars = "SELECT
    (SELECT COUNT(*) FROM cars) AS total_cars,
    (SELECT COUNT(*) FROM cars WHERE availability = 'available') AS available_cars,
    (SELECT COUNT(*) FROM cars WHERE availability = 'rented') AS rented_cars";

$fleet_stats = $conn->query($sql_cars)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Agent Dashboard - Famuso Rentals</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

<header class="bg-white shadow p-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold text-orange-600">Agent Dashboard</h1>
    <nav class="space-x-6 flex items-center">
        <a href="agent_dashboard.php" class="text-orange-600 hover:text-orange-800"><i class="fas fa-home"></i> Home</a>
        <a href="../bookings/manage_bookings.php" class="text-gray-700 hover:text-orange-600"><i class="fas fa-book"></i> Manage Bookings</a>
        <a href="../cars/manage_cars.php" class="text-gray-700 hover:text-orange-600"><i class="fas fa-car"></i> Manage Cars</a>
        <a href="../users/manage_users.php" class="text-gray-700 hover:text-orange-600"><i class="fas fa-users"></i> Manage Users</a>
        <a href="../logout.php" class="text-red-500 hover:text-red-700"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</header>

<main class="flex-grow max-w-7xl mx-auto p-6 space-y-8">

    <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded shadow flex flex-col items-center">
            <i class="fas fa-car text-4xl text-orange-600 mb-4"></i>
            <h3 class="text-lg font-semibold">Total Cars</h3>
            <p class="text-2xl font-bold"><?= $fleet_stats['total_cars'] ?></p>
        </div>
        <div class="bg-white p-6 rounded shadow flex flex-col items-center">
            <i class="fas fa-check-circle text-4xl text-green-600 mb-4"></i>
            <h3 class="text-lg font-semibold">Available Cars</h3>
            <p class="text-2xl font-bold"><?= $fleet_stats['available_cars'] ?></p>
        </div>
        <div class="bg-white p-6 rounded shadow flex flex-col items-center">
            <i class="fas fa-car-side text-4xl text-red-600 mb-4"></i>
            <h3 class="text-lg font-semibold">Rented Cars</h3>
            <p class="text-2xl font-bold"><?= $fleet_stats['rented_cars'] ?></p>
        </div>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Recent Bookings</h2>
        <?php if ($result->num_rows > 0): ?>
            <div class="overflow-x-auto rounded shadow bg-white">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-orange-100">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Booking ID</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Customer</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Car</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Dates</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Total Cost</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="px-4 py-2">#<?= $row['id'] ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($row['brand'] . ' ' . $row['car_name']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($row['booking_date']) ?> - <?= htmlspecialchars($row['return_date']) ?></td>
                                <td class="px-4 py-2">ZMW <?= number_format($row['total_cost'], 2) ?></td>
                                <td class="px-4 py-2">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold
                                        <?= match ($row['status']) {
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'approved' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800',
                                            'completed' => 'bg-blue-100 text-blue-800',
                                            default => 'bg-gray-100 text-gray-800',
                                        } ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-600">No recent bookings found.</p>
        <?php endif; ?>
    </section>

</main>

<footer class="bg-white shadow p-4 text-center text-gray-500 text-sm">
    &copy; <?= date("Y") ?> Famuso Rentals. All rights reserved.
</footer>

</body>
</html>
