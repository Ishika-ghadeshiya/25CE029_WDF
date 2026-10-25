```php
<?php
require_once __DIR__ . "/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Please submit the registration form.");
}

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$mobile = trim($_POST["mobile"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirmPassword"] ?? "";
$course = $_POST["course"] ?? "";
$year = $_POST["year"] ?? "";
$gender = $_POST["gender"] ?? "";
$terms = $_POST["terms"] ?? "";

$errors = [];

// Backend validation
if (!preg_match("/^[\p{L}][\p{L} .'-]{0,239}$/u", $name)) {
    $errors[] = "Enter a valid name.";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Enter a valid email address.";
}

if (!preg_match("/^[6-9][0-9]{9}$/", $mobile)) {
    $errors[] = "Enter a valid 10-digit Indian mobile number.";
}

$passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';

if (!preg_match($passwordPattern, $password)) {
    $errors[] = "Password must contain 8+ characters, uppercase, lowercase, number and special character.";
}

if ($password !== $confirmPassword) {
    $errors[] = "Passwords do not match.";
}

$allowedCourses = ["CE", "IT", "CSE", "EC"];
$allowedYears = ["1", "2", "3", "4"];
$allowedGenders = ["Male", "Female", "Other"];

if (!in_array($course, $allowedCourses, true)) {
    $errors[] = "Select a valid course.";
}

if (!in_array($year, $allowedYears, true)) {
    $errors[] = "Select a valid year.";
}

if (!in_array($gender, $allowedGenders, true)) {
    $errors[] = "Select a gender.";
}

if ($terms !== "yes") {
    $errors[] = "Accept the terms and conditions.";
}

// Display validation errors
if (!empty($errors)) {
    http_response_code(400);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Registration Error</title>
    </head>
    <body>
        <h2>Registration failed</h2>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></li>
            <?php endforeach; ?>
        </ul>
        <a href="register.html">Back to Registration</a>
    </body>
    </html>
    <?php
    exit;
}

try {
    // Check for an existing email using a prepared statement
    $stmt = $conn->prepare(
        "SELECT id FROM registrations WHERE email = ?"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        http_response_code(409);
        $message = "This email is already registered.";
    } else {
        $stmt->close();

        // Hash the password before storing it
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert registration using a prepared statement
        $stmt = $conn->prepare(
            "INSERT INTO registrations
            (fullname, email, phone, department, year, gender, password)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "sssssss",
            $name,
            $email,
            $mobile,
            $course,
            $year,
            $gender,
            $passwordHash
        );

        $stmt->execute();
        $stmt->close();
        $conn->close();

        // Preserve the existing successful redirect
        header("Location: dashboard.html?registered=1");
        exit;
    }
} catch (mysqli_sql_exception $e) {
    // Log technical details privately; do not show them to users
    error_log("Registration database error: " . $e->getMessage());

    if (isset($stmt)) {
        $stmt->close();
    }

    if ($e->getCode() === 1062) {
        http_response_code(409);
        $message = "This email is already registered.";
    } else {
        http_response_code(500);
        $message = "Registration could not be completed. Please try again.";
    }
}

if (isset($conn)) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Error</title>
</head>
<body>
    <h2>Registration failed</h2>
    <p><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></p>
    <a href="register.html">Back to Registration</a>
</body>
</html>
```