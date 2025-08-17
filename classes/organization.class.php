<?php
require_once 'database.class.php';

class Organization {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Get all categories from org_categories table
    public function getCategories() {
        $query = "SELECT category_id, category_name FROM org_categories ORDER BY category_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add a new category
    public function addCategory($category_name) {
        $query = "INSERT INTO org_categories (category_name) VALUES (?)";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . implode(" ", $this->conn->errorInfo()));
        }

        if (!$stmt->execute([$category_name])) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception('Failed to add category: ' . implode(" ", $errorInfo));
        }

        return $this->conn->lastInsertId();
    }

    public function deleteCategory($category_id) {
        $query = "DELETE FROM org_categories WHERE category_id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt->execute([$category_id])) {
            $error = $stmt->errorInfo();
            throw new Exception("Failed to delete category: " . $error[2]);
        }
        return true;
    }
    
    // Add a new organization member
    public function addOrganization($data) {
        // If a new category is provided, insert it first
        if (isset($data['category']) && $data['category'] === 'new' && !empty($data['new_category'])) {
            $data['category_id'] = $this->addCategory($data['new_category']);
        }
    
        // Check required fields
        if (empty($data['name']) || empty($data['position']) || empty($data['category_id']) || empty($data['date_appointed']) || empty($data['created_by'])) {
            throw new Exception('Missing required member fields.');
        }
    
        // Prepare image upload
        $imagePath = $this->uploadImage($_FILES['image']);
    
        // Insert organization member
        $query = "INSERT INTO organizational_chart 
                  (name, image, position, category_id, date_appointed, date_ended, created_by, created_at, updated_at, is_deleted) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 0)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['name'],
            $imagePath,
            $data['position'],
            $data['category_id'],
            $data['date_appointed'],
            $data['date_ended'] ?? null,
            $data['created_by']
        ]);
    
        return 'Member Added Successfully!';
    }
    

    public function editOrganization($org_id, $data) {
        // Only validate org_id exists
        $stmt = $this->conn->prepare("SELECT org_id FROM organizational_chart WHERE org_id = ?");
        $stmt->execute([$org_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Member not found');
        }
    
        // Handle image upload if new image was provided
        $imagePath = isset($data['image']) && is_array($data['image']) 
            ? $this->uploadImage($data['image'])
            : $data['existing_image'];
    
        // Build dynamic UPDATE query
        $updates = [];
        $params = [];
        
        if (isset($data['name'])) {
            $updates[] = 'name = ?';
            $params[] = $data['name'];
        }
        
        if (isset($data['position'])) {
            $updates[] = 'position = ?';
            $params[] = $data['position'];
        }
        
        if (isset($data['category_id'])) {
            $updates[] = 'category_id = ?';
            $params[] = $data['category_id'];
        }
        
        if (isset($data['date_appointed'])) {
            $updates[] = 'date_appointed = ?';
            $params[] = $data['date_appointed'];
        }
        
        if (isset($data['date_ended'])) {
            $updates[] = 'date_ended = ?';
            $params[] = $data['date_ended'];
        }
        
        // Always update image and timestamp
        $updates[] = 'image = ?';
        $params[] = $imagePath;
        
        $updates[] = 'updated_at = NOW()';
        
        // Finalize query
        $query = "UPDATE organizational_chart SET " . implode(', ', $updates) . " WHERE org_id = ?";
        $params[] = $org_id;
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt->execute($params)) {
            throw new Exception('Failed to update member');
        }
        
        return 'Member updated successfully';
    }


 

// Soft Delete Organization Member and set date_ended
public function deleteOrganization($org_id) {
    $query = "UPDATE organizational_chart 
              SET is_deleted = 1, 
                  updated_at = NOW(), 
                  date_ended = CURDATE() 
              WHERE org_id = ?";
    $stmt = $this->conn->prepare($query);
    
    if (!$stmt->execute([$org_id])) {
        $error = $stmt->errorInfo();
        throw new Exception("Failed to delete member: " . $error[2]);
    }

    return 'Member Deleted Successfully!';
}


// Revert a past member back to current members
public function revertMember($org_id) {
    $query = "UPDATE organizational_chart 
              SET is_deleted = 0, 
                  date_ended = NULL, 
                  updated_at = NOW() 
              WHERE org_id = ?";
    $stmt = $this->conn->prepare($query);
    
    if (!$stmt->execute([$org_id])) {
        $error = $stmt->errorInfo();
        throw new Exception("Failed to revert member: " . $error[2]);
    }

    return 'Member Reverted Successfully!';
}


    // Get Organization Members
    public function getOrganizations() {
        $query = "SELECT oc.*, cat.category_name 
                  FROM organizational_chart oc
                  LEFT JOIN org_categories cat ON oc.category_id = cat.category_id
                  WHERE oc.is_deleted = 0 
                  ORDER BY oc.date_appointed DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Upload Image and Save Path
private function uploadImage($image) {
    if ($image['error'] === UPLOAD_ERR_OK) {
        $targetDir = __DIR__ . '/../uploads/members/';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $imageInfo = getimagesize($image['tmp_name']);
        if ($imageInfo === false) {
            throw new Exception('Uploaded file is not a valid image.');
        }

        $extension = pathinfo($image['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $extension;
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($image['tmp_name'], $targetFilePath)) {
            return 'uploads/members/' . $fileName;
        } else {
            throw new Exception('Image upload failed.');
        }
    }

    return null;
}


    // Get a single organization member by ID
    public function getOrganizationById($org_id) {
        $query = "SELECT oc.*, cat.category_name 
                  FROM organizational_chart oc
                  LEFT JOIN org_categories cat ON oc.category_id = cat.category_id
                  WHERE oc.org_id = ? AND oc.is_deleted = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$org_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function getMembersWithEndedDate() {
        $sql = "SELECT 
                    oc.org_id, 
                    oc.name, 
                    oc.position, 
                    oc.image, 
                    oc.date_appointed, 
                    oc.date_ended,
                    c.category_name
                FROM organizational_chart oc
                LEFT JOIN org_categories c ON oc.category_id = c.category_id
                WHERE oc.date_ended IS NOT NULL
                ORDER BY oc.date_ended DESC";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log the error
            error_log("Error fetching ended members: " . $e->getMessage());
            throw new Exception("Unable to retrieve ended members: " . $e->getMessage());
        }
    }
    
    
}



?>