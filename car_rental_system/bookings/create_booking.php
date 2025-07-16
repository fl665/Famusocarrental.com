<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
  header("Location: ../login.php");
  exit;
}

$car_id = $_GET['car_id'];
$user_id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $start = $_POST['booking_date'];
  $end = $_POST['return_date'];
  $days = (strtotime($end) - strtotime($start)) / 86400;

  $car = $conn->query("SELECT price_per_day FROM cars WHERE id=$car_id")->fetch_assoc();
  $total = $days * $car['price_per_day'];

  $stmt = $conn->prepare("INSERT INTO bookings (user_id, car_id, booking_date, return_date, total_cost) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param("iissd", $user_id, $car_id, $start, $end, $total);

  if ($stmt->execute()) {
    $success = "Booking successful!";
  } else {
    $error = "Booking failed.";
  }
}
?>

<!-- UI -->
<!DOCTYPE html>
<html>
<head>
  <title>Book Car</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 py-10 px-4">
  <div class="max-w-xl mx-auto bg-white p-6 rounded shadow-md">
    <h2 class="text-2xl font-bold mb-4 text-orange-600">Book a Car</h2>
    <?php if (isset($success)): ?><div class="bg-green-100 text-green-700 px-4 py-2 mb-4 rounded"><?= $success ?></div><?php endif; ?>
    <?php if (isset($error)): ?><div class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded"><?= $error ?></div><?php endif; ?>

    <form method="POST">
      <label class="block mb-2">Pickup Date</label>
      <input type="date" name="booking_date" class="w-full border px-3 py-2 mb-4 rounded" required>

      <label class="block mb-2">Return Date</label>
      <input type="date" name="return_date" class="w-full border px-3 py-2 mb-4 rounded" required>

      <button type="submit" class="bg-orange-600 text-white px-6 py-2 rounded hover:bg-orange-700">Confirm Booking</button>
    </form>
  </div>
</body>
</html>
