<?php
$allowed_origins = ['https://sraws.com', 'https://www.sraws.com', 'http://localhost:3000'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
} else {
    header("Access-Control-Allow-Origin: https://sraws.com");
}
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include 'db_config.php';

if (!isset($_FILES['media']) || !is_array($_FILES['media']['tmp_name'])) {
    echo json_encode(["error" => "No files uploaded."]);
    exit();
}

if (!isset($_POST['username']) || empty($_POST['username'])) {
    echo json_encode(["error" => "Username not provided."]);
    exit();
}

$username = basename($_POST['username']);
$target_dir = "media/" . $username . "/";

if (!is_dir($target_dir)) {
    if (!mkdir($target_dir, 0777, true)) {
        echo json_encode(["error" => "Failed to create directory."]);
        exit();
    }
}

$response = [];

$allowed_extensions_images = ["jpg", "jpeg", "png", "gif", "bmp", "webp", "avif"];
$allowed_extensions_videos = ["mp4", "avi", "mov", "mkv", "wmv", "flv"];
$allowed_extensions_audios = ["mp3", "wav", "ogg", "aac", "flac"];
$max_file_size = 50 * 1024 * 1024; // Set a maximum file size, e.g., 50MB

function sanitizeFileName($fileName) {
    $fileName = preg_replace('/[\p{C}]/u', '', $fileName);
    $fileName = str_replace(' ', '-', $fileName);

    $max_name_length = 60;
    $file_extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $base_name = pathinfo($fileName, PATHINFO_FILENAME);

    if (strlen($base_name) > $max_name_length) {
        $base_name = substr($base_name, 0, $max_name_length);
    }

    return $base_name . '.' . $file_extension;
}

foreach ($_FILES['media']['tmp_name'] as $key => $tmp_name) {
    if ($_FILES['media']['error'][$key] !== UPLOAD_ERR_OK) {
        $response[] = ["error" => "Error with file upload at index $key."];
        continue;
    }

    $original_file_name = basename($_FILES['media']['name'][$key]);
    $sanitized_file_name = sanitizeFileName($original_file_name);
    $file_extension = strtolower(pathinfo($sanitized_file_name, PATHINFO_EXTENSION));
    
    $random_string = uniqid();
    $random_file_name = pathinfo($sanitized_file_name, PATHINFO_FILENAME) . '_' . $random_string . '.' . $file_extension;
    $target_file = $target_dir . $random_file_name;

    if ($_FILES['media']['size'][$key] == 0) {
        $response[] = ["error" => "Empty file uploaded: $original_file_name"];
        continue;
    }

    if ($_FILES['media']['size'][$key] > $max_file_size) {
        $response[] = ["error" => "File too large: $original_file_name"];
        continue;
    }

    if (in_array($file_extension, $allowed_extensions_images)) {
        $file_type = "image";
    } elseif (in_array($file_extension, $allowed_extensions_videos)) {
        $file_type = "video";
    } elseif (in_array($file_extension, $allowed_extensions_audios)) {
        $file_type = "audio";
    } else {
        $response[] = ["error" => "File type not supported: $original_file_name"];
        continue;
    }

    if (move_uploaded_file($tmp_name, $target_file)) {
        $file_path = 'https://media.sraws.com/media/' . $username . '/' . $random_file_name;
        $stmt = $conn->prepare("INSERT INTO media (file_name, file_path, file_type) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $sanitized_file_name, $file_path, $file_type);

        if ($stmt->execute()) {
            $response[] = ["url" => $file_path];
        } else {
            $response[] = ["error" => "Database error: " . $stmt->error];
        }
        $stmt->close();
    } else {
        $response[] = ["error" => "Error uploading file: $original_file_name"];
    }
}

$conn->close();

echo json_encode($response);
?>
