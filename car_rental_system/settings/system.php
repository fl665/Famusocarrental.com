<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['id'];
$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pass = trim($_POST['new_password']);
    $confirm_pass = trim($_POST['confirm_password']);

    if ($new_pass === $confirm_pass && strlen($new_pass) >= 6) {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $user_id);
        if ($stmt->execute()) {
            $success = "Password updated successfully.";
        } else {
            $error = "Error updating password.";
        }
    } else {
        $error = "Passwords do not match or are too short.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Settings - Famuso Rentals</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
  <header class="bg-white shadow p-4 flex justify-between items-center">
    <h1 class="text-xl font-bold text-orange-600">Settings</h1>
    <div class="flex space-x-4">
      <a href="../dashboard/admin_dashboard.php" class="text-orange-600 hover:text-orange-800"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="../logout.php" class="text-red-500 hover:text-red-700"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </header>

  <main class="max-w-xl mx-auto px-4 py-8 bg-white mt-6 rounded-lg shadow">
    <h2 class="text-2xl font-semibold text-gray-700 mb-4">Update Password</h2>

    <?php if ($success): ?>
      <div class="bg-green-100 text-green-800 p-3 rounded mb-4"><?= $success ?></div>
    <?php elseif ($error): ?>
      <div class="bg-red-100 text-red-800 p-3 rounded mb-4"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700">New Password</label>
        <input type="password" name="new_password" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2" required>
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
        <input type="password" name="confirm_password" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2" required>
      </div>
      <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">Update Password</button>
    </form>
  </main>
</body>
</html>
