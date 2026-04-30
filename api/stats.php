<?php
/**
 * API Endpoint for Dashboard Statistics
 * This file provides data for the main website.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow from any origin (or specify your main domain)

require_once '../includes/functions.php';

// Security: Simple API Key (Change this to a secure random string)
$apiKey = 'ditjenpas_banten_secret_key_2026';

$headers = getallheaders();
$providedKey = $_GET['api_key'] ?? $headers['X-API-KEY'] ?? '';

if ($providedKey !== $apiKey) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Valid API Key required.'
    ]);
    exit;
}

// Get action from request
$action = $_GET['action'] ?? 'stats';

try {
    if ($action === 'list_upt') {
        // Return list of all UPTs
        $uptList = getAllUPT();
        $simplifiedList = array_map(function($upt) {
            return [
                'id' => $upt['id'],
                'nama' => $upt['nama_upt']
            ];
        }, $uptList);
        
        echo json_encode([
            'success' => true,
            'data' => $simplifiedList
        ]);
    } elseif ($action === 'by_klasifikasi') {
        // Get data for Classification Chart
        $upt_id = isset($_GET['upt_id']) ? (int)$_GET['upt_id'] : null;
        $uptClause = "";
        $params = [];
        if ($upt_id) {
            $uptClause = "AND upt_id = ?";
            $params = [$upt_id];
        }
        $data = getPenghuniByKlasifikasi($uptClause, $params);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } elseif ($action === 'by_upt') {
        // Get data for UPT Chart
        $data = getPenghuniByUPT();
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } elseif ($action === 'table') {
        // Get data for Data Table
        $tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
        $upt_id = isset($_GET['upt_id']) ? (int)$_GET['upt_id'] : null;
        $dateClause = "AND dp.tanggal = ?";
        $params = [$tanggal];
        $data = getUptDataTable($dateClause, $params, $upt_id);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        // Default: Get Statistics
        $upt_id = isset($_GET['upt_id']) ? (int)$_GET['upt_id'] : null;
        $tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : null;
        $stats = getPublicStats($upt_id, $tanggal);
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal Server Error: ' . $e->getMessage()
    ]);
}
