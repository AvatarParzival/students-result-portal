<?php
require 'db.php';
$student_id = $_GET['student_id'];
$stmt = $conn->prepare("SELECT subject_id FROM student_subjects WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$subject_ids = [];
while ($row = $result->fetch_assoc()) {
    $subject_ids[] = $row['subject_id'];
}
echo json_encode($subject_ids);
?>