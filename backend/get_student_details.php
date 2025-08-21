<?php
require 'db.php';
$student_id = $_GET['student_id'];
$stmt = $conn->prepare("SELECT name FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
echo json_encode($student);
?>