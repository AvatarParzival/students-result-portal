<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $roll_no = $_POST['rollNo'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $semester = $_POST['semester'];
    $dob = $_POST['dob'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    if (!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
    }

    $profile_pic = 'default.png';
    $profile_pic_path = 'uploads/default.png';
    
    if (!empty($_FILES['profilePic']['name']) && $_FILES['profilePic']['error'] === UPLOAD_ERR_OK) {
    
        $file_name = $_FILES['profilePic']['name'];
        $file_tmp = $_FILES['profilePic']['tmp_name'];
        $file_size = $_FILES['profilePic']['size'];
        $file_error = $_FILES['profilePic']['error'];

        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        

        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed_ext)) {
            if ($file_error === 0) {
                if ($file_size <= 2097152) {

                    $unique_id = uniqid('', true);
                    $random_string = bin2hex(random_bytes(4));
                    $profile_pic = "student_{$unique_id}_{$random_string}.{$file_ext}";
                    $profile_pic_path = 'uploads/' . $profile_pic;
                    
                    if (!move_uploaded_file($file_tmp, $profile_pic_path)) {
                        $_SESSION['error'] = "Failed to upload profile picture";
                        header("Location: register.php");
                        exit();
                    }
                } else {
                    $_SESSION['error'] = "File size too large. Maximum size is 2MB";
                    header("Location: register.php");
                    exit();
                }
            } else {
                $_SESSION['error'] = "Error uploading file";
                header("Location: register.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed";
            header("Location: register.php");
            exit();
        }
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format";
        header("Location: register.php");
        exit();
    }

    if (empty($roll_no) || empty($name) || empty($email) || empty($phone) || empty($semester) || empty($_POST['password'])) {
        $_SESSION['error'] = "All fields are required";
        header("Location: register.php");
        exit();
    }

    $check_roll = $conn->prepare("SELECT id FROM students WHERE roll_no = ?");
    $check_roll->bind_param("s", $roll_no);
    $check_roll->execute();
    $check_roll->store_result();
    
    if ($check_roll->num_rows > 0) {
        $_SESSION['error'] = "Roll number already exists";
        header("Location: register.php");
        exit();
    }

    $check_email = $conn->prepare("SELECT id FROM students WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $check_email->store_result();
    
    if ($check_email->num_rows > 0) {
        $_SESSION['error'] = "Email already exists";
        header("Location: register.php");
        exit();
    }

    $check_phone = $conn->prepare("SELECT id FROM students WHERE phone = ?");
    $check_phone->bind_param("s", $phone);
    $check_phone->execute();
    $check_phone->store_result();
    
    if ($check_phone->num_rows > 0) {
        $_SESSION['error'] = "Phone number already exists";
        header("Location: register.php");
        exit();
    }

    $check_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_user->bind_param("s", $roll_no);
    $check_user->execute();
    $check_user->store_result();
    
    if ($check_user->num_rows > 0) {
        $_SESSION['error'] = "Username (roll number) already exists";
        header("Location: register.php");
        exit();
    }

    $conn->begin_transaction();
    
    try {

        $stmt = $conn->prepare("INSERT INTO students (roll_no, name, email, phone, semester, dob, profile_pic, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        

        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ssssisss", $roll_no, $name, $email, $phone, $semester, $dob, $profile_pic_path, $password);
        
        if (!$stmt->execute()) {
            throw new Exception("Error inserting student: " . $stmt->error);
        }
        
        $user_stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
        
        if ($user_stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $user_stmt->bind_param("ss", $roll_no, $password);
        
        if (!$user_stmt->execute()) {
            throw new Exception("Error creating user account: " . $user_stmt->error);
        }
        
        $conn->commit();
        
        $_SESSION['success'] = "Registration successful! You can now login using your roll number: " . $roll_no;
        header("Location: login.php");
        exit();
        
    } catch (Exception $e) {

        $conn->rollback();
        
        if ($profile_pic !== 'default.png' && file_exists($profile_pic_path)) {
            unlink($profile_pic_path);
        }
        
        $_SESSION['error'] = "Registration failed: " . $e->getMessage();
        header("Location: register.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
        <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6">
                <a href="../index.html" class="mr-2 text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <div class="text-center mb-6">
                    <i class="fas fa-user-graduate text-5xl text-blue-600 mb-3"></i>
                    <h2 class="text-2xl font-bold text-gray-800">Student Registration</h2>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                    </div>
                <?php endif; ?>
                
                <form action="register.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="rollNo">Roll Number</label>
                            <input type="text" id="rollNo" name="rollNo" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none" required>
                            <p class="text-sm text-gray-500 mt-1">This will be your username for login</p>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="name">Full Name</label>
                            <input type="text" id="name" name="name" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none" required>
                        </div>
                         <div>
                            <label class="block text-gray-700 font-medium mb-2" for="dob">Date of Birth</label>
                            <input type="date" id="dob" name="dob" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none" required>
                        </div>                     
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="semester">Semester</label>
                            <select id="semester" name="semester" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none" required>
                                <option value="">Select Semester</option>
                                <option value="1">Semester 1</option>
                                <option value="2">Semester 2</option>
                                <option value="3">Semester 3</option>
                                <option value="4">Semester 4</option>
                                <option value="5">Semester 5</option>
                                <option value="6">Semester 6</option>
                                <option value="7">Semester 7</option>
                                <option value="8">Semester 8</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="profilePic">Profile Picture</label>
                            <div class="flex items-center">
                                <label class="form-input flex items-center justify-center w-full px-4 py-2 border rounded-lg cursor-pointer">
                                    <i class="fas fa-upload mr-2 text-gray-500"></i>
                                    <span id="fileLabel">Choose a file</span>
                                    <input type="file" id="profilePic" name="profilePic" class="hidden" accept="uploads/*">
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-4 pt-4">
                        <button type="button" onclick="window.location.href='../index.html'" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Register</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('profilePic').addEventListener('change', function(e) {
            var fileName = e.target.files[0].name;
            document.getElementById('fileLabel').textContent = fileName;
        });
    </script>
</body>
</html>