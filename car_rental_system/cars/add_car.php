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
$validation_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_name = trim($_POST['car_name']);
    $brand = trim($_POST['brand']);
    $model = trim($_POST['model']);
    $year = intval($_POST['year']);
    $price_per_day = floatval($_POST['price_per_day']);
    $availability = $_POST['availability'];
    $added_by = $_SESSION['id'];
    
    // Enhanced validation
    if (empty($car_name)) {
        $validation_errors['car_name'] = "Car name is required";
    } elseif (strlen($car_name) < 3) {
        $validation_errors['car_name'] = "Car name must be at least 3 characters";
    }
    
    if (empty($brand)) {
        $validation_errors['brand'] = "Brand is required";
    }
    
    if (empty($model)) {
        $validation_errors['model'] = "Model is required";
    }
    
    if ($year < 2000 || $year > 2035) {
        $validation_errors['year'] = "Year must be between 2000 and 2035";
    }
    
    if ($price_per_day <= 0) {
        $validation_errors['price_per_day'] = "Price must be greater than 0";
    }
    
    // Handle image upload with enhanced validation
    $image_name = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $validation_errors['image'] = "Only JPEG, PNG, GIF, and WebP images are allowed";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $validation_errors['image'] = "Image size must be less than 5MB";
        } else {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid("car_", true) . "." . $ext;
            
            // Ensure directory exists
            $upload_dir = "../assets/images/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name)) {
                $validation_errors['image'] = "Failed to upload image";
            }
        }
    }
    
    // Only proceed if no validation errors
    if (empty($validation_errors)) {
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO cars (car_name, brand, model, year, price_per_day, availability, image, added_by)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssidsii", $car_name, $brand, $model, $year, $price_per_day, $availability, $image_name, $added_by);
        
        if ($stmt->execute()) {
            $success = "Car added successfully!";
            // Clear form data after successful submission
            $car_name = $brand = $model = "";
            $year = date('Y');
            $price_per_day = 0;
            $availability = "available";
        } else {
            $error = "Failed to add car. Please try again.";
        }
    }
}

