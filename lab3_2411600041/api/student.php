<?php
/**
 * student.php
 * A small JSON-file-backed API for the Student Grade Portal.
 *
 * GET  /api/student.php            -> returns all records
 * GET  /api/student.php?id=3       -> returns a single record
 * POST /api/student.php            -> body: {"action":"add", ...record}
 * POST /api/student.php            -> body: {"action":"update","id":3,"grade":88}
 */

header('Content-Type: application/json');

$dataFile = __DIR__ . '/allstudent.json';

function readData($file) {
    if (!file_exists($file)) return [];
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function writeData($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $data = readData($dataFile);

    if (isset($_GET['id'])) {
        $id = (int) $_GET['id'];
        $record = null;
        foreach ($data as $item) {
            if ((int) $item['id'] === $id) {
                $record = $item;
                break;
            }
        }
        if ($record === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Record not found']);
        } else {
            echo json_encode($record);
        }
        exit;
    }

    echo json_encode($data);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing "action" in request body']);
        exit;
    }

    $data = readData($dataFile);

    if ($input['action'] === 'add') {
        $newId = 1;
        foreach ($data as $item) {
            if ($item['id'] >= $newId) $newId = $item['id'] + 1;
        }
        $newRecord = [
            'id' => $newId,
            'date' => $input['date'] ?? date('Y-m-d'),
            'activity' => $input['activity'] ?? 'Untitled',
            'status' => $input['status'] ?? 'info',
            'grade' => isset($input['grade']) ? (int) $input['grade'] : 0,
            'semester' => isset($input['semester']) ? (int) $input['semester'] : 1
        ];
        $data[] = $newRecord;
        writeData($dataFile, $data);
        echo json_encode(['success' => true, 'record' => $newRecord]);
        exit;
    }

    if ($input['action'] === 'update') {
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing "id"']);
            exit;
        }
        $updated = null;
        foreach ($data as &$item) {
            if ((int) $item['id'] === (int) $input['id']) {
                if (isset($input['grade'])) $item['grade'] = (int) $input['grade'];
                if (isset($input['status'])) $item['status'] = $input['status'];
                $updated = $item;
                break;
            }
        }
        unset($item);
        if ($updated === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Record not found']);
            exit;
        }
        writeData($dataFile, $data);
        echo json_encode(['success' => true, 'record' => $updated]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);