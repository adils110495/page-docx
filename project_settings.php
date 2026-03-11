<?php
$settingsFile = __DIR__ . '/output/.project_settings.json';

function readSettings($file) {
    if (!file_exists($file)) return ['hidden' => []];
    $data = json_decode(file_get_contents($file), true);
    return (is_array($data) && isset($data['hidden'])) ? $data : ['hidden' => []];
}

function writeSettings($file, $settings) {
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT));
}

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$settings = readSettings($settingsFile);
$action = $data['action'];

if ($action === 'hide_project') {
    if (!isset($data['project'])) {
        echo json_encode(['success' => false, 'message' => 'No project specified']);
        exit;
    }
    $project = basename($data['project']); // Security: only allow folder name, no path traversal
    if (!in_array($project, $settings['hidden'])) {
        $settings['hidden'][] = $project;
    }
    writeSettings($settingsFile, $settings);
    echo json_encode(['success' => true]);

} elseif ($action === 'show_project') {
    if (!isset($data['project'])) {
        echo json_encode(['success' => false, 'message' => 'No project specified']);
        exit;
    }
    $project = basename($data['project']);
    $settings['hidden'] = array_values(array_filter($settings['hidden'], function($p) use ($project) {
        return $p !== $project;
    }));
    writeSettings($settingsFile, $settings);
    echo json_encode(['success' => true]);

} elseif ($action === 'get_settings') {
    echo json_encode(['success' => true, 'settings' => $settings]);

} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
