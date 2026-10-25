<?php
session_start();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.html");
    exit;
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === "") {
    exit("Invalid email or password. <a href='login.html'>Try again</a>");
}

$stmt = $conn->prepare(
    "SELECT id, fullname, email, password, role
     FROM registrations
     WHERE email = ?"
);

$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows !== 1) {
    $stmt->close();
    $conn->close();
    exit("Invalid email or password. <a href='login.html'>Try again</a>");
}

$stmt->bind_result($id, $fullname, $dbEmail, $passwordHash, $role);
$stmt->fetch();

$stmt->close();
$conn->close();

if (!password_verify($password, $passwordHash)) {
    exit("Invalid email or password. <a href='login.html'>Try again</a>");
}

session_regenerate_id(true);

$_SESSION["user_id"] = $id;
$_SESSION["fullname"] = $fullname;
$_SESSION["email"] = $dbEmail;
$_SESSION["role"] = $role;
$_SESSION["last_activity"] = time();
if ($role === "admin") {
    header("Location: admin.php");
} else {
    header("Location: dashboard.html");
}
exit;
header("Location: dashboard.html");
exit;
?>