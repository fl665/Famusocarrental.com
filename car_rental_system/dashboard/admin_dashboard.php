<?php
session_start();

// Enhanced security checks
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// CSRF protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Session timeout check (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_destroy();
    header("Location: ../index.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

// Database connection for statistics (assuming you have a config file)
try {
    // Include your database configuration
    // require_once '../config/database.php';
    
    // Sample statistics queries (uncomment and modify based on your database structure)
    /*
    $stmt = $pdo->query("SELECT COUNT(*) FROM cars");
    $total_cars = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
    $total_bookings = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
    $total_users = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
    $pending_bookings = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT SUM(total_amount) FROM bookings WHERE status = 'completed' AND MONTH(created_at) = MONTH(CURRENT_DATE())");
    $monthly_revenue = $stmt->fetchColumn() ?? 0;
    */
    
    // Sample data for demonstration
    $total_cars = 25;
    $total_bookings = 147;
    $total_users = 89;
    $pending_bookings = 8;
    $monthly_revenue = 15750;
    
} catch (PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    // Set default values if database is unavailable
    $total_cars = $total_bookings = $total_users = $pending_bookings = $monthly_revenue = 0;
}

// Get user's last login info
$last_login = $_SESSION['last_login'] ?? 'N/A';
$user_name = htmlspecialchars($_SESSION['name'] ?? 'Admin');
$user_email = htmlspecialchars($_SESSION['email'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Famuso Car Rentals</title>
    
    <!-- Security headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    
    <!-- Custom styles -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .notification-dot {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Mobile menu overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>
    
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar sidebar-transition fixed left-0 top-0 h-full w-64 bg-white shadow-lg z-50 md:translate-x-0">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-800">Famuso Admin</h1>
                <button id="sidebar-close" class="md:hidden text-gray-500 hover:text-gray-700">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>
        </div>
        
        <nav class="mt-6 px-4">
            <a href="#" class="flex items-center px-4 py-3 text-gray-700 bg-orange-50 border-r-4 border-orange-500 rounded-l-lg">
                <i class="ti ti-dashboard text-xl mr-3"></i>
                Dashboard
            </a>
            <a href="../cars/manage_cars.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg mt-1">
                <i class="ti ti-car text-xl mr-3"></i>
                Manage Cars
            </a>
            <a href="../bookings/manage_bookings.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg mt-1">
                <i class="ti ti-calendar-stats text-xl mr-3"></i>
                Bookings
                <?php if ($pending_bookings > 0): ?>
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full notification-dot">
                        <?= $pending_bookings ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="../users/manage_users.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg mt-1">
                <i class="ti ti-users text-xl mr-3"></i>
                Users
            </a>
            <a href="../reports/analytics.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg mt-1">
                <i class="ti ti-chart-line text-xl mr-3"></i>
                Analytics
            </a>
            <a href="../settings/system.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg mt-1">
                <i class="ti ti-settings text-xl mr-3"></i>
                Settings
            </a>
        </nav>
    </aside>
    
    <!-- Main Content -->
    <main class="md:ml-64 min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center">
                    <button id="sidebar-toggle" class="md:hidden text-gray-500 hover:text-gray-700 mr-4">
                        <i class="ti ti-menu-2 text-xl"></i>
                    </button>
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Dashboard</h1>
                        <p class="text-sm text-gray-600">Welcome back, <?= $user_name ?></p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Notifications -->
                    <div class="relative">
                        <button class="text-gray-500 hover:text-gray-700 relative">
                            <i class="ti ti-bell text-xl"></i>
                            <?php if ($pending_bookings > 0): ?>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                                    <?= $pending_bookings ?>
                                </span>
                            <?php endif; ?>
                        </button>
                    </div>
                    
                    <!-- User menu -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center text-gray-700 hover:text-gray-900">
                            <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center text-white font-medium text-sm">
                                <?= strtoupper(substr($user_name, 0, 1)) ?>
                            </div>
                            <i class="ti ti-chevron-down ml-1"></i>
                        </button>
                        
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                            <a href="../profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                            <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Dashboard Content -->
        <div class="p-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Cars</p>
                            <p class="text-2xl font-bold text-gray-800"><?= number_format($total_cars) ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="ti ti-car text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Bookings</p>
                            <p class="text-2xl font-bold text-gray-800"><?= number_format($total_bookings) ?></p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="ti ti-calendar-stats text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Users</p>
                            <p class="text-2xl font-bold text-gray-800"><?= number_format($total_users) ?></p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="ti ti-users text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Monthly Revenue</p>
                            <p class="text-2xl font-bold text-gray-800">$<?= number_format($monthly_revenue) ?></p>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="ti ti-currency-dollar text-orange-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <a href="../cars/add_car.php" class="bg-white rounded-lg shadow p-6 card-hover block">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="ti ti-car-plus text-blue-600 text-xl"></i>
                        </div>
                        <i class="ti ti-arrow-right text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Add New Car</h3>
                    <p class="text-sm text-gray-600">Add a new vehicle to your rental fleet</p>
                </a>
                
                <a href="../cars/manage_cars.php" class="bg-white rounded-lg shadow p-6 card-hover block">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="ti ti-tools text-green-600 text-xl"></i>
                        </div>
                        <i class="ti ti-arrow-right text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Manage Cars</h3>
                    <p class="text-sm text-gray-600">Edit, update, or remove vehicles</p>
                </a>
                
                <a href="../bookings/manage_bookings.php" class="bg-white rounded-lg shadow p-6 card-hover block">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="ti ti-calendar-stats text-orange-600 text-xl"></i>
                        </div>
                        <div class="flex items-center">
                            <?php if ($pending_bookings > 0): ?>
                                <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full mr-2">
                                    <?= $pending_bookings ?>
                                </span>
                            <?php endif; ?>
                            <i class="ti ti-arrow-right text-gray-400"></i>
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Manage Bookings</h3>
                    <p class="text-sm text-gray-600">View and manage customer bookings</p>
                </a>
                
                <a href="../users/manage_users.php" class="bg-white rounded-lg shadow p-6 card-hover block">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="ti ti-users text-purple-600 text-xl"></i>
                        </div>
                        <i class="ti ti-arrow-right text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Manage Users</h3>
                    <p class="text-sm text-gray-600">View and manage customer accounts</p>
                </a>
                
                <a href="../reports/analytics.php" class="bg-white rounded-lg shadow p-6 card-hover block">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <i class="ti ti-chart-line text-indigo-600 text-xl"></i>
                        </div>
                        <i class="ti ti-arrow-right text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Analytics</h3>
                    <p class="text-sm text-gray-600">View detailed reports and analytics</p>
                </a>
                
                <a href="../settings/system.php" class="bg-white rounded-lg shadow p-6 card-hover block">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                            <i class="ti ti-settings text-gray-600 text-xl"></i>
                        </div>
                        <i class="ti ti-arrow-right text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">System Settings</h3>
                    <p class="text-sm text-gray-600">Configure system preferences</p>
                </a>
            </div>
            
            <!-- Recent Activity -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Recent Activity</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="ti ti-check text-green-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-800">New booking confirmed</p>
                                <p class="text-xs text-gray-500">2 hours ago</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="ti ti-car text-blue-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-800">New car added to fleet</p>
                                <p class="text-xs text-gray-500">5 hours ago</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="ti ti-user text-purple-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-800">New user registered</p>
                                <p class="text-xs text-gray-500">1 day ago</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="md:ml-64 bg-white border-t border-gray-200 px-6 py-4">
        <div class="flex flex-col md:flex-row justify-between items-center text-sm text-gray-600">
            <p>&copy; <?= date('Y') ?> Famuso Car Rentals. All rights reserved.</p>
            <div class="flex items-center space-x-4 mt-2 md:mt-0">
                <span>Last login: <?= $last_login ?></span>
                <span>Version: 2.1.0</span>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebarClose = document.getElementById('sidebar-close');
            const overlay = document.getElementById('mobile-overlay');
            
            function openSidebar() {
                sidebar.classList.add('active');
                overlay.classList.remove('hidden');
            }
            
            function closeSidebar() {
                sidebar.classList.remove('active');
                overlay.classList.add('hidden');
            }
            
            sidebarToggle?.addEventListener('click', openSidebar);
            sidebarClose?.addEventListener('click', closeSidebar);
            overlay?.addEventListener('click', closeSidebar);
            
            // Session timeout warning
            let warningShown = false;
            setInterval(function() {
                fetch('../check_session.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.remaining < 300 && !warningShown) { // 5 minutes warning
                            warningShown = true;
                            if (confirm('Your session will expire in 5 minutes. Do you want to extend it?')) {
                                location.reload();
                            }
                        }
                    })
                    .catch(error => console.error('Session check failed:', error));
            }, 60000); // Check every minute
            
            // Auto-refresh stats every 30 seconds
            setInterval(function() {
                fetch('../api/dashboard_stats.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update stats cards with new data
                            document.querySelector('[data-stat="cars"]').textContent = data.total_cars;
                            document.querySelector('[data-stat="bookings"]').textContent = data.total_bookings;
                            document.querySelector('[data-stat="users"]').textContent = data.total_users;
                            document.querySelector('[data-stat="revenue"]').textContent = '$' + data.monthly_revenue;
                        }
                    })
                    .catch(error => console.error('Stats update failed:', error));
            }, 30000);
        });
    </script>
</body>
</html>