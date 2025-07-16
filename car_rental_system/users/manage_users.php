<?php
session_start();
require_once '../includes/db.php';

// Access control: Only admin or agent
if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['admin', 'agent'])) {
    header("Location: ../login.php");
    exit();
}

$success = "";
$error = "";

// Handle user updates or deletions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);

    if ($_POST['action'] === 'update_role' && isset($_POST['role'])) {
        $new_role = $_POST['role'];
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $user_id);
        if ($stmt->execute()) {
            $success = "User role updated.";
        } else {
            $error = "Failed to update user.";
        }
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $success = "User deleted successfully.";
        } else {
            $error = "Failed to delete user.";
        }
    }
}

// Fetch all users
$sql = "SELECT id, full_name, email, phone, role FROM users ORDER BY id DESC";
$users = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users - Famuso Rentals</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">

  <!-- Header -->
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold text-orange-600">Manage Users</h1>
    <a href="../dashboard/admin_dashboard.php" class="text-sm text-orange-600 hover:underline">← Back to Dashboard</a>
  </div>

  <!-- Notifications -->
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
          <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Full Name</th>
          <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Email</th>
          <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Phone</th>
          <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Role</th>
          <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        <?php if ($users->num_rows > 0): ?>
          <?php while ($row = $users->fetch_assoc()): ?>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 text-sm font-medium text-gray-900">#<?= $row['id'] ?></td>
              <td class="px-6 py-4 text-sm"><?= htmlspecialchars($row['full_name']) ?></td>
              <td class="px-6 py-4 text-sm"><?= htmlspecialchars($row['email']) ?></td>
              <td class="px-6 py-4 text-sm"><?= htmlspecialchars($row['phone']) ?></td>
              <td class="px-6 py-4 text-sm">
                <form method="POST">
                  <input type="hidden" name="action" value="update_role">
                  <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                  <select name="role" onchange="this.form.submit()" class="text-sm border rounded px-2 py-1">
                    <option value="customer" <?= $row['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                    <option value="agent" <?= $row['role'] === 'agent' ? 'selected' : '' ?>>Agent</option>
                    <option value="admin" <?= $row['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                  </select>
                </form>
              </td>
              <td class="px-6 py-4 text-sm">
                <form method="POST" onsubmit="return confirm('Delete this user?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                  <button type="submit" class="text-red-600 hover:underline">Delete</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="text-center px-6 py-4 text-gray-500">No users found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</body>
</html>
