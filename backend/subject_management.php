<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login_admin.php");
    exit();
}

$success_message = '';
$error_message = '';

if (isset($_GET['semester'])) {
    $selected_semester = $_GET['semester'] === '' ? null : (int) $_GET['semester'];
} else {
    $selected_semester = 1; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_subject'])) {
        $semester = $_POST['semester'];
        $subject_name = trim($_POST['subject_name']);
        
        if (!empty($subject_name)) {

            $check_stmt = $conn->prepare("SELECT id FROM semester_subjects WHERE semester = ? AND subject_name = ?");
            $check_stmt->bind_param("is", $semester, $subject_name);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows > 0) {
                $error_message = "Subject '$subject_name' already exists for semester $semester!";
            } else {
                $stmt = $conn->prepare("INSERT INTO semester_subjects (semester, subject_name) VALUES (?, ?)");
                $stmt->bind_param("is", $semester, $subject_name);
                
                if ($stmt->execute()) {
                    $success_message = "Subject added successfully!";

                    header("Location: subject_management.php?semester=" . $semester);
                    exit();
                } else {
                    $error_message = "Error adding subject: " . $conn->error;
                }
            }
        } else {
            $error_message = "Subject name cannot be empty!";
        }
    }
    
    if (isset($_POST['delete_subject'])) {
        $subject_id = $_POST['subject_id'];
        $semester = $_POST['semester'];
        
        $check_stmt = $conn->prepare("SELECT COUNT(*) as result_count FROM results WHERE subject = (SELECT subject_name FROM semester_subjects WHERE id = ?)");
        $check_stmt->bind_param("i", $subject_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result()->fetch_assoc();
        
        if ($result['result_count'] > 0) {
            $error_message = "Cannot delete subject with existing results!";
        } else {
            $stmt = $conn->prepare("DELETE FROM semester_subjects WHERE id = ?");
            $stmt->bind_param("i", $subject_id);
            
            if ($stmt->execute()) {
                $success_message = "Subject deleted successfully!";
                header("Location: subject_management.php?semester=" . $semester);
                exit();
            } else {
                $error_message = "Error deleting subject: " . $conn->error;
            }
        }
    }
}

$subjects_by_semester = [];
$stmt = $conn->prepare("SELECT id, semester, subject_name FROM semester_subjects ORDER BY semester, subject_name");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $semester = $row['semester'];
    if (!isset($subjects_by_semester[$semester])) {
        $subjects_by_semester[$semester] = [];
    }
    $subjects_by_semester[$semester][] = $row;
}

$semester_counts = [];
foreach ($subjects_by_semester as $semester => $subjects) {
    $semester_counts[$semester] = count($subjects);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f9ff;
        }
        .dashboard-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .subject-item {
            transition: background-color 0.2s ease;
        }
        .subject-item:hover {
            background-color: #f9fafb;
        }

        .sem-container{
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            min-height: 370px;
            height: auto;
            overflow: hidden;
        }
        .sem-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-blue-50">
    <div class="min-h-screen py-8 px-4">
        <div class="max-w-4xl mx-auto">

            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $error_message; ?></span>
                    <button onclick="this.parentElement.style.display='none'" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $success_message; ?></span>
                    <button onclick="this.parentElement.style.display='none'" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>
            
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Subject Management</h1>
                <a href="admin_dashboard.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-6 dashboard-card mb-8 ">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Add New Subject</h2>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="add_subject" value="1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 ">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="semester">Semester</label>
                            <select id="semester" name="semester" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <option value="">Select Semester</option>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($selected_semester == $i) ? 'selected' : ''; ?>>
                                        Semester <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="subject_name">Subject Name</label>
                            <input type="text" id="subject_name" name="subject_name" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., Mathematics, Physics..." required>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center">
                            <i class="fas fa-plus mr-2"></i> Add Subject
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 sem-container ">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4 md:mb-0 ">Manage Subjects</h2>
                    
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                        <form method="GET" class="flex items-center">
                            <label for="semester_filter" class="mr-2 text-gray-700">Filter by Semester:</label>
                            <select id="semester_filter" name="semester" onchange="this.form.submit()" class="form-input px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All Semesters</option>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($selected_semester == $i) ? 'selected' : ''; ?>>
                                        Semester <?php echo $i; ?> (<?php echo $semester_counts[$i] ?? 0; ?>)
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </form>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <?php if (count($subjects_by_semester) > 0): ?>
                        <?php 
                        $display_semesters = $selected_semester ? [$selected_semester] : array_keys($subjects_by_semester);
                        ?>
                        
                        <?php foreach ($display_semesters as $semester): ?>
                            <?php if (isset($subjects_by_semester[$semester])): ?>
                                <div class="border border-gray-200 rounded-lg overflow-hidden">
                                    <div class="flex justify-between items-center p-4 bg-gray-50">
                                        <h3 class="text-lg font-semibold">
                                            Semester <?php echo $semester; ?>
                                            <span class="text-sm font-normal text-gray-500 ml-2">(<?php echo count($subjects_by_semester[$semester]); ?> subjects)</span>
                                        </h3>
                                    </div>
                                    <div class="p-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                            <?php foreach ($subjects_by_semester[$semester] as $subject): ?>
                                                <div class="flex justify-between items-center bg-gray-50 p-3 rounded subject-item">
                                                    <span class="font-medium"><?php echo $subject['subject_name']; ?></span>
                                                    <form method="POST" class="inline-block">
                                                        <input type="hidden" name="delete_subject" value="1">
                                                        <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                                        <input type="hidden" name="semester" value="<?php echo $semester; ?>">
                                                        <button type="submit" class="text-red-600 hover:text-red-900 text-sm" onclick="return confirm('Are you sure you want to delete this subject?');">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-4">No subjects found. Add your first subject using the form above.</p>
                    <?php endif; ?>
                </div>
                
                <?php if ($selected_semester && empty($subjects_by_semester[$selected_semester])): ?>
                    <p class="text-gray-500 text-center py-20">No subjects found for Semester <?php echo $selected_semester; ?>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        setTimeout(() => {
            const messages = document.querySelectorAll('.bg-red-100, .bg-green-100');
            messages.forEach(message => {
                message.style.display = 'none';
            });
        }, 5000);

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const semester = urlParams.get('semester');
            if (semester) {
                document.getElementById('semester').value = semester;
            }
        });
    </script>
</body>
</html>