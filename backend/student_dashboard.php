<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login_student.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT *, 
                       CASE 
                         WHEN marks >= 90 THEN 'A+'
                         WHEN marks >= 80 THEN 'A'
                         WHEN marks >= 70 THEN 'B+'
                         WHEN marks >= 60 THEN 'B'
                         WHEN marks >= 50 THEN 'C'
                         WHEN marks >= 40 THEN 'D'
                         ELSE 'F'
                       END as grade 
                       FROM results WHERE student_id = ? ORDER BY semester DESC, subject ASC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_marks = 0;
$total_subjects = count($results);
$highest_mark = 0;
$lowest_mark = 100;

foreach ($results as $result) {
    $total_marks += $result['marks'];
    
    if ($result['marks'] > $highest_mark) {
        $highest_mark = $result['marks'];
    }
    
    if ($result['marks'] < $lowest_mark && $result['marks'] > 0) {
        $lowest_mark = $result['marks'];
    }
}

if ($total_subjects === 0) {
    $lowest_mark = 0;
}

$average_marks = $total_subjects > 0 ? round($total_marks / $total_subjects, 2) : 0;

if ($average_marks >= 90) $overall_grade = 'A+';
elseif ($average_marks >= 80) $overall_grade = 'A';
elseif ($average_marks >= 70) $overall_grade = 'B+';
elseif ($average_marks >= 60) $overall_grade = 'B';
elseif ($average_marks >= 50) $overall_grade = 'C';
elseif ($average_marks >= 40) $overall_grade = 'D';
else $overall_grade = 'N/A';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
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
        .results-container {
            max-height: 400px;
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
                <h1 class="text-3xl font-bold text-gray-800">Student Dashboard</h1>
                <div class="flex space-x-4">
                    <a href="edit_profile.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center">
                        <i class="fas fa-user-edit mr-2"></i> Edit Profile
                    </a>
                    <button onclick="logout()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </button>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <div class="lg:col-span-1 bg-white rounded-xl shadow-md p-6 dashboard-card results-container">
                    <div class="flex flex-col items-center">
                        <div class="relative mb-4">
                            <?php if (!empty($student['profile_pic']) && file_exists($student['profile_pic']) && $student['profile_pic'] !== 'default.png'): ?>
                                <img src="<?php echo $student['profile_pic']; ?>" alt="Profile" class="w-32 h-32 rounded-full object-cover">
                            <?php else: ?>
                               <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($student['name']); ?>&size=128&background=<?php echo substr(md5($student['id']), 0, 6); ?>&color=fff" alt="Profile" class="w-32 h-32 rounded-full object-cover">
                            <?php endif; ?>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800"><?php echo $student['name']; ?></h2>
                        <p class="text-gray-600">Roll No: <?php echo $student['roll_no']; ?></p>
                        <p class="text-gray-600 mb-4">Semester: <?php echo $student['semester']; ?></p>
                        
                        <div class="w-full mt-4">
                            <div class="flex items-center mb-3">
                                <i class="fas fa-envelope text-blue-600 mr-3"></i>
                                <span class="text-gray-700"><?php echo $student['email']; ?></span>
                            </div>
                            <div class="flex items-center mb-3">
                                <i class="fas fa-phone text-blue-600 mr-3"></i>
                                <span class="text-gray-700"><?php echo $student['phone']; ?></span>
                            </div>
                            <?php if (!empty($student['dob'])): ?>
                            <div class="flex items-center mb-3">
                                <i class="fas fa-birthday-cake text-blue-600 mr-3"></i>
                                <span class="text-gray-700">
                                    <?php echo date('M j, Y', strtotime($student['dob'])); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="lg:col-span-2 bg-white rounded-xl shadow-md p-6 dashboard-card">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Academic Results</h2>

                    
                    
                    <?php if (count($results) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marks</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($results as $i => $result): 
                                        $grade_class = 'text-green-600';
                                        if ($result['grade'] == 'F') {
                                            $grade_class = 'text-red-600';
                                        } elseif (in_array($result['grade'], ['D', 'C', 'C+'])) {
                                            $grade_class = 'text-yellow-600';
                                        } elseif (in_array($result['grade'], ['B', 'B+'])) {
                                            $grade_class = 'text-blue-600';
                                        }
                                        $row_bg = $i % 2 == 0 ? 'bg-white' : 'bg-blue-50';
                                        
                                    ?>
                                        <tr class="result-row <?php echo $row_bg; ?>">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $result['subject']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $result['semester']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $result['marks']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold <?php echo $grade_class; ?>"><?php echo $result['grade']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Performance Summary</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="bg-blue-50 p-4 rounded-lg text-center">
                                    <p class="text-2xl font-bold text-blue-600"><?php echo $average_marks; ?>%</p>
                                    <p class="text-gray-600">Average</p>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg text-center">
                                    <p class="text-2xl font-bold text-green-600"><?php echo $highest_mark; ?></p>
                                    <p class="text-gray-600">Highest</p>
                                </div>
                                <div class="bg-yellow-50 p-4 rounded-lg text-center">
                                    <p class="text-2xl font-bold text-yellow-600"><?php echo $lowest_mark; ?></p>
                                    <p class="text-gray-600">Lowest</p>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-lg text-center">
                                    <p class="text-2xl font-bold text-purple-600"><?php echo $overall_grade; ?></p>
                                    <p class="text-gray-600">Grade</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-book-open text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">No results available yet.</p>
                            <p class="text-sm text-gray-400 mt-2">Your results will appear here once they are published by your instructors.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function logout() {
            if (confirm("Are you sure you want to logout?")) {
                window.location.href = 'logout.php';
            }
        }
        
        function adjustResultsContainer() {
            const resultsContainer = document.querySelector('.results-container');
            const resultsTable = document.querySelector('tbody');
            
            if (resultsTable && resultsTable.children.length > 0) {
                resultsContainer.classList.remove('min-h-[400px]');
            }
        }
        
        document.addEventListener('DOMContentLoaded', adjustResultsContainer);
    </script>
</body>
</html>