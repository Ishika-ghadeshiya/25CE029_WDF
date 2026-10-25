
<?php
require_once "auth.php";
require_once "db.php";

// Only admins can manage events.
if (($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied. Admins only.");
}

$message = "";
$uploadDir = __DIR__ . "/uploads/events/";
$uploadUrl = "uploads/events/";

// Allowed poster types and maximum size: 2 MB.
$allowedTypes = [
    "image/jpeg" => "jpg",
    "image/png"  => "png",
    "image/webp" => "webp"
];
$maxSize = 2 * 1024 * 1024;

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

function clean($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function uploadPoster($field, $uploadDir, $allowedTypes, $maxSize) {
    if (!isset($_FILES[$field]) || $_FILES[$field]["error"] === UPLOAD_ERR_NO_FILE) {
        return ["ok" => true, "filename" => null, "error" => ""];
    }

    $file = $_FILES[$field];

    if ($file["error"] !== UPLOAD_ERR_OK) {
        return ["ok" => false, "filename" => null,
                "error" => "Poster upload failed."];
    }

    if ($file["size"] > $maxSize) {
        return ["ok" => false, "filename" => null,
                "error" => "Poster must be 2 MB or smaller."];
    }

    if (!is_uploaded_file($file["tmp_name"])) {
        return ["ok" => false, "filename" => null,
                "error" => "Invalid uploaded file."];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file["tmp_name"]);

    if (!isset($allowedTypes[$mime])) {
        return ["ok" => false, "filename" => null,
                "error" => "Only JPG, PNG, and WEBP posters are allowed."];
    }

    $filename = bin2hex(random_bytes(16)) . "." . $allowedTypes[$mime];

    if (!move_uploaded_file($file["tmp_name"], $uploadDir . $filename)) {
        return ["ok" => false, "filename" => null,
                "error" => "Could not save poster. Check folder permissions."];
    }

    return ["ok" => true, "filename" => $filename, "error" => ""];
}

function removePoster($filename, $uploadDir) {
    if ($filename && basename($filename) === $filename) {
        $path = $uploadDir . $filename;
        if (is_file($path)) {
            unlink($path);
        }
    }
}

// ADD EVENT
if (isset($_POST["add"])) {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $date = trim($_POST["event_date"] ?? "");
    $venue = trim($_POST["venue"] ?? "");

    if ($title === "" || $description === "" || $venue === "" ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ||
        !checkdate((int)substr($date, 5, 2),
                   (int)substr($date, 8, 2),
                   (int)substr($date, 0, 4))) {
        $message = "Please enter valid event details.";
    } else {
        $upload = uploadPoster("poster", $uploadDir, $allowedTypes, $maxSize);

        if (!$upload["ok"]) {
            $message = $upload["error"];
        } else {
            try {
                $poster = $upload["filename"];
                $stmt = $conn->prepare(
                    "INSERT INTO events (title, description, event_date, venue, poster)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("sssss", $title, $description, $date, $venue, $poster);
                $stmt->execute();
                $stmt->close();

                header("Location: events.php?status=added");
                exit;
            } catch (Throwable $e) {
                if ($upload["filename"]) {
                    removePoster($upload["filename"], $uploadDir);
                }
                $message = "Failed to add event.";
            }
        }
    }
}

// UPDATE EVENT
if (isset($_POST["update"])) {
    $id = (int)($_POST["id"] ?? 0);
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $date = trim($_POST["event_date"] ?? "");
    $venue = trim($_POST["venue"] ?? "");

    if ($id <= 0 || $title === "" || $description === "" || $venue === "" ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ||
        !checkdate((int)substr($date, 5, 2),
                   (int)substr($date, 8, 2),
                   (int)substr($date, 0, 4))) {
        $message = "Please enter valid event details.";
    } else {
        $stmt = $conn->prepare("SELECT poster FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $oldEvent = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$oldEvent) {
            $message = "Event not found.";
        } else {
            $upload = uploadPoster("poster", $uploadDir, $allowedTypes, $maxSize);

            if (!$upload["ok"]) {
                $message = $upload["error"];
            } else {
                $poster = $upload["filename"] ?? $oldEvent["poster"];

                try {
                    $stmt = $conn->prepare(
                        "UPDATE events
                         SET title=?, description=?, event_date=?, venue=?, poster=?
                         WHERE id=?"
                    );
                    $stmt->bind_param(
                        "sssssi", $title, $description, $date, $venue, $poster, $id
                    );
                    $stmt->execute();
                    $stmt->close();

                    if ($upload["filename"]) {
                        removePoster($oldEvent["poster"], $uploadDir);
                    }

                    header("Location: events.php?status=updated");
                    exit;
                } catch (Throwable $e) {
                    if ($upload["filename"]) {
                        removePoster($upload["filename"], $uploadDir);
                    }
                    $message = "Failed to update event.";
                }
            }
        }
    }
}

// DELETE EVENT
if (isset($_POST["delete"])) {
    $id = (int)($_POST["id"] ?? 0);

    $stmt = $conn->prepare("SELECT poster FROM events WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$event) {
        $message = "Event not found.";
    } else {
        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();

        if ($deleted) {
            removePoster($event["poster"], $uploadDir);
            header("Location: events.php?status=deleted");
            exit;
        }

        $message = "Failed to delete event.";
    }
}

if (isset($_GET["status"])) {
    $statuses = [
        "added" => "Event added successfully.",
        "updated" => "Event updated successfully.",
        "deleted" => "Event deleted successfully."
    ];
    $message = $statuses[$_GET["status"]] ?? $message;
}

// LOAD EVENT FOR EDITING
$editEvent = null;
if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editEvent = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$editEvent) {
        $message = "Event not found.";
    }
}

// VIEW ALL EVENTS
$events = $conn->query("SELECT * FROM events ORDER BY event_date DESC, id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<h1>Event Management</h1>

<?php if ($message !== ""): ?>
    <p><strong><?= clean($message) ?></strong></p>
<?php endif; ?>

<h2><?= $editEvent ? "Update Event" : "Add Event" ?></h2>

<form method="POST" action="events.php" enctype="multipart/form-data">
    <?php if ($editEvent): ?>
        <input type="hidden" name="id" value="<?= (int)$editEvent["id"] ?>">
    <?php endif; ?>

    <p>
        <label>Event Title</label><br>
        <input type="text" name="title" maxlength="150" required
               value="<?= clean($editEvent["title"] ?? "") ?>">
    </p>

    <p>
        <label>Description</label><br>
        <textarea name="description" required><?= clean($editEvent["description"] ?? "") ?></textarea>
    </p>

    <p>
        <label>Event Date</label><br>
        <input type="date" name="event_date" required
               value="<?= clean($editEvent["event_date"] ?? "") ?>">
    </p>

    <p>
        <label>Venue</label><br>
        <input type="text" name="venue" maxlength="150" required
               value="<?= clean($editEvent["venue"] ?? "") ?>">
    </p>

    <p>
        <label>Poster (JPG, PNG, or WEBP; maximum 2 MB)</label><br>
        <input type="file" name="poster" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
    </p>

    <?php if (!empty($editEvent["poster"])): ?>
        <p>Current poster:</p>
        <img src="<?= clean($uploadUrl . $editEvent["poster"]) ?>"
             alt="Event poster" width="150">
        <p>Leave the poster field empty to keep the existing poster.</p>
    <?php endif; ?>

    <?php if ($editEvent): ?>
        <button type="submit" name="update">Update Event</button>
        <a href="events.php">Cancel</a>
    <?php else: ?>
        <button type="submit" name="add">Add Event</button>
    <?php endif; ?>
</form>

<hr>

<h2>All Events</h2>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Poster</th>
        <th>Title</th>
        <th>Description</th>
        <th>Date</th>
        <th>Venue</th>
        <th>Actions</th>
    </tr>

    <?php while ($event = $events->fetch_assoc()): ?>
        <tr>
            <td><?= (int)$event["id"] ?></td>
            <td>
                <?php if ($event["poster"]): ?>
                    <img src="<?= clean($uploadUrl . $event["poster"]) ?>"
                         alt="Event poster" width="100">
                <?php else: ?>
                    No poster
                <?php endif; ?>
            </td>
            <td><?= clean($event["title"]) ?></td>
            <td><?= nl2br(clean($event["description"])) ?></td>
            <td><?= clean($event["event_date"]) ?></td>
            <td><?= clean($event["venue"]) ?></td>
            <td>
                <a href="events.php?edit=<?= (int)$event["id"] ?>">Edit</a>

                <form method="POST" action="events.php"
                      onsubmit="return confirm('Delete this event?');">
                    <input type="hidden" name="id" value="<?= (int)$event["id"] ?>">
                    <button type="submit" name="delete">Delete</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
