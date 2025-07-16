<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}
require_once '../includes/db.php';

// Fetch upcoming bookings for logged-in customer
$user_id = $_SESSION['id'];
$sql = "SELECT b.*, c.car_name, c.brand 
        FROM bookings b
        JOIN cars c ON b.car_id = c.id
        WHERE b.user_id = ? AND b.status IN ('pending', 'approved')
        ORDER BY b.booking_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();

// Fetch booking history (completed, cancelled)
$sql_hist = "SELECT b.*, c.car_name, c.brand 
             FROM bookings b
             JOIN cars c ON b.car_id = c.id
             WHERE b.user_id = ? AND b.status NOT IN ('pending', 'approved')
             ORDER BY b.booking_date DESC";

$stmt_hist = $conn->prepare($sql_hist);
$stmt_hist->bind_param("i", $user_id);
$stmt_hist->execute();
$history = $stmt_hist->get_result();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Customer Dashboard - Famuso Rentals</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

<header class="bg-white shadow p-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold text-orange-600">Welcome, <?= htmlspecialchars($_SESSION['name']) ?></h1>
    <nav class="space-x-6 flex items-center">
        <a href="customer_dashboard.php" class="text-orange-600 hover:text-orange-800"><i class="fas fa-home"></i> Home</a>
        <a href="../index.php" class="text-gray-700 hover:text-orange-600"><i class="fas fa-car"></i> Browse Cars</a>
        <a href="../bookings/manage_bookings.php" class="text-gray-700 hover:text-orange-600"><i class="fas fa-book"></i> My Bookings</a>
        <a href="../logout.php" class="text-red-500 hover:text-red-700"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</header>

<main class="flex-grow max-w-7xl mx-auto p-6 space-y-8">

    <section>
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Upcoming Bookings</h2>
        <?php if ($bookings->num_rows > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php while ($row = $bookings->fetch_assoc()): ?>
                    <div class="bg-white p-4 rounded shadow hover:shadow-lg transition">
                        <h3 class="font-semibold text-lg text-orange-600"><?= htmlspecialchars($row['brand'] . ' ' . $row['car_name']) ?></h3>
                        <p><strong>From:</strong> <?= htmlspecialchars($row['booking_date']) ?></p>
                        <p><strong>To:</strong> <?= htmlspecialchars($row['return_date']) ?></p>
                        <p><strong>Status:</strong> 
                            <span class="inline-block px-2 py-1 rounded text-xs font-semibold
                                <?= $row['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </p>
                        <p><strong>Total Cost:</strong> ZMW <?= number_format($row['total_cost'], 2) ?></p>
                        <a href="../bookings/manage_bookings.php" class="inline-block mt-2 text-sm text-orange-600 hover:underline">Manage Booking</a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-600">You have no upcoming bookings.</p>
        <?php endif; ?>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Booking History</h2>
        <?php if ($history->num_rows > 0): ?>
            <table class="min-w-full bg-white rounded shadow divide-y divide-gray-200">
                <thead class="bg-orange-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Car</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Dates</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Total Cost (ZMW)</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php while ($row = $history->fetch_assoc()): ?>
                        <tr>
                            <td class="px-4 py-2"><?= htmlspecialchars($row['brand'] . ' ' . $row['car_name']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($row['booking_date']) ?> - <?= htmlspecialchars($row['return_date']) ?></td>
                            <td class="px-4 py-2">ZMW <?= number_format($row['total_cost'], 2) ?></td>
                            <td class="px-4 py-2">
                                <span class="inline-block px-2 py-1 rounded text-xs font-semibold
                                    <?= match ($row['status']) {
                                        'completed' => 'bg-blue-100 text-blue-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    } ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-gray-600">No past bookings found.</p>
        <?php endif; ?>
    </section>

</main>

<footer class="bg-white shadow p-4 text-center text-gray-500 text-sm">
    &copy; <?= date("Y") ?> Famuso Rentals. All rights reserved.
</footer>

</body>
</html>
