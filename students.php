
<?php
require_once "db.php";

$message = "";
$editStudent = null;

// ADD STUDENT
if (isset($_POST["add"])) {
    $name = trim($_POST["fullname"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $department = trim($_POST["department"]);
    $year = trim($_POST["year"]);

    if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || $phone === "" || $department === "" || $year === "") {
        $message = "Please enter valid details in all fields.";
    } else {
        try {
            $stmt = $conn->prepare(
                "INSERT INTO registrations
                (fullname, email, phone, department, year, password)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            // Placeholder password hash for records created by this module.
            // Students should set a real password through registration/reset.
            $temporaryHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

            $stmt->bind_param(
                "ssssss",
                $name, $email, $phone, $department, $year, $temporaryHash
            );
            $stmt->execute();
            $stmt->close();

            header("Location: students.php?message=added");
            exit;
        } catch (mysqli_sql_exception $e) {
            $message = ($e->getCode() == 1062)
                ? "This email already exists."
                : "Failed to add student.";
        }
    }
}

// UPDATE STUDENT
if (isset($_POST["update"])) {
    $id = (int) $_POST["id"];
    $name = trim($_POST["fullname"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $department = trim($_POST["department"]);
    $year = trim($_POST["year"]);

    if ($id <= 0 || $name === ""
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || $phone === "" || $department === "" || $year === "") {
        $message = "Please enter valid details in all fields.";
    } else {
        try {
            $stmt = $conn->prepare(
                "UPDATE registrations
                 SET fullname=?, email=?, phone=?, department=?, year=?
                 WHERE id=?"
            );
            $stmt->bind_param(
                "sssssi",
                $name, $email, $phone, $department, $year, $id
            );
            $stmt->execute();

            $message = $stmt->affected_rows > 0
                ? "Student updated successfully."
                : "No changes made, or student ID not found.";

            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $message = ($e->getCode() == 1062)
                ? "That email is already used by another student."
                : "Failed to update student.";
        }
    }
}

// DELETE STUDENT
if (isset($_POST["delete"])) {
    $id = (int) $_POST["id"];

    if ($id > 0) {
        $stmt = $conn->prepare(
            "DELETE FROM registrations WHERE id=?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $message = $stmt->affected_rows > 0
            ? "Student deleted successfully."
            : "Student not found.";

        $stmt->close();
    }
}

// SUCCESS MESSAGES AFTER ADD
if (isset($_GET["message"]) && $_GET["message"] === "added") {
    $message = "Student added successfully.";
}

// LOAD STUDENT FOR EDITING
if (isset($_GET["edit"])) {
    $id = (int) $_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT id, fullname, email, phone, department, year
         FROM registrations WHERE id=?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $editStudent = $result->fetch_assoc();
    $stmt->close();

    if (!$editStudent) {
        $message = "Student not found.";
    }
}

// SEARCH AND FILTER
$search = trim($_GET["search"] ?? "");
$departmentFilter = trim($_GET["department"] ?? "");
$yearFilter = trim($_GET["year"] ?? "");

$sql = "SELECT id, fullname, email, phone, department, year
        FROM registrations WHERE role='student'";

$params = [];
$types = "";

if ($search !== "") {
    $sql .= " AND (fullname LIKE ? OR email LIKE ?)";
    $term = "%" . $search . "%";
    $params[] = $term;
    $params[] = $term;
    $types .= "ss";
}

if ($departmentFilter !== "") {
    $sql .= " AND department = ?";
    $params[] = $departmentFilter;
    $types .= "s";
}

if ($yearFilter !== "") {
    $sql .= " AND year = ?";
    $params[] = $yearFilter;
    $types .= "s";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$students = $stmt->get_result();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<h1>Student Management</h1>

<?php if ($message !== ""): ?>
    <p><strong><?= e($message) ?></strong></p>
<?php endif; ?>

<h2><?= $editStudent ? "Edit Student" : "Add Student" ?></h2>

<form method="POST" action="students.php">
    <?php if ($editStudent): ?>
        <input type="hidden" name="id"
               value="<?= e($editStudent["id"]) ?>">
    <?php endif; ?>

    <label>Full Name:</label>
    <input type="text" name="fullname" required
           value="<?= e($editStudent["fullname"] ?? "") ?>">

    <label>Email:</label>
    <input type="email" name="email" required
           value="<?= e($editStudent["email"] ?? "") ?>">

    <label>Phone:</label>
    <input type="text" name="phone" required
           value="<?= e($editStudent["phone"] ?? "") ?>">

    <label>Department:</label>
    <input type="text" name="department" required
           value="<?= e($editStudent["department"] ?? "") ?>">

    <label>Year:</label>
    <select name="year" required>
        <option value="">Select Year</option>
        <?php foreach (["1st Year", "2nd Year", "3rd Year", "4th Year"] as $y): ?>
            <option value="<?= e($y) ?>"
                <?= (($editStudent["year"] ?? "") === $y) ? "selected" : "" ?>>
                <?= e($y) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?php if ($editStudent): ?>
        <button type="submit" name="update">Update Student</button>
        <a href="students.php">Cancel</a>
    <?php else: ?>
        <button type="submit" name="add">Add Student</button>
    <?php endif; ?>
</form>

<hr>

<h2>Search and Filter Students</h2>

<form method="GET" action="students.php">
    <input type="text" name="search"
           placeholder="Search name or email"
           value="<?= e($search) ?>">

    <select name="department">
        <option value="">All Departments</option>
        <?php
        $departments = $conn->query(
            "SELECT DISTINCT department FROM registrations
             WHERE role='student' ORDER BY department"
        );
        while ($d = $departments->fetch_assoc()):
        ?>
            <option value="<?= e($d["department"]) ?>"
                <?= $departmentFilter === $d["department"] ? "selected" : "" ?>>
                <?= e($d["department"]) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <select name="year">
        <option value="">All Years</option>
        <?php foreach (["1st Year", "2nd Year", "3rd Year", "4th Year"] as $y): ?>
            <option value="<?= e($y) ?>"
                <?= $yearFilter === $y ? "selected" : "" ?>>
                <?= e($y) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Search / Filter</button>
    <a href="students.php">Reset</a>
</form>

<h2>Student Records</h2>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Department</th>
        <th>Year</th>
        <th>Actions</th>
    </tr>

    <?php if ($students->num_rows > 0): ?>
        <?php while ($student = $students->fetch_assoc()): ?>
            <tr>
                <td><?= e($student["id"]) ?></td>
                <td><?= e($student["fullname"]) ?></td>
                <td><?= e($student["email"]) ?></td>
                <td><?= e($student["phone"]) ?></td>
                <td><?= e($student["department"]) ?></td>
                <td><?= e($student["year"]) ?></td>
                <td>
                    <a href="students.php?edit=<?= e($student["id"]) ?>">
                        Edit
                    </a>

                    <form method="POST" action="students.php"
                          onsubmit="return confirm('Delete this student?');">
                        <input type="hidden" name="id"
                               value="<?= e($student["id"]) ?>">
                        <button type="submit" name="delete">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="7">No student records found.</td>
        </tr>
    <?php endif; ?>
</table>

</body>
</html>
