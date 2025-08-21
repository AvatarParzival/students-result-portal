<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login_admin.php");
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No student ID provided.";
    header("Location: admin_dashboard.php");
    exit();
}

$student_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    $_SESSION['error'] = "Student not found.";
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_results'])) {
        if (!empty($_POST['selected_results'])) {
            $placeholders = implode(',', array_fill(0, count($_POST['selected_results']), '?'));
            $stmt = $conn->prepare("DELETE FROM results WHERE id IN ($placeholders)");

            $types = str_repeat('i', count($_POST['selected_results']));
            $stmt->bind_param($types, ...$_POST['selected_results']);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Selected results deleted successfully!";
            } else {
                $_SESSION['error'] = "Error deleting results: " . $conn->error;
            }
        } else {
            $_SESSION['error'] = "No results selected for deletion.";
        }
        
        header("Location: edit_student.php?id=" . $student_id);
        exit();
    }

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $semester = intval($_POST['semester']);

    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    }
    
    if ($semester < 1 || $semester > 8) {
        $errors[] = "Semester must be between 1 and 8.";
    }

    $check_email = $conn->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
    $check_email->bind_param("si", $email, $student_id);
    $check_email->execute();
    $check_email->store_result();
    
    if ($check_email->num_rows > 0) {
        $errors[] = "Email already exists.";
    }

    $check_phone = $conn->prepare("SELECT id FROM students WHERE phone = ? AND id != ?");
    $check_phone->bind_param("si", $phone, $student_id);
    $check_phone->execute();
    $check_phone->store_result();
    
    if ($check_phone->num_rows > 0) {
        $errors[] = "Phone number already exists.";
    }

    $profile_pic = $student['profile_pic'];
    if (!empty($_FILES['profilePic']['name'])) {
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profilePic']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Only JPG, PNG, and GIF files are allowed.";
        } else {
            if ($profile_pic !== 'default.png' && file_exists($profile_pic)) {
                unlink($profile_pic);
            }
            
            $file_extension = pathinfo($_FILES['profilePic']['name'], PATHINFO_EXTENSION);
            $profile_pic = 'uploads/' . time() . '_' . $student_id . '.' . $file_extension;
            move_uploaded_file($_FILES['profilePic']['tmp_name'], $profile_pic);
        }
    }
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE students SET name = ?, email = ?, phone = ?, semester = ?, profile_pic = ? WHERE id = ?");
        $stmt->bind_param("sssisi", $name, $email, $phone, $semester, $profile_pic, $student_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Student updated successfully!";
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $errors[] = "Error updating student: " . $conn->error;
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

$stmt = $conn->prepare("SELECT * FROM results WHERE student_id = ? ORDER BY semester, subject");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f9ff;
        }
        .select-all-checkbox {
            margin-right: 8px;
        }
        .result-checkbox {
            margin-right: 12px;
        }
    </style>
</head>
<body class="bg-blue-50">
    <div class="min-h-screen py-8 px-4">
        <div class="max-w-4xl mx-auto">
            <div class="mb-6">
                <a href="admin_dashboard.php" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>
            
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Edit Student</h2>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <form action="edit_student.php?id=<?php echo $student_id; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <div class="flex flex-col items-center mb-6">
                            <div class="relative mb-4">
                                <?php if (!empty($student['profile_pic']) && file_exists($student['profile_pic'])): ?>
                                    <img id="profilePreview" src="<?php echo $student['profile_pic']; ?>" alt="Profile" class="w-32 h-32 rounded-full object-cover">
                                <?php else: ?>
                                    <img id="profilePreview" src="https://ui-avatars.com/api/?name=<?php echo urlencode($student['name']); ?>&size=128&background=3b82f6&color=fff" alt="Profile" class="w-32 h-32 rounded-full object-cover">
                                <?php endif; ?>
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
                                <input type="text" id="name" name="name" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2" for="rollNo">Roll Number</label>
                                <input type="text" id="rollNo" class="form-input w-full px-4 py-2 border rounded-lg bg-gray-100" value="<?php echo htmlspecialchars($student['roll_no']); ?>" readonly>
                                <p class="text-sm text-gray-500 mt-1">Roll number cannot be changed</p>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2" for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2" for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($student['phone']); ?>" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2" for="semester">Semester</label>
                                <select id="semester" name="semester" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Select Semester</option>
                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $student['semester'] == $i ? 'selected' : ''; ?>>Semester <?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2" for="created_at">Registration Date</label>
                                <input type="text" id="created_at" class="form-input w-full px-4 py-2 border rounded-lg bg-gray-100" value="<?php echo date('M j, Y', strtotime($student['created_at'])); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-4 pt-4">
                            <a href="admin_dashboard.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Update Student</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 mt-8">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Student Results</h2>
                    
                    <?php if (count($results) > 0): ?>
                        <div class="mt-4 md:mt-0">
                            <button type="button" onclick="toggleSelectAll()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 mr-2">
                                <i class="fas fa-check-square mr-2"></i> Select All
                            </button>
                            <button type="button" onclick="deleteSelected()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                <i class="fas fa-trash mr-2"></i> Delete Selected
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (count($results) > 0): ?>
                    <form id="deleteResultsForm" action="edit_student.php?id=<?php echo $student_id; ?>" method="POST">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <input type="checkbox" id="selectAll" class="select-all-checkbox" onchange="toggleAllCheckboxes(this)">
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marks</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($results as $result): 
                                        $grade_class = 'text-green-600';
                                        if ($result['grade'] == 'F') {
                                            $grade_class = 'text-red-600';
                                        } elseif (in_array($result['grade'], ['D', 'C', 'C+'])) {
                                            $grade_class = 'text-yellow-600';
                                        } elseif (in_array($result['grade'], ['B', 'B+'])) {
                                            $grade_class = 'text-blue-600';
                                        }
                                    ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="checkbox" name="selected_results[]" value="<?php echo $result['id']; ?>" class="result-checkbox">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $result['subject']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $result['semester']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $result['marks']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold <?php echo $grade_class; ?>"><?php echo $result['grade']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <form action="delete_result.php" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this result?');">
                                                    <input type="hidden" name="result_id" value="<?php echo $result['id']; ?>">
                                                    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <input type="hidden" name="delete_results" value="1">
                    </form>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-book-open text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">No results available for this student.</p>
                    </div>
                <?php endif; ?>
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
        
        function toggleAllCheckboxes(source) {
            var checkboxes = document.querySelectorAll('.result-checkbox');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
        
        function toggleSelectAll() {
            var selectAll = document.getElementById('selectAll');
            selectAll.checked = !selectAll.checked;
            toggleAllCheckboxes(selectAll);
        }
        
        function deleteSelected() {
            var selectedCount = document.querySelectorAll('.result-checkbox:checked').length;
            
            if (selectedCount === 0) {
                alert('Please select at least one result to delete.');
                return;
            }
            
            if (confirm(`Are you sure you want to delete ${selectedCount} selected result(s)?`)) {
                document.getElementById('deleteResultsForm').submit();
            }
        }
    </script>
</body>
</html>