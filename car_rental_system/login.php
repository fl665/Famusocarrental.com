<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'];
  $password = $_POST['password'];

  $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
      $_SESSION['id'] = $user['id'];
      $_SESSION['role'] = $user['role'];
      $_SESSION['name'] = $user['full_name'];

      if ($user['role'] === 'admin') {
        header("Location: dashboard/admin_dashboard.php");
      } elseif ($user['role'] === 'agent') {
        header("Location: dashboard/agent_dashboard.php");
      } else {
        header("Location: dashboard/customer_dashboard.php");
      }
      exit();
    } else {
      $error = "Invalid password!";
    }
  } else {
    $error = "User not found!";
  }
}
?>

<!-- UI -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - Famuso Car Rentals</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
  <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
    <h2 class="text-2xl font-bold text-center text-orange-600 mb-4">Login</h2>
    <?php if (isset($error)): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4"><?= $error ?></div>
    <?php endif; ?>
    <form method="POST">
      <label class="block mb-2">Email</label>
      <input type="email" name="email" class="w-full border border-gray-300 rounded px-3 py-2 mb-4" required>
      
      <label class="block mb-2">Password</label>
      <input type="password" name="password" class="w-full border border-gray-300 rounded px-3 py-2 mb-6" required>
      
      <button type="submit" class="w-full bg-orange-600 text-white py-2 rounded hover:bg-orange-700 transition">Login</button>
    </form>
    <p class="mt-4 text-center text-sm">Don't have an account? <a href="register.php" class="text-orange-600 hover:underline">Register here</a></p>
  </div>
</body>
</html>
