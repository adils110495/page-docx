<?php
session_start();

/**
 * Bulk Actions Handler
 * Handles bulk download and delete operations
 */

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $data['action'];
$baseDir = __DIR__ . '/output';
$realBase = realpath($baseDir);

// Handle delete_folder action
if ($action === 'delete_folder') {
    if (!isset($data['folder'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No folder specified']);
        exit;
    }

    $folderParam = $data['folder'];

    // Remove 'output/' prefix if present
    $cleanFolder = preg_replace('#^output/#', '', $folderParam);

    // Security: no path traversal
    if (strpos($cleanFolder, '..') !== false || strpos($cleanFolder, '/') !== false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid folder path']);
        exit;
    }

    $fullPath = $baseDir . '/' . $cleanFolder;

    if (!is_dir($fullPath)) {
        echo json_encode(['success' => false, 'message' => 'Folder not found']);
        exit;
    }

    // Verify real path is within output directory
    $realPath = realpath($fullPath);
    if ($realPath === false || strpos($realPath, $realBase) !== 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid folder path']);
        exit;
    }

    // Recursively delete folder contents and folder itself
    function deleteRecursive($dir) {
        $count = 0;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $count += deleteRecursive($path);
            } else {
                if (unlink($path)) $count++;
            }
        }
        rmdir($dir);
        return $count;
    }

    $deleted = deleteRecursive($realPath);
    echo json_encode(['success' => true, 'deleted' => $deleted]);
    exit;
}

// All other actions require 'files'
if (!isset($data['files'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$files = $data['files'];

// Security: Validate all files are in output directory
$validFiles = [];

foreach ($files as $file) {
    // Remove 'output/' prefix if present
    $cleanFile = str_replace('output/', '', $file);
    $fullPath = $baseDir . '/' . $cleanFile;

    // Security checks
    if (strpos($cleanFile, '..') !== false) {
        continue; // Skip directory traversal attempts
    }

    if (!file_exists($fullPath) || !is_file($fullPath)) {
        continue; // Skip non-existent or non-file paths
    }

    // Verify real path is within output directory
    $realPath = realpath($fullPath);

    if ($realPath === false || strpos($realPath, $realBase) !== 0) {
        continue; // Skip files outside output directory
    }

    $validFiles[] = $realPath;
}

if (empty($validFiles)) {
    echo json_encode(['success' => false, 'message' => 'No valid files to process']);
    exit;
}

// Handle action
if ($action === 'delete') {
    $deletedCount = 0;
    $errors = [];

    foreach ($validFiles as $filePath) {
        if (unlink($filePath)) {
            $deletedCount++;
        } else {
            $errors[] = basename($filePath);
        }
    }

    if ($deletedCount > 0) {
        echo json_encode([
            'success' => true,
            'deleted' => $deletedCount,
            'errors' => $errors
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete any files'
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
