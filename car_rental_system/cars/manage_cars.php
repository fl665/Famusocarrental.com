<?php
session_start();
require_once '../includes/db.php';

// Ensure only logged-in agents/admins can access
if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['admin', 'agent'])) {
    header("Location: ../login.php");
    exit();
}

$success = "";
$error = "";

// Handle car deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $car_id = intval($_POST['car_id']);
    
    // Check if car has active bookings
    $booking_check = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE car_id = ? AND status IN ('pending', 'confirmed')");
    $booking_check->bind_param("i", $car_id);
    $booking_check->execute();
    $booking_result = $booking_check->get_result();
    $booking_count = $booking_result->fetch_assoc()['count'];
    
    if ($booking_count > 0) {
        $error = "Cannot delete car. It has active bookings.";
    } else {
        // Get image filename to delete
        $image_stmt = $conn->prepare("SELECT image FROM cars WHERE id = ?");
        $image_stmt->bind_param("i", $car_id);
        $image_stmt->execute();
        $image_result = $image_stmt->get_result();
        $image_data = $image_result->fetch_assoc();
        
        // Delete the car record
        $delete_stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
        $delete_stmt->bind_param("i", $car_id);
        
        if ($delete_stmt->execute()) {
            // Delete image file if exists
            if ($image_data && $image_data['image']) {
                $image_path = "../assets/images/" . $image_data['image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            $success = "Car deleted successfully!";
        } else {
            $error = "Failed to delete car.";
        }
    }
}

// Handle availability toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_availability') {
    $car_id = intval($_POST['car_id']);
    $new_availability = $_POST['availability'];
    
    $update_stmt = $conn->prepare("UPDATE cars SET availability = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_availability, $car_id);
    
    if ($update_stmt->execute()) {
        $success = "Car availability updated successfully!";
    } else {
        $error = "Failed to update availability.";
    }
}

// Pagination and filtering
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$brand_filter = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$availability_filter = isset($_GET['availability']) ? trim($_GET['availability']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Build WHERE clause
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(car_name LIKE ? OR brand LIKE ? OR model LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

if (!empty($brand_filter)) {
    $where_conditions[] = "brand = ?";
    $params[] = $brand_filter;
    $param_types .= 's';
}

if (!empty($availability_filter)) {
    $where_conditions[] = "availability = ?";
    $params[] = $availability_filter;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM cars $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_cars = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_cars / $per_page);

// Get cars with pagination
$valid_sort_columns = ['id', 'car_name', 'brand', 'model', 'year', 'price_per_day', 'availability', 'created_at'];
$sort_by = in_array($sort_by, $valid_sort_columns) ? $sort_by : 'id';
$sort_order = ($sort_order === 'ASC') ? 'ASC' : 'DESC';

$sql = "SELECT c.*, u.full_name as added_by_name 
        FROM cars c 
        LEFT JOIN users u ON c.added_by = u.id 
        $where_clause 
        ORDER BY c.$sort_by $sort_order 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$params[] = $per_page;
$params[] = $offset;
$param_types .= 'ii';

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$cars = $stmt->get_result();

// Get unique brands for filter dropdown
$brands_sql = "SELECT DISTINCT brand FROM cars ORDER BY brand";
$brands_result = $conn->query($brands_sql);
$brands = [];
while ($brand = $brands_result->fetch_assoc()) {
    $brands[] = $brand['brand'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cars - Famuso Rentals</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'famuso-orange': '#ea580c',
                        'famuso-orange-dark': '#c2410c',
                        'famuso-orange-light': '#fed7aa',
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(234, 88, 12, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(220, 38, 38, 0.3);
        }
        
        .status-badge {
            transition: all 0.3s ease;
        }
        
        .status-badge:hover {
            transform: scale(1.05);
        }
        
        .table-row {
            transition: all 0.3s ease;
        }
        
        .table-row:hover {
            background-color: #fef3c7;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .filter-active {
            background-color: #fed7aa;
            border-color: #ea580c;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Navigation Header -->
    <header class="bg-white shadow-sm border-b border-gray-200 mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <i class="fas fa-car text-famuso-orange text-2xl mr-3"></i>
                    <h1 class="text-2xl font-bold text-gray-900">Famuso Rentals</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                    <a href="add_car.php" class="bg-famuso-orange text-white px-4 py-2 rounded-lg hover:bg-famuso-orange-dark transition-colors">
                        <i class="fas fa-plus mr-1"></i> Add Car
                    </a>
                    <a href="../dashboard/admin_dashboard.php" class="text-famuso-orange hover:text-famuso-orange-dark">
                        <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div class="mb-4 sm:mb-0">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Manage Cars</h2>
                    <p class="text-gray-600">Manage your rental fleet inventory</p>
                </div>
                <div class="flex items-center space-x-2 bg-famuso-orange-light px-4 py-2 rounded-full">
                    <i class="fas fa-cars text-famuso-orange"></i>
                    <span class="text-famuso-orange font-medium"><?= $total_cars ?> Total Cars</span>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-r-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-400 mr-3"></i>
                    <p class="text-green-800 font-medium"><?= htmlspecialchars($success) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-r-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-400 mr-3"></i>
                    <p class="text-red-800 font-medium"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filters and Search -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-famuso-orange focus:border-famuso-orange <?= !empty($search) ? 'filter-active' : '' ?>"
                               placeholder="Search cars...">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                    <select name="brand" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-famuso-orange focus:border-famuso-orange <?= !empty($brand_filter) ? 'filter-active' : '' ?>">
                        <option value="">All Brands</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= htmlspecialchars($brand) ?>" <?= $brand_filter === $brand ? 'selected' : '' ?>>
                                <?= htmlspecialchars($brand) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Availability</label>
                    <select name="availability" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-famuso-orange focus:border-famuso-orange <?= !empty($availability_filter) ? 'filter-active' : '' ?>">
                        <option value="">All Status</option>
                        <option value="available" <?= $availability_filter === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="unavailable" <?= $availability_filter === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                    <select name="sort" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-famuso-orange focus:border-famuso-orange">
                        <option value="id" <?= $sort_by === 'id' ? 'selected' : '' ?>>ID</option>
                        <option value="car_name" <?= $sort_by === 'car_name' ? 'selected' : '' ?>>Car Name</option>
                        <option value="brand" <?= $sort_by === 'brand' ? 'selected' : '' ?>>Brand</option>
                        <option value="year" <?= $sort_by === 'year' ? 'selected' : '' ?>>Year</option>
                        <option value="price_per_day" <?= $sort_by === 'price_per_day' ? 'selected' : '' ?>>Price</option>
                        <option value="created_at" <?= $sort_by === 'created_at' ? 'selected' : '' ?>>Date Added</option>
                    </select>
                </div>

                <div class="flex items-end space-x-2">
                    <button type="submit" class="btn-primary text-white px-4 py-2 rounded-lg flex items-center">
                        <i class="fas fa-filter mr-2"></i>
                        Filter
                    </button>
                    <a href="?" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
                
                <input type="hidden" name="order" value="<?= $sort_order ?>">
            </form>
        </div>

        <!-- Cars Table -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-famuso-orange to-famuso-orange-dark">
                <h3 class="text-lg font-semibold text-white flex items-center">
                    <i class="fas fa-list mr-2"></i>
                    Cars Inventory
                </h3>
            </div>

            <?php if ($cars->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'id', 'order' => $sort_by === 'id' && $sort_order === 'ASC' ? 'DESC' : 'ASC'])) ?>" 
                                       class="flex items-center hover:text-famuso-orange">
                                        ID
                                        <?php if ($sort_by === 'id'): ?>
                                            <i class="fas fa-sort-<?= $sort_order === 'ASC' ? 'up' : 'down' ?> ml-1"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'car_name', 'order' => $sort_by === 'car_name' && $sort_order === 'ASC' ? 'DESC' : 'ASC'])) ?>" 
                                       class="flex items-center hover:text-famuso-orange">
                                        Car Details
                                        <?php if ($sort_by === 'car_name'): ?>
                                            <i class="fas fa-sort-<?= $sort_order === 'ASC' ? 'up' : 'down' ?> ml-1"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'price_per_day', 'order' => $sort_by === 'price_per_day' && $sort_order === 'ASC' ? 'DESC' : 'ASC'])) ?>" 
                                       class="flex items-center hover:text-famuso-orange">
                                        Price/Day
                                        <?php if ($sort_by === 'price_per_day'): ?>
                                            <i class="fas fa-sort-<?= $sort_order === 'ASC' ? 'up' : 'down' ?> ml-1"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Added By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($car = $cars->fetch_assoc()): ?>
                                <tr class="table-row">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #<?= $car['id'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($car['image']): ?>
                                            <img src="../assets/images/<?= htmlspecialchars($car['image']) ?>" 
                                                 alt="<?= htmlspecialchars($car['car_name']) ?>"
                                                 class="w-16 h-12 object-cover rounded-lg shadow-sm cursor-pointer"
                                                 onclick="showImageModal('<?= htmlspecialchars($car['image']) ?>', '<?= htmlspecialchars($car['car_name']) ?>')">
                                        <?php else: ?>
                                            <div class="w-16 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-car text-gray-400"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($car['car_name']) ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($car['brand']) ?> <?= htmlspecialchars($car['model']) ?></div>
                                        <div class="text-xs text-gray-400"><?= $car['year'] ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">ZMW <?= number_format($car['price_per_day'], 2) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="toggle_availability">
                                            <input type="hidden" name="car_id" value="<?= $car['id'] ?>">
                                            <input type="hidden" name="availability" value="<?= $car['availability'] === 'available' ? 'unavailable' : 'available' ?>">
                                            <button type="submit" class="status-badge px-3 py-1 text-xs font-medium rounded-full cursor-pointer transition-colors <?= $car['availability'] === 'available' ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' ?>">
                                                <?= $car['availability'] === 'available' ? '🟢 Available' : '🔴 Unavailable' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($car['added_by_name'] ?? 'Unknown') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="edit_car.php?id=<?= $car['id'] ?>" 
                                               class="text-famuso-orange hover:text-famuso-orange-dark">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="confirmDelete(<?= $car['id'] ?>, '<?= htmlspecialchars($car['car_name']) ?>')" 
                                                    class="text-red-600 hover:text-red-800">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="bg-gray-50 px-6 py-3 flex items-center justify-between border-t border-gray-200">
                        <div class="text-sm text-gray-700">
                            Showing <?= $offset + 1 ?> to <?= min($offset + $per_page, $total_cars) ?> of <?= $total_cars ?> results
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                                   class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                                   class="px-3 py-2 text-sm rounded-lg <?= $i === $page ? 'bg-famuso-orange text-white' : 'bg-white border border-gray-300 hover:bg-gray-50' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                                   class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-12">
                    <i class="fas fa-car text-gray-400 text-6xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No cars found</h3>
                    <p class="text-gray-500 mb-4">No cars match your current filters.</p>
                    <a href="add_car.php" class="btn-primary text-white px-6 py-3 rounded-lg inline-flex items-center">
                        <i class="fas fa-plus mr-2"></i>
                        Add First Car
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="px-6 py-4 bg-red-50 border-b border-red-200">
                <h3 class="text-lg font-semibold text-red-800 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Confirm Deletion
                </h3>
            </div>
            <div class="p-6">
                <p class="text-gray-700 mb-4">Are you sure you want to delete this car?</p>
                <div id="carDetails" class="bg-gray-50 p-4 rounded-lg mb-4"></div>
                <p class="text-sm text-red-600 mb-4">
                    <i class="fas fa-warning mr-1"></i>
                    This action cannot be undone.
                </p>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                        Cancel
                    </button>
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="car_id" id="deleteCarId">
                        <button type="submit" class="btn-danger text-white px-4 py-2 rounded-lg">
                            <i class="fas fa-trash mr-2"></i>
                            Delete Car
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

   <!-- Image Modal Content -->
            </div>
            <div class="p-6">
                <img id="modalImage" src="" alt="Car Image" class="w-full h-auto object-cover rounded-lg shadow">
            </div>
            <div class="flex justify-end px-6 py-4 border-t border-gray-200">
                <button onclick="closeImageModal()" class="px-4 py-2 text-white bg-famuso-orange hover:bg-famuso-orange-dark rounded-lg">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        function confirmDelete(carId, carName) {
            document.getElementById('deleteCarId').value = carId;
            document.getElementById('carDetails').innerHTML = `<strong>${carName}</strong>`;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        function showImageModal(imageName, carName) {
            document.getElementById('modalImage').src = `../assets/images/${imageName}`;
            document.getElementById('imageModalTitle').innerText = carName;
            document.getElementById('imageModal').style.display = 'block';
        }

        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // Close modals on ESC
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeModal();
                closeImageModal();
            }
        });
    </script>

</body>
</html>
