# 📂 ディレクトリ構成

```
project_root/
├── config/
│   └── database.php       # DB接続設定
├── src/
│   ├── Core/              # 基底クラス・コア機能
│   │   ├── Database.php
│   │   ├── EntityReflector.php
│   │   ├── BaseEntity.php
│   │   └── BaseRepository.php
│   ├── Entity/            # テーブル定義
│   │   └── User.php
│   └── Repository/        # テーブル操作
│       └── UserRepository.php
└── index.php              # 実行ファイル
```

# 1. 設定ファイル
config/database.php

# 2. Core（コアライブラリ）
src/Core/Database.php DB接続を管理するシングルトンクラスです。

src/Core/EntityReflector.php リフレクション情報をキャッシュし、型キャストを行うヘルパークラスです。

src/Core/BaseEntity.php 全てのエンティティの親クラスです。データ注入（Hydration）を自動化します。

src/Core/BaseRepository.php 共通のCRUD操作を提供する親クラスです。

# 3. 実装クラス（Userの例）
src/Entity/User.php プロパティの型定義とデフォルト値を記述します。

```PHP
<?php
namespace App\Entity;

use App\Core\BaseEntity;

class User extends BaseEntity {
    // DBのカラム定義に合わせて型を指定
    // 自動キャストにより、DBから文字列で来てもここでintやboolになります
    public ?int $id = null;
    public string $name = 'No Name'; // デフォルト値
    public ?string $email = null;
    public bool $is_active = true;   // TINYINT(1)などをboolへ変換
    
    // ゲッターメソッドを追加してもOK
    public function getDisplayName(): string {
        return $this->name . ($this->is_active ? '' : ' (Inactive)');
    }
}
```

src/Repository/UserRepository.php User固有のDB操作があればここに追加します。

```PHP
<?php
namespace App\Repository;

use App\Core\BaseRepository;
use App\Entity\User;

class UserRepository extends BaseRepository {
    protected string $table = 'users';
    protected string $entityClass = User::class;

    public function findByEmail(string $email): ?User {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE email = :email");
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $row ? $this->mapToEntity($row) : null;
    }
}
```

# 4. 実行ファイル（エントリーポイント）
index.php オートローダーがない場合の簡易的な require と、使用例です。

```PHP
<?php
// 本来はComposerのオートローダーを使うべきですが、簡易的にrequireします
require_once __DIR__ . '/src/Core/Database.php';
require_once __DIR__ . '/src/Core/EntityReflector.php';
require_once __DIR__ . '/src/Core/BaseEntity.php';
require_once __DIR__ . '/src/Core/BaseRepository.php';
require_once __DIR__ . '/src/Entity/User.php';
require_once __DIR__ . '/src/Repository/UserRepository.php';

use App\Core\Database;
use App\Repository\UserRepository;
use App\Entity\User;

// エラー表示（開発用）
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // DB接続とリポジトリ初期化
    $pdo = Database::getConnection();
    $userRepo = new UserRepository($pdo);

    // --- 1. 新規作成 ---
    $newId = $userRepo->create([
        'name'      => 'Tanaka Taro',
        'email'     => 'tanaka@example.com',
        'is_active' => 1 // DBへは1で入るが、取得時にtrueになる
    ]);
    echo "Created ID: {$newId}<br>\n";

    // --- 2. IDで取得 ---
    /** @var User $user */
    $user = $userRepo->find($newId);
    
    if ($user) {
        // 型キャストの確認
        echo "Name: {$user->name} (Type: " . gettype($user->name) . ")<br>\n";
        echo "Active: " . ($user->is_active ? 'Yes' : 'No') . " (Type: " . gettype($user->is_active) . ")<br>\n";
    }

    // --- 3. 全件取得 ---
    $users = $userRepo->findAll();
    echo "Total Users: " . count($users) . "<br>\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```
