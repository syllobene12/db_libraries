<?php
namespace App\Core;

abstract class BaseRepository {
    protected \PDO $pdo;
    
    // 子クラスで定義必須
    protected string $table;
    protected string $entityClass;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * 配列をエンティティオブジェクトに変換する
     */
    protected function mapToEntity(array $row): object {
        return new $this->entityClass($row);
    }

    /**
     * IDによる取得
     */
    public function find(int $id): ?object {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $this->mapToEntity($row) : null;
    }

    /**
     * 全件取得
     */
    public function findAll(): array {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table}");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return array_map([$this, 'mapToEntity'], $rows);
    }

    /**
     * 新規作成
     */
    public function create(array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        
        $stmt->execute();
        
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * 更新
     */
    public function update(int $id, array $data): bool {
        $fields = [];
        foreach (array_keys($data) as $key) {
            $fields[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $fields);

        $sql = "UPDATE {$this->table} SET {$setClause} WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        return $stmt->execute();
    }

    /**
     * 削除
     */
    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        return $stmt->execute();
    }
}
