
<?php
require_once __DIR__ . "/db.php";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
}

if (!$id || $id < 1) {
    http_response_code(400);
    exit("Invalid student ID. <a href='admin.php'>Back</a>");
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["fullname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $department = $_POST["department"] ?? "";
    $year = $_POST["year"] ?? "";
    $gender = $_POST["gender"] ?? "";

    if ($name === "" || strlen($name) > 100) $errors[] = "Enter a valid name.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email.";
    if (!preg_match("/^[0-9]{10}$/", $phone)) $errors[] = "Enter a 10-digit mobile number.";
    if (!in_array($department, ["CE", "IT", "CSE", "EC"], true)) $errors[] = "Select a valid department.";
    if (!in_array($year, ["1", "2", "3", "4"], true)) $errors[] = "Select a valid year.";
    if (!in_array($gender, ["Male", "Female", "Other"], true)) $errors[] = "Select a valid gender.";

    if (!$errors) {
        try {
            $stmt = $conn->prepare(
                "UPDATE registrations
                 SET fullname=?, email=?, phone=?, department=?, year=?, gender=?
                 WHERE id=?"
            );
            $stmt->bind_param(
                "ssssssi",
                $name, $email, $phone, $department, $year, $gender, $id
            );
            $stmt->execute();
            $stmt->close();
            $conn->close();

            header("Location: admin.php?updated=1");
            exit;
        } catch (mysqli_sql_exception $e) {
            $errors[] = $e->getCode() === 1062
                ? "That email is already in use."
                : "Unable to update the record.";
        }
    }

    $student = [
        "id" => $id,
        "fullname" => $name,
        "email" => $email,
        "phone" => $phone,
        "department" => $department,
        "year" => $year,
        "gender" => $gender
    ];
} else {
    $stmt = $conn->prepare("SELECT id, fullname, email, phone, department, year, gender FROM registrations WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$student) {
        http_response_code(404);
        exit("Student record not found. <a href='admin.php'>Back</a>");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Student</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 520px; margin: 30px auto; padding: 0 16px; }
        label { display: block; margin-top: 12px; }
        input, select, button { box-sizing: border-box; width: 100%; padding: 10px; margin-top: 5px; }
        .errors { color: #c62828; }
    </style>
</head>
<body>
    <h2>Edit Student Record</h2>

    <?php if ($errors): ?>
        <ul class="errors">
            <?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="edit.php">
        <input type="hidden" name="id" value="<?= (int)$student["id"] ?>">

        <label>Full Name
            <input name="fullname" maxlength="100" required value="<?= h($student["fullname"]) ?>">
        </label>
        <label>Email
            <input type="email" name="email" maxlength="254" required value="<?= h($student["email"]) ?>">
        </label>
        <label>Mobile Number
            <input name="phone" pattern="[0-9]{10}" maxlength="10" required value="<?= h($student["phone"]) ?>">
        </label>
        <label>Department
            <select name="department" required>
                <?php foreach (["CE", "IT", "CSE", "EC"] as $option): ?>
                    <option value="<?= h($option) ?>" <?= $student["department"] === $option ? "selected" : "" ?>>
                        <?= h($option) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Year
            <select name="year" required>
                <?php foreach (["1", "2", "3", "4"] as $option): ?>
                    <option value="<?= h($option) ?>" <?= (string)$student["year"] === $option ? "selected" : "" ?>>
                        Year <?= h($option) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Gender
            <select name="gender" required>
                <?php foreach (["Male", "Female", "Other"] as $option): ?>
                    <option value="<?= h($option) ?>" <?= $student["gender"] === $option ? "selected" : "" ?>>
                        <?= h($option) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <button type="submit">Save Changes</button>
    </form>

    <p><a href="admin.php">Back to Admin Dashboard</a></p>
</body>
</html>
<?php $conn->close(); ?>
