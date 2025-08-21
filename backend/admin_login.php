<?php
session_start();
require 'db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            
            header("Location: admin_dashboard.php");
            exit();
        }
    }
    $_SESSION['error'] = 'Invalid username or password';
    header("Location: admin_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f9ff;
        }
    </style>
</head>
<body class="bg-blue-50">
    <div class="min-h-screen py-12 px-4 flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6">
                <a href="../index.html" class="mr-2 text-gray-800 hover:text-blue-600">
                   <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <div class="text-center mb-6">
                    <i class="fas fa-user-shield text-5xl text-gray-800 mb-3"></i>
                    <h2 class="text-2xl font-bold text-gray-800">Admin Login</h2>
                    <p class="text-gray-600">Access the administration panel</p>
                </div>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>
                
                <form action="admin_login.php" method="POST" class="space-y-6">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2" for="username">
                            Username
                        </label>
                        <input type="text" id="username" name="username" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none" required>
                        <p class="text-sm text-gray-500 mt-1">Enter your admin username</p>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2" for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none" required>
                    </div>
                    <div class="pt-4">
                        <button type="submit" class="w-full px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 flex items-center justify-center">
                            <i class="fas fa-sign-in-alt mr-2"></i> Login as Admin
                        </button>
                    </div>
                </form>
                <div class="mt-6 text-center">
                    <p class="text-gray-600">Are you a student? <a href="login.php" class="text-blue-600 hover:underline">Student login</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>