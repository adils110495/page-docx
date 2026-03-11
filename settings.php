<?php
/**
 * Settings Handler
 * Saves/loads project visibility settings
 */

header('Content-Type: application/json');

$settingsFile = __DIR__ . '/output/.settings.json';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if ($data['action'] === 'save_settings') {
    $hiddenProjects = $data['hidden_projects'] ?? [];

    // Validate: only non-empty strings, no path traversal
    $hiddenProjects = array_values(array_filter($hiddenProjects, function($p) {
        return is_string($p) && $p !== '' && strpos($p, '/') === false && strpos($p, '\\') === false && strpos($p, '..') === false;
    }));

    // Ensure output dir exists
    if (!is_dir(__DIR__ . '/output')) {
        mkdir(__DIR__ . '/output', 0755, true);
    }

    $settings = ['hidden_projects' => $hiddenProjects];

    if (file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT)) !== false) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save settings']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
