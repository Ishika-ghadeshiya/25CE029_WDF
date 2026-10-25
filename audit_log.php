<?php
require_once "auth.php";
require_once "db.php";

if (($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied. Admins only.");
}

// Record an action from another PHP page.
function logAction($conn, $action, $details = "") {
    $userId = isset($_SESSION["user_id"]) ? (int)$_SESSION["user_id"] : null;
    $email = $_SESSION["email"] ?? "unknown";

    $stmt = $conn->prepare(
        "INSERT INTO audit_logs (user_id, user_email, action, details)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("isss", $userId, $email, $action, $details);
    $stmt->execute();
    $stmt->close();
}

// Display recent audit records.
$result = $conn->query(
    "SELECT user_email, action, details, created_at
     FROM audit_logs ORDER BY id DESC LIMIT 100"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audit Log</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 25px; background: #f4f6f9; }
        h1 { color: #243b55; }
        .table-wrap { overflow-x: auto; background: white; padding: 15px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #243b55; color: white; }
        a { color: #2463a6; }
    </style>
</head>
<body>
    <h1>Admin Audit Log</h1>
    <p><a href="admin_dashboard.php">Back to Dashboard</a></p>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>Date and Time</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row["user_email"]) ?></td>
                    <td><?= htmlspecialchars($row["action"]) ?></td>
                    <td><?= htmlspecialchars($row["details"] ?? "") ?></td>
                    <td><?= htmlspecialchars($row["created_at"]) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>