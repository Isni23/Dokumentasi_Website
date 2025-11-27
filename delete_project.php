<?php
header('Content-Type: application/json');

$metaFile = __DIR__ . DIRECTORY_SEPARATOR . 'projects.json';
$response = ['success' => false];
<?php
header('Content-Type: application/json');

$metaFile = __DIR__ . DIRECTORY_SEPARATOR . 'projects.json';
$response = ['success' => false];

// read id
$id = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // support both form-encoded and json
    if (!empty($_POST['id'])) $id = trim($_POST['id']);
    else {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $data = json_decode($raw, true);
            if (isset($data['id'])) $id = trim($data['id']);
        }
    }
}

if (!$id) {
    $response['error'] = 'No id provided';
    echo json_encode($response);
    exit;
}

if (!file_exists($metaFile)) {
    $response['error'] = 'No metadata file';
    echo json_encode($response);
    exit;
}

$raw = file_get_contents($metaFile);
$projects = json_decode($raw, true) ?: [];

$found = false;
foreach ($projects as $idx => $p) {
    if (isset($p['id']) && $p['id'] === $id) {
        // delete file if exists and located in uploads/
        if (!empty($p['image'])) {
            $imgPath = __DIR__ . DIRECTORY_SEPARATOR . $p['image'];
            // only unlink if file is inside uploads directory for safety
            $uploadsDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads') . DIRECTORY_SEPARATOR;
            $realImg = realpath($imgPath) ?: '';
            if ($uploadsDir && strpos($realImg, $uploadsDir) === 0 && file_exists($realImg)) {
                @unlink($realImg);
            }
        }
        // remove from array
        array_splice($projects, $idx, 1);
        $found = true;
        break;
    }
}

if ($found) {
    file_put_contents($metaFile, json_encode($projects, JSON_PRETTY_PRINT));
    $response['success'] = true;
} else {
    $response['error'] = 'Not found';
}

echo json_encode($response);
