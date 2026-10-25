
<?php
require_once __DIR__ . "/db.php";

$search = trim($_GET["search"] ?? "");

if ($search !== "") {
    $like = "%" . $search . "%";

    if (ctype_digit($search)) {
        $stmt = $conn->prepare(
            "SELECT id, fullname, email, phone, department, year, gender, created_at
             FROM registrations
             WHERE id = ? OR fullname LIKE ?
             ORDER BY id ASC"
        );
        $id = (int)$search;
        $stmt->bind_param("is", $id, $like);
    } else {
        $stmt = $conn->prepare(
            "SELECT id, fullname, email, phone, department, year, gender, created_at
             FROM registrations
             WHERE fullname LIKE ?
             ORDER BY id ASC"
        );
        $stmt->bind_param("s", $like);
    }

    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query(
        "SELECT id, fullname, email, phone, department, year, gender, created_at
         FROM registrations ORDER BY id ASC"
    );
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StudentHub Admin Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #222; }
        h2 { color: #0056b3; }
        form { margin: 18px 0; }
        input, button { padding: 9px; }
        table { border-collapse: collapse; width: 100%; margin-top: 18px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background: #0056b3; color: white; }
        tr:nth-child(even) { background: #f5f7fa; }
        a, button { cursor: pointer; }
        .edit { color: #174ea6; }
        .delete { color: #c62828; border: 0; background: none; text-decoration: underline; }
    </style>
</head>
<body>
    <h2>Student Records (Admin Dashboard)</h2>

    <form method="get" action="admin.php">
        <input type="text" name="search" placeholder="Search by name or ID"
               value="<?= h($search) ?>">
        <button type="submit">Search</button>
        <a href="admin.php">Reset</a>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>Department</th>
                <th>Year</th>
                <th>Gender</th>
                <th>Registration Time</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= h($row["id"]) ?></td>
                    <td><?= h($row["fullname"]) ?></td>
                    <td><?= h($row["email"]) ?></td>
                    <td><?= h($row["phone"]) ?></td>
                    <td><?= h($row["department"]) ?></td>
                    <td><?= h($row["year"]) ?></td>
                    <td><?= h($row["gender"]) ?></td>
                    <td><?= h($row["created_at"]) ?></td>
                    <td>
                        <a class="edit" href="edit.php?id=<?= (int)$row["id"] ?>">Edit</a>
                        <form method="post" action="delete.php"
                              onsubmit="return confirm('Delete this student record?');">
                            <input type="hidden" name="id" value="<?= (int)$row["id"] ?>">
                            <button class="delete" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="9">No student records found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <p><a href="register.html">Add New Registration</a></p>
</body>
</html>
<?php
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>
