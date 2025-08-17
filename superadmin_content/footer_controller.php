<?php
/**
 * Footer Controller
 * Contains functions to manage footer information and social media
 */

/**
 * Get footer information
 * @return array Footer information
 */
function getFooter() {
    global $conn;
    
    try {
        $query = $conn->prepare("SELECT * FROM footer_info LIMIT 1");
        $query->execute();
        
        if ($query->rowCount() > 0) {
            return $query->fetch(PDO::FETCH_ASSOC);
        } else {
            // Create default footer if none exists
            // Get admin_id from session like in sa_add_posts.php
            $admin_id = $_SESSION['admin_id'] ?? null;
            
            if (!$admin_id) {
                throw new Exception("Admin ID not found in session");
            }
            
            $create = $conn->prepare("INSERT INTO footer_info 
                (address, email, phone, copyright_text, created_by) 
                VALUES (?, ?, ?, ?, ?)");
            
            $create->execute([
                'Default Address',
                'contact@example.com',
                '123-456-7890',
                '© ' . date('Y') . ' Your Website. All rights reserved.',
                $admin_id
            ]);
            
            $footer_id = $conn->lastInsertId();
            
            return [
                'footer_id' => $footer_id,
                'address' => 'Default Address',
                'email' => 'contact@example.com',
                'phone' => '123-456-7890',
                'copyright_text' => '© ' . date('Y') . ' Your Website. All rights reserved.',
                'created_by' => $admin_id
            ];
        }
    } catch (PDOException $e) {
        // Log error
        error_log("Database error in getFooter: " . $e->getMessage());
        
        // Return empty footer with error
        return [
            'footer_id' => 0,
            'address' => 'Error retrieving footer',
            'email' => '',
            'phone' => '',
            'copyright_text' => '',
            'created_by' => null,
            'error' => $e->getMessage()
        ];
    } catch (Exception $e) {
        error_log("Error in getFooter: " . $e->getMessage());
        
        return [
            'footer_id' => 0,
            'address' => 'Error: ' . $e->getMessage(),
            'email' => '',
            'phone' => '',
            'copyright_text' => '',
            'created_by' => null,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Get footer social media links
 * @param int $footer_id Footer ID
 * @return array Array of social media links
 */
function getFooterSocials($footer_id) {
    global $conn;
    
    try {
        $query = $conn->prepare("SELECT * FROM footer_socials WHERE footer_id = ? ORDER BY platform ASC");
        $query->execute([$footer_id]);
        
        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log error
        error_log("Database error in getFooterSocials: " . $e->getMessage());
        
        // Return empty array
        return [];
    }
}

/**
 * Update footer information
 * @param array $data Footer data to update
 * @return bool Success status
 */
function updateFooter($data) {
    global $conn;
    
    try {
        $query = $conn->prepare("UPDATE footer_info SET 
            address = ?, 
            email = ?, 
            phone = ?, 
            copyright_text = ? 
            WHERE footer_id = ?");
            
        $query->execute([
            $data['address'],
            $data['email'],
            $data['phone'],
            $data['copyright_text'],
            $data['footer_id']
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Database error in updateFooter: " . $e->getMessage());
        return false;
    }
}

/**
 * Add a new social media link
 * @param array $data Social media data
 * @return bool Success status
 */
function addFooterSocial($data) {
    global $conn;
    
    try {
        $query = $conn->prepare("INSERT INTO footer_socials (platform, url, icon_class, footer_id) VALUES (?, ?, ?, ?)");
        $query->execute([
            $data['platform'],
            $data['url'],
            $data['icon_class'],
            $data['footer_id']
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Database error in addFooterSocial: " . $e->getMessage());
        return false;
    }
}

/**
 * Update a social media link
 * @param array $data Social media data
 * @return bool Success status
 */
function updateFooterSocial($data) {
    global $conn;
    
    try {
        $query = $conn->prepare("UPDATE footer_socials SET platform = ?, url = ?, icon_class = ? WHERE social_id = ?");
        $query->execute([
            $data['platform'],
            $data['url'],
            $data['icon_class'],
            $data['social_id']
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Database error in updateFooterSocial: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a social media link
 * @param int $social_id Social media ID
 * @return bool Success status
 */
function deleteFooterSocial($social_id) {
    global $conn;
    
    try {
        $query = $conn->prepare("DELETE FROM footer_socials WHERE social_id = ?");
        $query->execute([$social_id]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Database error in deleteFooterSocial: " . $e->getMessage());
        return false;
    }
}