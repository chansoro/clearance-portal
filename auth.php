<?php
session_start();
include "db.php";

$role     = isset($_POST['role']) ? trim($_POST['role']) : '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if ($role === "student") {
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_no = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $student = $result->fetch_assoc();
        $_SESSION['role'] = "student";
        $_SESSION['student_id'] = $student['student_id'];
        $_SESSION['name'] = $student['first_name'] . " " . $student['last_name'];
        header("Location: student.php");
        exit;
    }
} else {
    $stmt = $conn->prepare("SELECT * FROM requirements WHERE staff_email = ? AND staff_password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $office = $result->fetch_assoc();
        $_SESSION['role'] = "staff";
        $_SESSION['office_name'] = $office['office_name'];
        $_SESSION['staff_username'] = $username;
        header("Location: staff.php");
        exit;
    }
}

header("Location: index.php?error=1");
exit;
?>
