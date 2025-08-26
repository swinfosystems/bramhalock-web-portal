<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: bramhalock.php');
    exit();
}

$error = '';

if ($_POST) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $loginType = $_POST['login_type'] ?? 'user';
    
    if ($email && $password) {
        $user = authenticateUser($email, $password);
        
        if ($user) {
            // Check role-based access
            if ($loginType === 'admin' && $user['role'] !== 'admin') {
                $error = 'Admin access required for this login type.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                
                header('Location: bramhalock.php');
                exit();
            }
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BramhaLock - Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .login-card { backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95); }
        .login-type-btn { transition: all 0.3s ease; }
        .login-type-btn.active { background: #667eea; color: white; }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center">
    <div class="login-card rounded-2xl shadow-2xl p-8 w-full max-w-md mx-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                <i class="fas fa-shield-alt text-2xl text-blue-600"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">BramhaLock</h1>
            <p class="text-gray-600">Secure Remote Management</p>
        </div>

        <!-- Login Type Selection -->
        <div class="flex mb-6 bg-gray-100 rounded-lg p-1">
            <button type="button" id="user-login-btn" class="login-type-btn flex-1 py-2 px-4 rounded-md text-sm font-medium text-gray-700 active" onclick="switchLoginType('user')">
                <i class="fas fa-user mr-2"></i>User Login
            </button>
            <button type="button" id="admin-login-btn" class="login-type-btn flex-1 py-2 px-4 rounded-md text-sm font-medium text-gray-700" onclick="switchLoginType('admin')">
                <i class="fas fa-user-shield mr-2"></i>Admin Login
            </button>
        </div>

        <!-- Error Message -->
        <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" class="space-y-6">
            <input type="hidden" id="login_type" name="login_type" value="user">
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-envelope mr-2"></i>Email Address
                </label>
                <input type="email" id="email" name="email" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Enter your email address"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-lock mr-2"></i>Password
                </label>
                <div class="relative">
                    <input type="password" id="password" name="password" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-12"
                           placeholder="Enter your password">
                    <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-eye" id="password-toggle-icon"></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-600">Remember me</span>
                </label>
                <a href="#" class="text-sm text-blue-600 hover:text-blue-800">Forgot password?</a>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors font-medium">
                <i class="fas fa-sign-in-alt mr-2"></i>Sign In
            </button>
        </form>

        <!-- Features Info -->
        <div class="mt-8 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-medium text-gray-900 mb-3">Access Features:</h3>
            <div class="space-y-2 text-sm text-gray-600">
                <div id="user-features">
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span>Device Lock/Unlock Control</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span>System Uptime & Usage Stats</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span>Device Status Monitoring</span>
                    </div>
                </div>
                <div id="admin-features" class="hidden">
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span>Full Device Management</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span>Security Event Monitoring</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span>Media Capture & Keylogger Access</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span>Remote Commands & Messaging</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-xs text-gray-500">
            <p>© 2024 BramhaLock. Secure Remote Management System.</p>
            <p class="mt-1">Made with ❤️ by Sanket Wanve Technologies</p>
        </div>
    </div>

    <script>
        function switchLoginType(type) {
            // Update buttons
            document.getElementById('user-login-btn').classList.remove('active');
            document.getElementById('admin-login-btn').classList.remove('active');
            document.getElementById(type + '-login-btn').classList.add('active');
            
            // Update hidden input
            document.getElementById('login_type').value = type;
            
            // Update features display
            document.getElementById('user-features').classList.toggle('hidden', type === 'admin');
            document.getElementById('admin-features').classList.toggle('hidden', type === 'user');
        }

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('password-toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Auto-focus email field
        document.getElementById('email').focus();
    </script>
</body>
</html>
