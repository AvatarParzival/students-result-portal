<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login_admin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_subjects' && isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);
    
    $stmt = $conn->prepare("SELECT subject FROM results WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row['subject'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($subjects);
    exit();
}

$subjects_by_semester = [];

try {
    $stmt = $conn->prepare("SELECT semester, subject_name FROM semester_subjects ORDER BY semester, subject_name");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $db_subjects = [];
    while ($row = $result->fetch_assoc()) {
        $semester = $row['semester'];
        if (!isset($db_subjects[$semester])) {
            $db_subjects[$semester] = [];
        }
        $db_subjects[$semester][] = $row['subject_name'];
    }

    if (!empty($db_subjects)) {
        $subjects_by_semester = $db_subjects;
    }
} catch (Exception $e) {
    error_log("Error fetching subjects: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_result'])) {
    $student_id = $_POST['student_id'];
    $subject = $_POST['subject'];
    $marks = $_POST['marks'];

    $stmt = $conn->prepare("SELECT semester FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student_result = $stmt->get_result()->fetch_assoc();
    $semester = $student_result['semester'];

    $check_stmt = $conn->prepare("SELECT id FROM results WHERE student_id = ? AND subject = ?");
    $check_stmt->bind_param("is", $student_id, $subject);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $_SESSION['error'] = "Result already exists for this student in the subject: $subject";
        header("Location: admin_dashboard.php");
        exit();
    }

    if ($marks >= 90) $grade = 'A+';
    elseif ($marks >= 80) $grade = 'A';
    elseif ($marks >= 70) $grade = 'B+';
    elseif ($marks >= 60) $grade = 'B';
    elseif ($marks >= 50) $grade = 'C';
    elseif ($marks >= 40) $grade = 'D';
    else $grade = 'F';

    $stmt = $conn->prepare("INSERT INTO results (student_id, subject, marks, semester, grade) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isiss", $student_id, $subject, $marks, $semester, $grade);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Result uploaded successfully!";
    } else {
        $_SESSION['error'] = "Error uploading result: " . $conn->error;
    }
    
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_student'])) {
    $student_id = $_POST['student_id'];
    
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Student deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting student: " . $conn->error;
    }
    
    header("Location: admin_dashboard.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM students");
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$total_students = count($students);
$stmt = $conn->prepare("SELECT COUNT(DISTINCT subject) as total_courses FROM results");
$stmt->execute();
$courses_result = $stmt->get_result()->fetch_assoc();
$total_courses = $courses_result['total_courses'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
        .semester-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            background-color: #e5e7eb;
            color: #4b5563;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .student-table-container {
            max-height: 250px;
            overflow-y: auto;
            position: relative;
        }

        .student-table-container thead th {
            position: sticky;
            top: 0;
            background-color: #f9fafb;
            z-index: 10;
            box-shadow: 0 1px 0 #e5e7eb;
        }

        .student-table-container::-webkit-scrollbar {
            width: 8px;
        }
        .student-table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        .student-table-container::-webkit-scrollbar-thumb {
            background: #c5c5c5;
            border-radius: 4px;
        }
        .student-table-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>
