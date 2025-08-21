<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $phone = $_POST['phone'];
    $dob = $_POST['dob'];

    $profile_pic_path = $student['profile_pic'];
    if (!empty($_FILES['profilePic']['name']) && $_FILES['profilePic']['error'] === UPLOAD_ERR_OK) {

        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        $file_name = $_FILES['profilePic']['name'];
        $file_tmp = $_FILES['profilePic']['tmp_name'];
        $file_size = $_FILES['profilePic']['size'];
        $file_error = $_FILES['profilePic']['error'];

        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];


        
        if (in_array($file_ext, $allowed_ext)) {
            if ($file_error === 0) {
                if ($file_size <= 2097152) {

                    if ($profile_pic_path !== 'uploads/default.png' && file_exists($profile_pic_path)) {
                        unlink($profile_pic_path);
                    }
                    
                    $unique_id = uniqid('', true);
                    $random_string = bin2hex(random_bytes(4));
                    $profile_pic = "student_{$student['id']}_{$unique_id}_{$random_string}.{$file_ext}";
                    $profile_pic_path = 'uploads/' . $profile_pic;
                    
                    if (!move_uploaded_file($file_tmp, $profile_pic_path)) {
                        $_SESSION['error'] = "Failed to upload profile picture";
                        header("Location: edit_profile.php");
                        exit();
                    }
                } else {
                    $_SESSION['error'] = "File size too large. Maximum size is 2MB";
                    header("Location: edit_profile.php");
                    exit();
                }
            } else {
                $_SESSION['error'] = "Error uploading file";
                header("Location: edit_profile.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed";
            header("Location: edit_profile.php");
            exit();
        }
    }

    $stmt = $conn->prepare("UPDATE students SET phone = ?, dob = ?, profile_pic = ? WHERE id = ?");
    $stmt->bind_param("sssi", $phone, $dob, $profile_pic_path, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: student_dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating profile: " . $conn->error;
        header("Location: edit_profile.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Student Dashboard</title>
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
    <div class="min-h-screen py-8 px-4">
        <div class="max-w-2xl mx-auto">
            <div class="mb-6">
                <a href="student_dashboard.php" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>
            
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Edit Profile</h2>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <form action="edit_profile.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <div class="flex flex-col items-center mb-6">
                            <div class="relative mb-4">
                                <img id="profilePreview" src="<?php echo (!empty($student['profile_pic']) && file_exists($student['profile_pic']) && $student['profile_pic'] !== 'default.png') ? $student['profile_pic'] : 'https://ui-avatars.com/api/?name=' . urlencode($student['name']) . '&size=128&background=' . substr(md5($student['id']), 0, 6) . '&color=fff'; ?>" alt="Profile" class="w-32 h-32 rounded-full object-cover">
                                <label for="profilePic" class="absolute bottom-0 right-0 bg-blue-600 text-white p-2 rounded-full hover:bg-blue-700 cursor-pointer">
                                    <i class="fas fa-camera"></i>
                                    <input type="file" id="profilePic" name="profilePic" class="hidden" accept="image/*" onchange="previewImage(this)">
                                </label>
                            </div>
                            <p class="text-sm text-gray-500">Click camera icon to change photo</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 font-medium mb-2" for="name">Full Name</label>
                                <input type="text" id="name" class="form-input w-full px-4 py-2 border rounded-lg bg-gray-100" value="<?php echo htmlspecialchars($student['name']); ?>" readonly>
                                <p class="text-sm text-gray-500 mt-1">Name cannot be changed</p>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2" for="rollNo">Roll Number</label>
                                <input type="text" id="rollNo" class="form-input w-full px-4 py-2 border rounded-lg bg-gray-100" value="<?php echo htmlspecialchars($student['roll_no']); ?>" readonly>
                                <p class="text-sm text-gray-500 mt-1">Roll number cannot be changed</p>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2" for="email">Email Address</label>
                                <input type="email" id="email" class="form-input w-full px-4 py-2 border rounded-lg bg-gray-100" value="<?php echo htmlspecialchars($student['email']); ?>" readonly>
                                <p class="text-sm text-gray-500 mt-1">Email cannot be changed</p>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2" for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($student['phone']); ?>" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2" for="dob">Date of Birth</label>
                                <input type="date" id="dob" name="dob" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo $student['dob']; ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2" for="semester">Semester</label>
                                <input type="text" id="semester" class="form-input w-full px-4 py-2 border rounded-lg bg-gray-100" value="Semester <?php echo $student['semester']; ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-4 pt-4">
                            <a href="student_dashboard.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
                            <button type="submit" name="update_profile" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>