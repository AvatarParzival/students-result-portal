<?php
require 'db.php';
$semester = $_GET['semester'];
$stmt = $conn->prepare("SELECT id, subject_name as name FROM semester_subjects WHERE semester = ? ORDER BY subject_name");
$stmt->bind_param("i", $semester);
$stmt->execute();
$result = $stmt->get_result();
$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}
echo json_encode($subjects);
?>