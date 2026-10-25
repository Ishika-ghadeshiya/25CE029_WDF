<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";

function sendResponse($success, $message, $data = null, $status = 200) {
    http_response_code($status);
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

// Only admins can manage events.
if (($_SESSION["role"] ?? "") !== "admin") {
    sendResponse(false, "Access denied. Admins only.", null, 403);
}

$method = $_SERVER["REQUEST_METHOD"];
$input = json_decode(file_get_contents("php://input"), true);

try {
    switch ($method) {
        // LIST EVENTS
        case "GET":
            $result = $conn->query(
                "SELECT id, title, description, event_date, venue
                 FROM events ORDER BY id DESC"
            );

            $events = [];
            while ($row = $result->fetch_assoc()) {
                $events[] = $row;
            }

            sendResponse(true, "Events fetched successfully.", $events);
            break;

        // ADD EVENT
        case "POST":
            if (
                !is_array($input) ||
                empty(trim($input["title"] ?? "")) ||
                empty(trim($input["description"] ?? "")) ||
                empty($input["event_date"]) ||
                empty(trim($input["venue"] ?? ""))
            ) {
                sendResponse(false, "Please fill in all event fields.", null, 400);
            }

            $title = trim($input["title"]);
            $description = trim($input["description"]);
            $eventDate = $input["event_date"];
            $venue = trim($input["venue"]);

            $date = DateTime::createFromFormat("!Y-m-d", $eventDate);
            if (!$date || $date->format("Y-m-d") !== $eventDate) {
                sendResponse(false, "Invalid event date.", null, 400);
            }

            $stmt = $conn->prepare(
                "INSERT INTO events (title, description, event_date, venue)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param("ssss", $title, $description, $eventDate, $venue);
            $stmt->execute();
            $id = $conn->insert_id;
            $stmt->close();

            sendResponse(true, "Event added successfully.", ["id" => $id], 201);
            break;

        // UPDATE EVENT
        case "PUT":
            if (
                !is_array($input) ||
                !filter_var($input["id"] ?? null, FILTER_VALIDATE_INT) ||
                (int)$input["id"] < 1 ||
                empty(trim($input["title"] ?? "")) ||
                empty(trim($input["description"] ?? "")) ||
                empty($input["event_date"]) ||
                empty(trim($input["venue"] ?? ""))
            ) {
                sendResponse(false, "Valid ID and all event fields are required.", null, 400);
            }

            $id = (int)$input["id"];
            $title = trim($input["title"]);
            $description = trim($input["description"]);
            $eventDate = $input["event_date"];
            $venue = trim($input["venue"]);

            $date = DateTime::createFromFormat("!Y-m-d", $eventDate);
            if (!$date || $date->format("Y-m-d") !== $eventDate) {
                sendResponse(false, "Invalid event date.", null, 400);
            }

            $stmt = $conn->prepare(
                "UPDATE events
                 SET title = ?, description = ?, event_date = ?, venue = ?
                 WHERE id = ?"
            );
            $stmt->bind_param("ssssi", $title, $description, $eventDate, $venue, $id);
            $stmt->execute();
            $changed = $stmt->affected_rows;
            $stmt->close();

            if ($changed === 0) {
                $check = $conn->prepare("SELECT id FROM events WHERE id = ?");
                $check->bind_param("i", $id);
                $check->execute();
                $check->store_result();
                $exists = $check->num_rows > 0;
                $check->close();

                if (!$exists) {
                    sendResponse(false, "Event not found.", null, 404);
                }
            }

            sendResponse(true, "Event updated successfully.");
            break;

        // DELETE EVENT
        case "DELETE":
            if (
                !is_array($input) ||
                !filter_var($input["id"] ?? null, FILTER_VALIDATE_INT) ||
                (int)$input["id"] < 1
            ) {
                sendResponse(false, "A valid event ID is required.", null, 400);
            }

            $id = (int)$input["id"];
            $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();

            if ($deleted === 0) {
                sendResponse(false, "Event not found.", null, 404);
            }

            sendResponse(true, "Event deleted successfully.");
            break;

        default:
            header("Allow: GET, POST, PUT, DELETE");
            sendResponse(false, "Method not allowed.", null, 405);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    sendResponse(false, "A server or database error occurred.", null, 500);
}
?>