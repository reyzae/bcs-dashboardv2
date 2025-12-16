<?php
/**
 * Bytebalok Base Model
 * Abstract base class for all models with common functionality
 */

require_once __DIR__ . '/../helpers/Debug.php';

abstract class BaseModel
{
    protected $pdo;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $timestamps = true;
    // Cache table columns to avoid repeated DESCRIBE calls
    private $tableColumns = null;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Find record by ID
     */
    public function find($id)
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);

            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                Debug::error('Query failed in find()', new Exception($errorInfo[2]));
                return false;
            }

            return $stmt->fetch();
        } catch (PDOException $e) {
            Debug::error('PDO Error in find() - Table: ' . $this->table . ', ID: ' . $id, $e);
            return false;
        }
    }

    /**
     * Find all records with optional conditions
     */
    public function findAll($conditions = [], $orderBy = null, $limit = null, $offset = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }

        // SECURITY: Validasi ORDER BY untuk mencegah SQL injection
        if ($orderBy) {
            // Parse ORDER BY (format: "column" atau "column ASC/DESC")
            $parts = explode(' ', trim($orderBy));
            $column = $parts[0];
            $direction = strtoupper($parts[1] ?? 'ASC');

            // Validasi direction hanya ASC atau DESC
            if (!in_array($direction, ['ASC', 'DESC'])) {
                $direction = 'ASC';
            }

            // Validasi column name (hanya huruf, angka, underscore)
            if (preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
                $sql .= " ORDER BY {$column} {$direction}";
            }
        }

        // SECURITY: Validasi LIMIT dan OFFSET sebagai integer
        if ($limit) {
            $limit = (int) $limit;
            if ($limit > 0) {
                $sql .= " LIMIT ?";
                $params[] = $limit;

                if ($offset) {
                    $offset = (int) $offset;
                    $sql .= " OFFSET ?";
                    $params[] = $offset;
                }
            }
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Create new record
     */
    public function create($data)
    {
        try {
            // Debug: Consolidated logging for better performance
            if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
                Debug::log('BaseModel->create()', [
                    'table' => $this->table,
                    'data_before' => $data,
                    'fillable' => $this->fillable
                ]);
            }

            $data = $this->filterFillable($data);

            // Debug: Log filtered data only in debug mode
            if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
                Debug::log('Data after filterFillable', $data);
            }

            if ($this->timestamps) {
                // Only include timestamps if columns exist in the table
                $now = date('Y-m-d H:i:s');
                if ($this->hasColumn('created_at')) {
                    $data['created_at'] = $now;
                }
                if ($this->hasColumn('updated_at')) {
                    $data['updated_at'] = $now;
                }
            }

            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');

            $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute(array_values($data));

            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                Debug::error('Execute failed', new Exception($errorInfo[2]));
                throw new Exception("Failed to insert into {$this->table}: " . $errorInfo[2]);
            }

            $insertedId = $this->pdo->lastInsertId();

            // Debug: Log success only in debug mode
            if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
                Debug::log('Insert successful', [
                    'table' => $this->table,
                    'id' => $insertedId,
                    'sql' => $sql
                ]);
            }

            return $insertedId;
        } catch (PDOException $e) {
            Debug::error('PDO Error in BaseModel->create()', $e);
            throw new Exception("Database error creating record in {$this->table}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update record by ID
     */
    public function update($id, $data)
    {
        // Debug: Consolidated logging for better performance
        if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
            Debug::log('BaseModel->update()', [
                'table' => $this->table,
                'id' => $id,
                'data_before' => $data
            ]);
        }

        $data = $this->filterFillable($data);

        // Debug: Log filtered data only in debug mode
        if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
            Debug::log('Data after filterFillable', $data);
        }

        if ($this->timestamps) {
            // Only include updated_at if column exists
            if ($this->hasColumn('updated_at')) {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
        }

        $fields = array_keys($data);
        $setClause = array_map(function ($field) {
            return "{$field} = ?";
        }, $fields);

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE {$this->primaryKey} = ?";
        $params = array_merge(array_values($data), [$id]);

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete record by ID
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Count records with optional conditions
     */
    public function count($conditions = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['count'];
    }

    /**
     * Execute raw SQL query
     */
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Filter data to only include fillable fields
     */
    protected function filterFillable($data)
    {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Hide sensitive fields from output
     */
    public function hideFields($data)
    {
        if (empty($this->hidden)) {
            return $data;
        }

        return array_diff_key($data, array_flip($this->hidden));
    }

    /**
     * Check if a column exists in the current model's table.
     */
    protected function hasColumn($column)
    {
        try {
            if ($this->tableColumns === null) {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM {$this->table}");
                $this->tableColumns = array_map(function ($row) {
                    return $row['Field'];
                }, $stmt->fetchAll());
            }
            return in_array($column, $this->tableColumns);
        } catch (PDOException $e) {
            // If we cannot determine columns, fail safe: do not add timestamps
            Debug::error('Failed to get columns for table: ' . $this->table, $e);
            return false;
        }
    }
}
