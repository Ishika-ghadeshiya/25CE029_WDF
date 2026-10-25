<?php
require_once "auth.php";
require_once "db.php";

// Only admins may access this dashboard.
if (($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied. Admins only.");
}

function getCount($conn, $sql) {
    $result = $conn->query($sql);
    return (int)$result->fetch_row()[0];
}

$totalUsers = getCount($conn, "SELECT COUNT(*) FROM registrations");
$totalStudents = getCount(
    $conn,
    "SELECT COUNT(*) FROM registrations WHERE role = 'student'"
);
$totalEvents = getCount($conn, "SELECT COUNT(*) FROM events");

$latest = $conn->query(
    "SELECT fullname, email, department, year, created_at
     FROM registrations
     ORDER BY created_at DESC
     LIMIT 5"
);

// Record dashboard access.
$userId = (int)($_SESSION["user_id"] ?? 0);
$email = $_SESSION["email"] ?? "unknown";
$action = "Dashboard accessed";
$details = "Admin opened the dashboard";

$stmt = $conn->prepare(
    "INSERT INTO audit_logs (user_id, user_email, action, details)
     VALUES (?, ?, ?, ?)"
);
$stmt->bind_param("isss", $userId, $email, $action, $details);
$stmt->execute();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #222;
        }
        header {
            background: #243b55;
            color: white;
            padding: 20px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        header h1 { margin: 0; font-size: 24px; }
        nav { display: flex; flex-wrap: wrap; gap: 15px; }
        nav a { color: white; text-decoration: none; }
        nav a:hover { text-decoration: underline; }
        main { max-width: 1100px; margin: 25px auto; padding: 0 18px; }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }
        .card, .panel {
            background: white;
            padding: 22px;
            border-radius: 9px;
            box-shadow: 0 2px 8px #ddd;
        }
        .card h3 { margin-top: 0; color: #555; }
        .number { font-size: 32px; font-weight: bold; color: #2463a6; }
        .panel { margin-top: 25px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #eef2f7; }
        @media (max-width: 650px) {
            .stats { grid-template-columns: 1fr; }
            header { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body>
<header>
    <h1>Admin Dashboard</h1>

    <nav>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="students.php">Manage Students</a>
        <a href="events.php">Manage Events</a>
        <a href="audit_log.php">Audit Log</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
    <h2>Welcome, <?= htmlspecialchars($_SESSION["fullname"] ?? "Admin") ?></h2>
    <p>Overview of users, students, events, and recent registrations.</p>

    <section class="stats">
        <div class="card">
            <h3>Total Users</h3>
            <div class="number"><?= $totalUsers ?></div>
        </div>
        <div class="card">
            <h3>Total Students</h3>
            <div class="number"><?= $totalStudents ?></div>
        </div>
        <div class="card">
            <h3>Total Events</h3>
            <div class="number"><?= $totalEvents ?></div>
        </div>
    </section>

    <section class="panel">
        <h2>Latest Registrations</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Year</th>
                    <th>Registered At</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($latest->num_rows === 0): ?>
                <tr><td colspan="5">No registrations found.</td></tr>
            <?php else: ?>
                <?php while ($row = $latest->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row["fullname"]) ?></td>
                        <td><?= htmlspecialchars($row["email"]) ?></td>
                        <td><?= htmlspecialchars($row["department"]) ?></td>
                        <td><?= htmlspecialchars($row["year"]) ?></td>
                        <td><?= htmlspecialchars($row["created_at"]) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>