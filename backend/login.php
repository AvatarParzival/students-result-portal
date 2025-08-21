<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT u.*, s.id as student_id FROM users u 
                           JOIN students s ON u.username = s.roll_no 
                           WHERE u.username = ? AND u.role = 'student'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $_SESSION['error'] = 'Student account does not exist.';
        header("Location: login.php");
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['student_id']; 
        $_SESSION['role'] = 'student';
        $_SESSION['username'] = $user['username'];
        

        $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $update_stmt->bind_param("i", $user['id']);
        $update_stmt->execute();
        
        header("Location: student_dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = 'Invalid password.';
        header("Location: login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
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
                    <a href="../index.html" class="mr-2 text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                <div class="text-center mb-6">
                    <i class="fas fa-user-graduate text-5xl text-blue-600 mb-3"></i>
                    <h2 class="text-2xl font-bold text-gray-800">Student Login</h2>
                    <p class="text-gray-600">Access your student dashboard</p>
                </div>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>
                
                <form action="login.php" method="POST" class="space-y-6">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2" for="username">
                            Roll Number
                        </label>
                        <input type="text" id="username" name="username" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none" required>
                        <p class="text-sm text-gray-500 mt-1">Enter your roll number to login</p>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2" for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none" required>
                    </div>
                    <div class="pt-4">
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center justify-center">
                            <i class="fas fa-sign-in-alt mr-2"></i> Login as Student
                        </button>
                    </div>
                </form>
                
                <div class="mt-6 text-center">
                    <p class="text-gray-600">Don't have an account? <a href="register.php" class="text-blue-600 hover:underline">Register here</a></p>
                    <p class="text-gray-600 mt-2">Are you an admin? <a href="admin_login.php" class="text-blue-600 hover:underline">Admin login</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>