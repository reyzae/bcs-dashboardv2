<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/BaseController.php';

/**
 * Bytebalok Notification Controller
 * Handles real-time notifications for staff
 */

class NotificationController extends BaseController {
    private $notificationModel;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->notificationModel = new Notification($pdo);
    }
    
    /**
     * Get unread notifications for current user
     */
    public function unread() {
        if ($this->getMethod() !== 'GET') {
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed'
            ]);
            return;
        }
        
        try {
            // Get unread notifications (user_id = null means broadcast to all staff)
            $notifications = $this->notificationModel->getUnread();
            
            // Format notifications for frontend
            $formattedNotifications = array_map(function($notif) {
                return [
                    'id' => $notif['id'],
                    'type' => $notif['type'],
                    'title' => $notif['title'],
                    'message' => $notif['message'],
                    'is_read' => $notif['is_read'],
                    'order_id' => $notif['order_id'],
                    'created_at' => $notif['created_at']
                ];
            }, $notifications);
            
            echo json_encode([
                'success' => true,
                'data' => $formattedNotifications
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to get notifications: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead() {
        if ($this->getMethod() !== 'POST') {
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed'
            ]);
            return;
        }
        
        $data = $this->getRequestData();
        $notificationId = $data['id'] ?? $data['notification_id'] ?? null;
        
        if (!$notificationId) {
            echo json_encode([
                'success' => false,
                'error' => 'Notification ID is required'
            ]);
            return;
        }
        
        try {
            $result = $this->notificationModel->markAsRead($notificationId);
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification marked as read'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Notification not found'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to mark notification as read: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead() {
        if ($this->getMethod() !== 'POST') {
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed'
            ]);
            return;
        }
        
        try {
            $result = $this->notificationModel->markAllAsRead(null); // null = all users
            echo json_encode([
                'success' => true,
                'message' => 'All notifications marked as read',
                'affected_rows' => $result
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to mark all notifications as read: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get recent notifications (last 24 hours)
     */
    public function recent() {
        if ($this->getMethod() !== 'GET') {
            $this->sendError('Method not allowed', 405);
        }
        
        $limit = intval($_GET['limit'] ?? 20);
        $limit = min($limit, 100); // Max 100 notifications
        $afterId = intval($_GET['after_id'] ?? 0);
        
        try {
            // Get recent notifications
            if ($afterId > 0) {
                // Get notifications after specific ID (for polling)
                $sql = "SELECT * FROM notifications 
                        WHERE id > ? AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                        ORDER BY created_at DESC 
                        LIMIT ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$afterId, $limit]);
            } else {
                // Get recent notifications from last 24 hours
                $sql = "SELECT * FROM notifications 
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                        ORDER BY created_at DESC 
                        LIMIT ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$limit]);
            }
            
            $notifications = $stmt->fetchAll();
            
            // Format notifications
            $formattedNotifications = array_map(function($notif) {
                return [
                    'id' => $notif['id'],
                    'type' => $notif['type'],
                    'title' => $notif['title'],
                    'message' => $notif['message'],
                    'is_read' => $notif['is_read'],
                    'order_id' => $notif['order_id'],
                    'created_at' => $notif['created_at']
                ];
            }, $notifications);
            
            echo json_encode([
                'success' => true,
                'data' => $formattedNotifications
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to get recent notifications: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Utility function to convert timestamp to "time ago" format
     */
    private function timeAgo($datetime) {
        $timestamp = strtotime($datetime);
        $now = time();
        $diff = $now - $timestamp;
        
        if ($diff < 60) {
            return 'Baru saja';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' menit yang lalu';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' jam yang lalu';
        } else {
            $days = floor($diff / 86400);
            return $days . ' hari yang lalu';
        }
    }
}