<body class="bg-blue-50">
    <div class="min-h-screen py-8 px-4">
        <div class="max-w-6xl mx-auto">

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
            
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
                <div class="flex space-x-4">
                    <a href="subject_management.php" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center">
                        <i class="fas fa-book mr-2"></i> Manage Subjects
                    </a>
                    <button onclick="logout()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </button>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <div class="bg-white rounded-xl shadow-md p-6 dashboard-card">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-full mr-4">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-600">Total Students</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $total_students; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 dashboard-card">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-3 rounded-full mr-4">
                            <i class="fas fa-book text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-600">Courses</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $total_courses; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 dashboard-card">
                    <div class="flex items-center">
                        <div class="bg-purple-100 p-3 rounded-full mr-4">
                            <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-600">Students with Results</p>
                            <p class="text-2xl font-bold text-gray-800">
                                <?php 
                                $stmt = $conn->prepare("SELECT COUNT(DISTINCT student_id) as students_with_results FROM results");
                                $stmt->execute();
                                $result = $stmt->get_result()->fetch_assoc();
                                echo $result['students_with_results']; 
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-6 mb-8 dashboard-card">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4 md:mb-0">Student Management</h2>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                        <div class="relative">
                            <input type="text" id="searchStudents" placeholder="Search students..." class="pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>
                </div>
                
                <div class="student-table-container">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roll No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="studentsTable">
                            <?php foreach ($students as $student): ?>
                                <tr class="student-row">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <?php if (!empty($student['profile_pic']) && file_exists($student['profile_pic'])): ?>
                                                <img class="h-10 w-10 rounded-full object-cover" src="<?php echo $student['profile_pic']; ?>" alt="Profile">
                                            <?php else: ?>
                                                <img class="h-10 w-10 rounded-full" src="https://ui-avatars.com/api/?name=<?php echo urlencode($student['name']); ?>&background=<?php echo substr(md5($student['id']), 0, 6); ?>&color=fff" alt="Profile">
                                            <?php endif; ?>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo $student['name']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $student['roll_no']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="semester-badge">Semester <?php echo $student['semester']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $student['email']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $student['phone']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="admin_dashboard.php" method="POST" class="inline-block">
                                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                            <button type="submit" name="delete_student" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this student?');">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (count($students) === 0): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">No students found.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-6 dashboard-card">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Upload Results</h2>
                
                <form action="admin_dashboard.php" method="POST" class="space-y-6" id="resultForm">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="student_id">Student</label>
                            <select id="student_id" name="student_id" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required onchange="updateSubjects()">
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student): 
                                    $stmt = $conn->prepare("SELECT subject FROM results WHERE student_id = ?");
                                    $stmt->bind_param("i", $student['id']);
                                    $stmt->execute();
                                    $existing_subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                    $existing_subjects = array_column($existing_subjects, 'subject');
                                    $available_subjects = array_diff($subjects_by_semester[$student['semester']] ?? [], $existing_subjects);
                              
                                if (count($available_subjects) > 0):
                                ?>
                                    <option value="<?php echo $student['id']; ?>" data-semester="<?php echo $student['semester']; ?>" data-available="<?php echo count($available_subjects); ?>">
                                        <?php echo $student['name']; ?> (<?php echo $student['roll_no']; ?>)
                                    </option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="subject">Subject</label>
                            <select id="subject" name="subject" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required disabled>
                                <option value="">Select Student First</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="marks">Marks (0-100)</label>
                            <input type="number" id="marks" name="marks" min="0" max="100" class="form-input w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter marks" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="semester_display">Semester</label>
                            <input type="text" id="semester_display" class="form-input w-full px-4 py-2 border rounded-lg bg-gray-100 text-gray-500" value="-" readonly>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" name="upload_result" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center" id="submitButton">
                            <i class="fas fa-upload mr-2"></i> Upload Result
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function logout() {
            if (confirm("Are you sure you want to logout?")) {
                window.location.href = 'logout.php';
            }
        }
        
        document.getElementById('searchStudents').addEventListener('input', function() {
            var searchText = this.value.toLowerCase();
            var rows = document.querySelectorAll('.student-row');
            
            rows.forEach(function(row) {
                var name = row.querySelector('td:first-child').textContent.toLowerCase();
                var rollNo = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                var email = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                
                if (name.includes(searchText) || rollNo.includes(searchText) || email.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        function updateSubjects() {
            var studentSelect = document.getElementById('student_id');
            var subjectSelect = document.getElementById('subject');
            var semesterDisplay = document.getElementById('semester_display');
            var submitButton = document.getElementById('submitButton');
            var selectedOption = studentSelect.options[studentSelect.selectedIndex];
            
            if (studentSelect.value === "") {
                subjectSelect.innerHTML = '<option value="">Select Student First</option>';
                subjectSelect.disabled = true;
                semesterDisplay.value = "-";
                submitButton.disabled = false;
                return;
            }
            
            var semester = selectedOption.getAttribute('data-semester');
            var availableSubjects = parseInt(selectedOption.getAttribute('data-available'));
            semesterDisplay.value = "Semester " + semester;
            
            subjectSelect.innerHTML = '';

            var subjects = <?php echo json_encode($subjects_by_semester); ?>;
            
            if (subjects[semester] && availableSubjects > 0) {
                subjectSelect.disabled = false;

                var studentId = studentSelect.value;
                fetch('admin_dashboard.php?action=get_subjects&student_id=' + studentId)
                    .then(response => response.json())
                    .then(existingSubjects => {
                        subjects[semester].forEach(function(subject) {
                            if (!existingSubjects.includes(subject)) {
                                var option = document.createElement('option');
                                option.value = subject;
                                option.textContent = subject;
                                subjectSelect.appendChild(option);
                            }
                        });
                        
                        if (subjectSelect.options.length === 0) {
                            subjectSelect.innerHTML = '<option value="">No subjects available</option>';
                            subjectSelect.disabled = true;
                            submitButton.disabled = true;
                        }
                    });
            } else {
                subjectSelect.innerHTML = '<option value="">No subjects available</option>';
                subjectSelect.disabled = true;
                submitButton.disabled = true;
            }
        }

        document.getElementById('resultForm').addEventListener('submit', function(e) {
            var subjectSelect = document.getElementById('subject');
            if (subjectSelect.disabled || subjectSelect.value === "") {
                e.preventDefault();
                alert("Please select a valid subject before submitting.");
            }
        });
    </script>
</body>
</html>