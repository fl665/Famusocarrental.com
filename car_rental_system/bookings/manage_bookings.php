<?php
session_start();
require_once '../includes/db.php';

// Allow only admin or agent
if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['admin', 'agent'])) {
    header("Location: ../login.php");
    exit();
}

$success = "";
$error = "";

// Handle booking status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);

    if ($_POST['action'] === 'update_status') {
        $new_status = $_POST['status'];
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $booking_id);
        if ($stmt->execute()) {
            $success = "Booking status updated.";
        } else {
            $error = "Failed to update status.";
        }
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        if ($stmt->execute()) {
            $success = "Booking deleted successfully.";
        } else {
            $error = "Failed to delete booking.";
        }
    }
}

// Fetch bookings (fallback to booking_date if created_at doesn't exist)
$sql = "SELECT b.id, b.user_id, b.car_id, b.booking_date, b.return_date, b.total_cost, b.status,
               c.car_name, c.brand,
               u.full_name AS customer_name
        FROM bookings b
        JOIN cars c ON b.car_id = c.id
        JOIN users u ON b.user_id = u.id
        ORDER BY b.booking_date DESC";
$bookings = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Bookings - Famuso Rentals</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">

  <!-- Header -->
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold text-orange-600">Manage Bookings</h1>
    <a href="../dashboard/admin_dashboard.php" class="text-sm text-orange-600 hover:underline">← Back to Dashboard</a>
  </div>

  <!-- Success/Error Message -->
  <?php if ($success): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded mb-4"><?= $success ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded mb-4"><?= $error ?></div>
  <?php endif; ?>

  <!-- Table -->
  <div class="bg-white shadow rounded-xl overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">#ID</th>
          <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Car</th>
          <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Customer</th>
          <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Booking Date</th>
          <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Return Date</th>
          <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Total Cost</th>
          <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Status</th>
          <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        <?php if ($bookings->num_rows > 0): ?>
          <?php while ($row = $bookings->fetch_assoc()): ?>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 text-sm font-medium text-gray-900">#<?= $row['id'] ?></td>
              <td class="px-6 py-4 text-sm"><?= htmlspecialchars($row['brand']) ?> <?= htmlspecialchars($row['car_name']) ?></td>
              <td class="px-6 py-4 text-sm"><?= htmlspecialchars($row['customer_name']) ?></td>
              <td class="px-6 py-4 text-sm"><?= $row['booking_date'] ?></td>
              <td class="px-6 py-4 text-sm"><?= $row['return_date'] ?></td>
              <td class="px-6 py-4 text-sm text-green-700">ZMW <?= number_format($row['total_cost'], 2) ?></td>
              <td class="px-6 py-4 text-sm">
                <form method="POST" class="inline-block">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="booking_id" value="<?= $row['id'] ?>">
                  <select name="status" onchange="this.form.submit()" class="px-2 py-1 rounded border text-sm">
                    <option value="pending" <?= $row['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="confirmed" <?= $row['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="cancelled" <?= $row['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    <option value="completed" <?= $row['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                  </select>
                </form>
              </td>
              <td class="px-6 py-4 text-sm">
                <form method="POST" onsubmit="return confirm('Delete this booking?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="booking_id" value="<?= $row['id'] ?>">
                  <button type="submit" class="text-red-600 hover:underline">Delete</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" class="px-6 py-4 text-center text-gray-500">No bookings found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</body>
</html>
