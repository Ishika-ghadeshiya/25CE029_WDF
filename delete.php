
<?php
require_once __DIR__ . "/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit("Invalid student ID. <a href='admin.php'>Back</a>");
}

$stmt = $conn->prepare("DELETE FROM registrations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$deleted = $stmt->affected_rows;
$stmt->close();
$conn->close();

if ($deleted > 0) {
    header("Location: admin.php?deleted=1");
    exit;
}

http_response_code(404);
echo "Student record not found. <a href='admin.php'>Back to dashboard</a>";
?>
