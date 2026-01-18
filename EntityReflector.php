<?php
namespace App\Core;

use ReflectionClass;
use ReflectionProperty;
use ReflectionNamedType;

class EntityReflector {
    /**
     * クラスごとのプロパティ情報をキャッシュ
     */
    private static array $cache = [];

    /**
     * 指定されたクラスのプロパティ定義（名前と型）を取得してキャッシュする
     */
    public static function getProperties(string $className): array {
        if (isset(self::$cache[$className])) {
            return self::$cache[$className];
        }

        $reflection = new ReflectionClass($className);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        
        $definitions = [];
        foreach ($properties as $property) {
            $name = $property->getName();
            $type = $property->getType();
            
            // 型定義があれば型名を、なければnullを保存
            $typeName = ($type instanceof ReflectionNamedType) ? $type->getName() : null;
            
            $definitions[$name] = [
                'type' => $typeName,
                'nullable' => $type ? $type->allowsNull() : true
            ];
        }

        self::$cache[$className] = $definitions;

        return $definitions;
    }

    /**
     * 型定義に基づいて値をキャストする
     */
    public static function cast(mixed $value, ?string $type, bool $nullable): mixed {
        if ($value === null) {
            return $nullable ? null : $value;
        }

        if ($type === null) {
            return $value;
        }

        return match ($type) {
            'int'     => (int) $value,
            'float'   => (float) $value,
            'string'  => (string) $value,
            'bool'    => (bool) $value,
            'array'   => is_string($value) ? json_decode($value, true) : (array) $value,
            default   => $value,
        };
    }
}
