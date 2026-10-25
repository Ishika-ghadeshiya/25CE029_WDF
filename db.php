<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli("localhost", "root", "", "students");
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit("Database connection failed. Check XAMPP MySQL and database settings.");
}
?>