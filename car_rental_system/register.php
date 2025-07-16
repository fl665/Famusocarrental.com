<?php
require_once 'includes/db.php';

$success = "";
$error = "";

// Check admin availability for dropdown display
$admin_check = $conn->query("SELECT COUNT(*) AS total_admins FROM users WHERE role = 'admin'");
$admin_count = $admin_check->fetch_assoc()['total_admins'];
$allow_admin = $admin_count < 2;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name = trim($_POST['full_name']);
  $email = trim($_POST['email']);
  $phone = trim($_POST['phone']);
  $password = $_POST['password'];
  $role = $_POST['role'];

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Invalid email format.";
  } elseif (strlen($password) < 6) {
    $error = "Password must be at least 6 characters.";
  } elseif ($role === 'admin' && !$allow_admin) {
    $error = "Maximum number of admin accounts reached. Cannot register as admin.";
  } else {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $full_name, $email, $phone, $hashedPassword, $role);

    if ($stmt->execute()) {
      $success = "Registration successful! You can now login.";
    } else {
      $error = "Registration failed. Email may already be used.";
    }
  }
}
?>

<!-- UI -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - Famuso Car Rentals</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
  <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
    <h2 class="text-2xl font-bold text-center text-orange-600 mb-4">Register</h2>

    <?php if ($success): ?>
      <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4"><?= $success ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <label class="block mb-2">Full Name</label>
      <input type="text" name="full_name" class="w-full border rounded px-3 py-2 mb-4" required>

      <label class="block mb-2">Email</label>
      <input type="email" name="email" class="w-full border rounded px-3 py-2 mb-4" required>

      <label class="block mb-2">Phone</label>
      <input type="text" name="phone" class="w-full border rounded px-3 py-2 mb-4" required>

      <label class="block mb-2">Password</label>
      <input type="password" name="password" class="w-full border rounded px-3 py-2 mb-4" required>

      <label class="block mb-2">Register As</label>
      <select name="role" class="w-full border rounded px-3 py-2 mb-6" required>
        <option value="customer">Customer</option>
        <option value="agent">Agent</option>
        <?php if ($allow_admin): ?>
          <option value="admin">Admin</option>
        <?php endif; ?>
      </select>

      <button type="submit" class="w-full bg-orange-600 text-white py-2 rounded hover:bg-orange-700 transition">Register</button>
    </form>

    <p class="mt-4 text-center text-sm">Already have an account? <a href="login.php" class="text-orange-600 hover:underline">Login here</a></p>
  </div>
</body>
</html>
