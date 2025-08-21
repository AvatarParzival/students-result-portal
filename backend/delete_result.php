<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login_admin.php");
    exit();
}

if (!isset($_POST['result_id']) || !isset($_POST['student_id'])) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: admin_dashboard.php");
    exit();
}

$result_id = intval($_POST['result_id']);
$student_id = intval($_POST['student_id']);

$stmt = $conn->prepare("DELETE FROM results WHERE id = ?");
$stmt->bind_param("i", $result_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Result deleted successfully!";
} else {
    $_SESSION['error'] = "Error deleting result: " . $conn->error;
}

header("Location: edit_student.php?id=" . $student_id);
exit();
?>