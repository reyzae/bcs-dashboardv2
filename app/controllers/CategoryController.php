<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/BaseController.php';

/**
 * Bytebalok Category Controller
 * Handles category management operations
 */

class CategoryController extends BaseController {
    private $categoryModel;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->categoryModel = new Category($pdo);
    }
    
    /**
     * Get list of categories
     */
    public function list() {
        $categories = $this->categoryModel->findAllWithProductCount();
        $this->sendSuccess($categories);
    }
    
    /**
     * Get active categories only
     */
    public function getActive() {
        $categories = $this->categoryModel->getActive();
        $this->sendSuccess($categories);
    }
    
    /**
     * Get single category
     */
    public function get() {
        $id = intval($_GET['id']);
        
        if (!$id) {
            $this->sendError('Category ID is required', 400);
        }
        
        $category = $this->categoryModel->find($id);
        
        if (!$category) {
            $this->sendError('Category not found', 404);
        }
        
        $this->sendSuccess($category);
    }
    
    /**
     * Create new category
     */
    public function create() {
        $this->requireRole(['admin', 'manager']);
        
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $data = $this->getRequestData();
        $this->validateRequired($data, ['name']);
        
        // Check if category name already exists
        if ($this->categoryModel->nameExists($data['name'])) {
            $this->sendError('Category name already exists', 400);
        }
        
        $categoryId = $this->categoryModel->create($data);
        
        if ($categoryId) {
            $this->logAction('create', 'categories', $categoryId, null, $data);
            $this->sendSuccess(['id' => $categoryId], 'Category created successfully');
        } else {
            $this->sendError('Failed to create category', 500);
        }
    }
    
    /**
     * Update category
     */
    public function update() {
        $this->requireRole(['admin', 'manager']);
        
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $id = intval($_GET['id']);
        if (!$id) {
            $this->sendError('Category ID is required', 400);
        }
        
        $data = $this->getRequestData();
        
        // Check if category exists
        $oldCategory = $this->categoryModel->find($id);
        if (!$oldCategory) {
            $this->sendError('Category not found', 404);
        }
        
        // Check if category name already exists (excluding current category)
        if (isset($data['name']) && $this->categoryModel->nameExists($data['name'], $id)) {
            $this->sendError('Category name already exists', 400);
        }
        
        $success = $this->categoryModel->update($id, $data);
        
        if ($success) {
            $this->logAction('update', 'categories', $id, $oldCategory, $data);
            $this->sendSuccess(null, 'Category updated successfully');
        } else {
            $this->sendError('Failed to update category', 500);
        }
    }
    
    /**
     * Delete category
     */
    public function delete() {
        $this->requireRole(['admin', 'manager']);
        
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $id = intval($_GET['id']);
        if (!$id) {
            $this->sendError('Category ID is required', 400);
        }
        
        // Check if category exists
        $category = $this->categoryModel->find($id);
        if (!$category) {
            $this->sendError('Category not found', 404);
        }
        
        // Check if category has products
        $sql = "SELECT COUNT(*) as count FROM products WHERE category_id = ? AND is_active = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $this->sendError('Cannot delete category with existing products', 400);
        }
        
        // Soft delete by setting is_active to 0
        $success = $this->categoryModel->update($id, ['is_active' => 0]);
        
        if ($success) {
            $this->logAction('delete', 'categories', $id, $category, ['is_active' => 0]);
            $this->sendSuccess(null, 'Category deleted successfully');
        } else {
            $this->sendError('Failed to delete category', 500);
        }
    }
    
    /**
     * Get category statistics
     */
    public function getStats() {
        $stats = $this->categoryModel->getStats();
        $this->sendSuccess($stats);
    }

    public function deduplicate() {
        $this->requireRole(['admin','manager']);
        $sql = "SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name, id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $groups = [];
        foreach ($rows as $r) {
            $key = strtolower(trim(preg_replace('/\s+/', ' ', $r['name'])));
            if (!isset($groups[$key])) { $groups[$key] = []; }
            $groups[$key][] = (int)$r['id'];
        }
        $changes = [];
        foreach ($groups as $key => $ids) {
            if (count($ids) < 2) { continue; }
            sort($ids);
            $primary = $ids[0];
            $dups = array_slice($ids, 1);
            foreach ($dups as $dupId) {
                $u1 = $this->pdo->prepare("UPDATE products SET category_id = ? WHERE category_id = ?");
                $u1->execute([$primary, $dupId]);
                $u2 = $this->pdo->prepare("UPDATE categories SET is_active = 0 WHERE id = ?");
                $u2->execute([$dupId]);
                $changes[] = ['primary' => $primary, 'removed' => $dupId];
            }
        }
        $this->sendSuccess(['changes' => $changes]);
    }
}

