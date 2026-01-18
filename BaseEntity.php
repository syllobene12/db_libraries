<?php
namespace App\Core;

abstract class BaseEntity {
    
    public function __construct(array $data = []) {
        $this->hydrate($data);
    }

    protected function hydrate(array $data): void {
        // キャッシュ対応済みのリフレクターからプロパティ情報を取得
        $properties = EntityReflector::getProperties(static::class);

        foreach ($properties as $propName => $meta) {
            // DBデータにそのカラムが存在する場合のみセット
            if (array_key_exists($propName, $data)) {
                
                // リフレクターを使ってキャスト
                $castedValue = EntityReflector::cast(
                    $data[$propName], 
                    $meta['type'], 
                    $meta['nullable']
                );

                $this->$propName = $castedValue;
            }
        }
    }

    public function toArray(): array {
        return get_object_vars($this);
    }
}
