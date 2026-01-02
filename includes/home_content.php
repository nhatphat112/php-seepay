<?php
/**
 * Home Content Loader
 * Load và render content từ database cho index.php
 */

require_once __DIR__ . '/../connection_manager.php';

class HomeContent {
    private static $db = null;
    
    private static function getDB() {
        if (self::$db === null) {
            self::$db = ConnectionManager::getAccountDB();
        }
        return self::$db;
    }
    
    /**
     * Get Sliders
     */
    public static function getSliders() {
        try {
            $db = self::getDB();
            $stmt = $db->query("SELECT ImagePath, LinkURL FROM TB_HomeSlider WHERE IsActive = 1 ORDER BY DisplayOrder ASC, SliderID ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get News by Category
     */
    public static function getNews($category = null) {
        try {
            $db = self::getDB();
            if ($category && $category !== 'Tất Cả') {
                $stmt = $db->prepare("SELECT Category, Title, LinkURL, CreatedDate FROM TB_News WHERE Category = ? AND IsActive = 1 ORDER BY DisplayOrder ASC, CreatedDate DESC");
                $stmt->execute([$category]);
            } else {
                $stmt = $db->query("SELECT Category, Title, LinkURL, CreatedDate FROM TB_News WHERE IsActive = 1 ORDER BY DisplayOrder ASC, CreatedDate DESC");
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get Social Links
     */
    public static function getSocialLinks() {
        try {
            $db = self::getDB();
            $stmt = $db->query("SELECT LinkType, LinkURL, DisplayName FROM TB_SocialLinks ORDER BY DisplayOrder ASC");
            $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [];
            foreach ($links as $link) {
                $result[$link['LinkType']] = [
                    'url' => $link['LinkURL'],
                    'name' => $link['DisplayName']
                ];
            }
            return $result;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get Server Info
     */
    public static function getServerInfo() {
        try {
            $db = self::getDB();
            $stmt = $db->query("SELECT TOP 1 Content FROM TB_ServerInfo ORDER BY InfoID DESC");
            $info = $stmt->fetch(PDO::FETCH_ASSOC);
            return $info ? $info['Content'] : '';
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Get Weekly Events
     */
    public static function getWeeklyEvents() {
        try {
            $db = self::getDB();
            $stmt = $db->query("SELECT EventTime, EventDay, EventTitle FROM TB_WeeklyEvents WHERE IsActive = 1 ORDER BY DisplayOrder ASC, EventID ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get QR Code
     */
    public static function getQRCode() {
        try {
            $db = self::getDB();
            $stmt = $db->query("SELECT TOP 1 ImagePath, Description FROM TB_QRCode ORDER BY QRCodeID DESC");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
}
?>

