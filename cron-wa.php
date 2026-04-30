<?php
/**
 * Cron Job for WhatsApp Notifications
 * 
 * Setup cron jobs:
 * - Recommended (run all due): * * * * * php /path/to/cron-wa.php run
 * - Morning reminder: 0 7 * * * php /path/to/cron-wa.php morning
 * - Afternoon report: 0 12 * * * php /path/to/cron-wa.php afternoon
 * - Scheduled reports: * / 15 * * * * php /path/to/cron-wa.php scheduled
 * 
 * Or call via web with secret key:
 * - https://yourdomain.com/cron-wa.php?key=YOUR_SECRET_KEY&action=run
 */

// Set timezone
date_default_timezone_set('Asia/Jakarta');

require_once 'includes/database.php';
require_once 'includes/functions.php';

// Secret key for web access (change this to a secure value)
$secretKey = 'ditjenpas_banten_2026_secret';

// Get action from CLI or web
$action = '';
if (php_sapi_name() === 'cli') {
    // CLI mode
    $action = $argv[1] ?? '';
} else {
    // Web mode - require secret key
    $key = $_GET['key'] ?? '';
    if ($key !== $secretKey) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid secret key']);
        exit;
    }
    $action = $_GET['action'] ?? '';
}

// Check if notifications are enabled
$settings = getWASettings();
if (!$settings || empty($settings['aktif'])) {
    echo json_encode(['success' => false, 'message' => 'WhatsApp notifications are disabled']);
    exit;
}

// Execute based on action
$result = ['success' => false, 'message' => 'Invalid action'];

switch ($action) {
    case 'morning':
        $result = sendMorningReminder();
        break;
        
    case 'afternoon':
        $result = sendAfternoonReport();
        break;

    case 'scheduled':
        $scheduledResults = processScheduledReports();
        $result = [
            'success' => true, 
            'message' => 'Processed ' . count($scheduledResults) . ' schedules',
            'details' => $scheduledResults
        ];
        break;
        
    case 'run':
        // Run everything that's due
        $morning = ['status' => 'skipped'];
        $afternoon = ['status' => 'skipped'];
        
        $today = date('Y-m-d');
        $currentTime = date('H:i');
        
        // Check morning reminder
        $morningTime = substr($settings['jam_reminder_pagi'] ?? '07:30:00', 0, 5);
        if ($currentTime >= $morningTime) {
            // Check if already sent today
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM wa_notification_logs WHERE tipe = 'reminder_pagi' AND DATE(tanggal_kirim) = ?");
            $stmt->execute([$today]);
            if ($stmt->fetchColumn() == 0) {
                $morning = sendMorningReminder();
            } else {
                $morning = ['status' => 'already_sent'];
            }
        }
        
        // Check afternoon report
        $afternoonTime = substr($settings['jam_reminder_siang'] ?? '12:00:00', 0, 5);
        if ($currentTime >= $afternoonTime) {
            // Check if already sent today
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM wa_notification_logs WHERE tipe = 'reminder_siang' AND DATE(tanggal_kirim) = ?");
            $stmt->execute([$today]);
            if ($stmt->fetchColumn() == 0) {
                $afternoon = sendAfternoonReport();
            } else {
                $afternoon = ['status' => 'already_sent'];
            }
        }
        
        // Always run scheduled reports
        $scheduled = processScheduledReports();
        
        $result = [
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'morning' => $morning,
            'afternoon' => $afternoon,
            'scheduled' => $scheduled
        ];
        break;
        
    case 'status':
        // ... (existing status code)
        // Return current status
        $today = date('Y-m-d');
        
        // Get today's notification count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM wa_notification_logs WHERE DATE(tanggal_kirim) = ? AND status = 'success'");
        $stmt->execute([$today]);
        $todayCount = $stmt->fetchColumn();
        
        // Get operators with WA
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'operator' AND no_wa IS NOT NULL AND no_wa != ''");
        $operatorsWithWA = $stmt->fetchColumn();
        
        // Get UPTs that haven't submitted today
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM upt WHERE id NOT IN (SELECT DISTINCT upt_id FROM data_penghuni WHERE tanggal = ?)");
        $stmt->execute([$today]);
        $missingPenghuni = $stmt->fetchColumn();
        
        $result = [
            'success' => true,
            'data' => [
                'notifications_today' => $todayCount,
                'operators_with_wa' => $operatorsWithWA,
                'missing_penghuni' => $missingPenghuni,
                'settings' => [
                    'aktif' => (bool)$settings['aktif'],
                    'reminder_pagi' => $settings['jam_reminder_pagi'],
                    'reminder_siang' => $settings['jam_reminder_siang'],
                    'group_id' => !empty($settings['group_id'])
                ]
            ]
        ];
        break;
        
    default:
        $result = ['success' => false, 'message' => 'Usage: cron-wa.php [run|scheduled|morning|afternoon|status]'];
}

// Output result
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);
