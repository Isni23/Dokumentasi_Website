<?php
// Simple endpoint to accept project title, desc and an optional image upload.
// Saves uploaded image to /uploads and appends metadata to projects.json

header('Content-Type: application/json');

$uploadsDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$desc = isset($_POST['desc']) ? trim($_POST['desc']) : '';
$id = isset($_POST['id']) ? trim($_POST['id']) : '';

$response = ['success' => false];

// handle file upload
$imagePath = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $tmpName = $_FILES['image']['tmp_name'];
    $origName = basename($_FILES['image']['name']);
    $ext = pathinfo($origName, PATHINFO_EXTENSION);
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array(strtolower($ext), $allowed)) {
        $response['error'] = 'File type not allowed';
        echo json_encode($response);
        exit;
    }
    $newName = uniqid('img_') . '.' . $ext;
    $dest = $uploadsDir . DIRECTORY_SEPARATOR . $newName;
    if (move_uploaded_file($tmpName, $dest)) {
        $imagePath = 'uploads/' . $newName;
    } else {
        $response['error'] = 'Failed to move uploaded file';
        echo json_encode($response);
        exit;
    }
}

// Persist metadata in projects.json
$metaFile = __DIR__ . DIRECTORY_SEPARATOR . 'projects.json';
$projects = [];
if (file_exists($metaFile)) {
    $raw = file_get_contents($metaFile);
    $projects = json_decode($raw, true) ?: [];
}

// if id provided, try update existing entry
if ($id) {
    $found = false;
    foreach ($projects as &$p) {
        if (isset($p['id']) && $p['id'] === $id) {
            $p['title'] = $title;
            $p['desc'] = $desc;
            if ($imagePath) $p['image'] = $imagePath;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $projects[] = ['id'=>$id, 'title'=>$title, 'desc'=>$desc, 'image'=>($imagePath?:'')];
    }
} else {
    $id = uniqid();
    $projects[] = ['id'=>$id, 'title'=>$title, 'desc'=>$desc, 'image'=>($imagePath?:'')];
}

file_put_contents($metaFile, json_encode($projects, JSON_PRETTY_PRINT));

$response['success'] = true;
$response['id'] = $id;
$response['path'] = $imagePath ?: '';

echo json_encode($response);