// Get popular brands for autocomplete
$popular_brands = ['Toyota', 'Honda', 'Ford', 'Chevrolet', 'BMW', 'Mercedes-Benz', 'Audi', 'Volkswagen', 'Nissan', 'Hyundai', 'Mazda', 'Subaru'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Car - Famuso Rentals</title>
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
        .form-group {
            position: relative;
        }
        
        .form-input {
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(234, 88, 12, 0.1);
        }
        
        .upload-zone {
            transition: all 0.3s ease;
        }
        
        .upload-zone:hover {
            background-color: #fef3c7;
            border-color: #ea580c;
        }
        
        .upload-zone.dragover {
            background-color: #fed7aa;
            border-color: #ea580c;
            transform: scale(1.02);
        }
        
        .success-animation {
            animation: slideInRight 0.5s ease-out;
        }
        
        .error-animation {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(234, 88, 12, 0.3);
        }
        
        .btn-primary:active {
            transform: translateY(0);
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
                    <a href="../dashboard/admin_dashboard.php" class="text-famuso-orange hover:text-famuso-orange-dark">
                        <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Add New Car</h2>
                    <p class="text-gray-600">Add a new vehicle to your rental fleet</p>
                </div>
                <div class="hidden sm:block">
                    <div class="flex items-center space-x-2 bg-famuso-orange-light px-4 py-2 rounded-full">
                        <i class="fas fa-plus-circle text-famuso-orange"></i>
                        <span class="text-famuso-orange font-medium">New Vehicle</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="success-animation bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-r-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-400 mr-3"></i>
                    <div>
                        <p class="text-green-800 font-medium"><?= htmlspecialchars($success) ?></p>
                        <p class="text-green-600 text-sm mt-1">The car has been successfully added to your inventory.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-animation bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-r-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-400 mr-3"></i>
                    <div>
                        <p class="text-red-800 font-medium"><?= htmlspecialchars($error) ?></p>
                        <p class="text-red-600 text-sm mt-1">Please check your input and try again.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Form -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-famuso-orange to-famuso-orange-dark">
                <h3 class="text-lg font-semibold text-white flex items-center">
                    <i class="fas fa-car-side mr-2"></i>
                    Vehicle Information
                </h3>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                <!-- Car Name & Brand Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-tag mr-1 text-famuso-orange"></i>
                            Car Name
                        </label>
                        <input type="text" name="car_name" 
                               value="<?= htmlspecialchars($car_name ?? '') ?>"
                               class="form-input w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:border-famuso-orange focus:ring-2 focus:ring-famuso-orange focus:ring-opacity-20 outline-none <?= isset($validation_errors['car_name']) ? 'border-red-500' : '' ?>"
                               placeholder="e.g., Toyota Camry 2023"
                               required>
                        <?php if (isset($validation_errors['car_name'])): ?>
                            <p class="mt-1 text-sm text-red-600 flex items-center">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                <?= htmlspecialchars($validation_errors['car_name']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-industry mr-1 text-famuso-orange"></i>
                            Brand
                        </label>
                        <input type="text" name="brand" 
                               value="<?= htmlspecialchars($brand ?? '') ?>"
                               class="form-input w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:border-famuso-orange focus:ring-2 focus:ring-famuso-orange focus:ring-opacity-20 outline-none <?= isset($validation_errors['brand']) ? 'border-red-500' : '' ?>"
                               placeholder="e.g., Toyota"
                               list="brand-suggestions"
                               required>
                        <datalist id="brand-suggestions">
                            <?php foreach ($popular_brands as $brand): ?>
                                <option value="<?= htmlspecialchars($brand) ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <?php if (isset($validation_errors['brand'])): ?>
                            <p class="mt-1 text-sm text-red-600 flex items-center">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                <?= htmlspecialchars($validation_errors['brand']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Model & Year Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-car mr-1 text-famuso-orange"></i>
                            Model
                        </label>
                        <input type="text" name="model" 
                               value="<?= htmlspecialchars($model ?? '') ?>"
                               class="form-input w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:border-famuso-orange focus:ring-2 focus:ring-famuso-orange focus:ring-opacity-20 outline-none <?= isset($validation_errors['model']) ? 'border-red-500' : '' ?>"
                               placeholder="e.g., Camry"
                               required>
                        <?php if (isset($validation_errors['model'])): ?>
                            <p class="mt-1 text-sm text-red-600 flex items-center">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                <?= htmlspecialchars($validation_errors['model']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar-alt mr-1 text-famuso-orange"></i>
                            Year
                        </label>
                        <input type="number" name="year" 
                               value="<?= htmlspecialchars($year ?? date('Y')) ?>"
                               class="form-input w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:border-famuso-orange focus:ring-2 focus:ring-famuso-orange focus:ring-opacity-20 outline-none <?= isset($validation_errors['year']) ? 'border-red-500' : '' ?>"
                               min="2000" max="2035" required>
                        <?php if (isset($validation_errors['year'])): ?>
                            <p class="mt-1 text-sm text-red-600 flex items-center">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                <?= htmlspecialchars($validation_errors['year']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Price & Availability Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-money-bill-wave mr-1 text-famuso-orange"></i>
                            Price per Day (ZMW)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-gray-500">ZMW</span>
                            <input type="number" step="0.01" name="price_per_day" 
                                   value="<?= htmlspecialchars($price_per_day ?? '') ?>"
                                   class="form-input w-full border-2 border-gray-300 rounded-lg pl-16 pr-4 py-3 focus:border-famuso-orange focus:ring-2 focus:ring-famuso-orange focus:ring-opacity-20 outline-none <?= isset($validation_errors['price_per_day']) ? 'border-red-500' : '' ?>"
                                   placeholder="0.00"
                                   required>
                        </div>
                        <?php if (isset($validation_errors['price_per_day'])): ?>
                            <p class="mt-1 text-sm text-red-600 flex items-center">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                <?= htmlspecialchars($validation_errors['price_per_day']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-toggle-on mr-1 text-famuso-orange"></i>
                            Availability Status
                        </label>
                        <select name="availability" 
                                class="form-input w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:border-famuso-orange focus:ring-2 focus:ring-famuso-orange focus:ring-opacity-20 outline-none"
                                required>
                            <option value="available" <?= ($availability ?? 'available') === 'available' ? 'selected' : '' ?>>
                                🟢 Available
                            </option>
                            <option value="unavailable" <?= ($availability ?? '') === 'unavailable' ? 'selected' : '' ?>>
                                🔴 Unavailable
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Image Upload -->
                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-image mr-1 text-famuso-orange"></i>
                        Car Image
                    </label>
                    <div class="upload-zone border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-famuso-orange transition-colors <?= isset($validation_errors['image']) ? 'border-red-500' : '' ?>">
                        <input type="file" name="image" accept="image/*" 
                               class="hidden" id="image-upload">
                        <label for="image-upload" class="cursor-pointer">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                            <p class="text-gray-600 mb-2">Click to upload or drag and drop</p>
                            <p class="text-sm text-gray-500">PNG, JPG, GIF, WebP up to 5MB</p>
                        </label>
                        <div id="image-preview" class="mt-4 hidden">
                            <img id="preview-img" class="mx-auto max-h-48 rounded-lg shadow-md" alt="Preview">
                            <p id="image-name" class="text-sm text-gray-600 mt-2"></p>
                        </div>
                    </div>
                    <?php if (isset($validation_errors['image'])): ?>
                        <p class="mt-1 text-sm text-red-600 flex items-center">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <?= htmlspecialchars($validation_errors['image']) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <a href="../dashboard/admin_dashboard.php" class="text-gray-600 hover:text-gray-800 flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                    <button type="submit" class="btn-primary text-white px-8 py-3 rounded-lg font-medium flex items-center space-x-2">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add Car</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Image preview functionality
        const imageUpload = document.getElementById('image-upload');
        const imagePreview = document.getElementById('image-preview');
        const previewImg = document.getElementById('preview-img');
        const imageName = document.getElementById('image-name');
        const uploadZone = document.querySelector('.upload-zone');

        imageUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    imageName.textContent = file.name;
                    imagePreview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });

        // Drag and drop functionality
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });

        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
        });

        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                imageUpload.files = files;
                const event = new Event('change', { bubbles: true });
                imageUpload.dispatchEvent(event);
            }
        });

        // Auto-hide success/error messages
        setTimeout(function() {
            const alerts = document.querySelectorAll('.success-animation, .error-animation');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Form validation enhancements
        const form = document.querySelector('form');
        const inputs = form.querySelectorAll('input, select');

        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.checkValidity()) {
                    this.classList.remove('border-red-500');
                    this.classList.add('border-green-500');
                } else {
                    this.classList.add('border-red-500');
                    this.classList.remove('border-green-500');
                }
            });
        });

        // Price formatting
        const priceInput = document.querySelector('input[name="price_per_day"]');
        priceInput.addEventListener('input', function() {
            let value = this.value.replace(/[^\d.]/g, '');
            if (value.includes('.')) {
                const parts = value.split('.');
                value = parts[0] + '.' + parts[1].substring(0, 2);
            }
            this.value = value;
        });
    </script>
</body>
</html>