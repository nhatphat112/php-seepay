<?php
/**
 * CMS API - Weekly Events
 * GET: List events
 * POST: Create/Update event
 * DELETE: Delete event (via query param ?event_id=)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../connection_manager.php';

try {
    $db = ConnectionManager::getAccountDB();
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Get single event by ID or all events
        $eventID = isset($_GET['event_id']) ? (int)$_GET['event_id'] : null;
        
        if ($eventID) {
            // Get single event
            $stmt = $db->prepare("SELECT EventID, EventTime, EventDay, EventTitle, DisplayOrder, IsActive, CreatedDate, UpdatedDate FROM TB_WeeklyEvents WHERE EventID = ?");
            $stmt->execute([$eventID]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($event) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'event_id' => (int)$event['EventID'],
                        'event_time' => $event['EventTime'],
                        'event_day' => $event['EventDay'],
                        'event_title' => $event['EventTitle'],
                        'display_order' => (int)$event['DisplayOrder'],
                        'is_active' => (bool)$event['IsActive'],
                        'created_date' => $event['CreatedDate'],
                        'updated_date' => $event['UpdatedDate']
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Event not found'
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
        // Get all events
        $stmt = $db->query("SELECT EventID, EventTime, EventDay, EventTitle, DisplayOrder, IsActive, CreatedDate, UpdatedDate FROM TB_WeeklyEvents ORDER BY DisplayOrder ASC, EventID ASC");
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => array_map(function($event) {
                return [
                    'event_id' => (int)$event['EventID'],
                    'event_time' => $event['EventTime'],
                    'event_day' => $event['EventDay'],
                    'event_title' => $event['EventTitle'],
                    'display_order' => (int)$event['DisplayOrder'],
                    'is_active' => (bool)$event['IsActive'],
                    'created_date' => $event['CreatedDate'],
                    'updated_date' => $event['UpdatedDate']
                ];
            }, $events)
        ], JSON_UNESCAPED_UNICODE);
        }
    } 
    elseif ($method === 'POST') {
        // Create or Update event
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['event_time']) || !isset($input['event_day']) || !isset($input['event_title'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'event_time, event_day, and event_title are required'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $eventTime = trim($input['event_time']);
        $eventDay = trim($input['event_day']);
        $eventTitle = trim($input['event_title']);
        $displayOrder = isset($input['display_order']) ? (int)$input['display_order'] : 0;
        $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : true;
        $eventID = isset($input['event_id']) ? (int)$input['event_id'] : null;
        
        if ($eventID) {
            // Update existing
            $stmt = $db->prepare("UPDATE TB_WeeklyEvents SET EventTime = ?, EventDay = ?, EventTitle = ?, DisplayOrder = ?, IsActive = ?, UpdatedDate = GETDATE() WHERE EventID = ?");
            $stmt->execute([$eventTime, $eventDay, $eventTitle, $displayOrder, $isActive ? 1 : 0, $eventID]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Event updated successfully',
                'event_id' => $eventID
            ], JSON_UNESCAPED_UNICODE);
        } else {
            // Insert new
            $stmt = $db->prepare("INSERT INTO TB_WeeklyEvents (EventTime, EventDay, EventTitle, DisplayOrder, IsActive, CreatedDate, UpdatedDate) VALUES (?, ?, ?, ?, ?, GETDATE(), GETDATE())");
            $stmt->execute([$eventTime, $eventDay, $eventTitle, $displayOrder, $isActive ? 1 : 0]);
            
            $newID = $db->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Event created successfully',
                'event_id' => (int)$newID
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    elseif ($method === 'DELETE') {
        // Delete event
        $eventID = isset($_GET['event_id']) ? (int)$_GET['event_id'] : null;
        
        if (!$eventID) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'event_id parameter is required'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $stmt = $db->prepare("DELETE FROM TB_WeeklyEvents WHERE EventID = ?");
        $stmt->execute([$eventID]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Event deleted successfully'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Event not found'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

