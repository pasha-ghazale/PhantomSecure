<?php
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
    $dotenv->required(['DB_host', 'DB_name', 'DB_user', 'DB_pass'])->notEmpty();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    die("Environment file not found in root directory. Please create .env file.");
} catch (\Dotenv\Exception\ValidationException $e) {
    die("Missing required environment variables: " . $e->getMessage());
}

class Database
{
    private $pdo;
    public function __construct()
    {
        if (
            !isset($_ENV['DB_host']) || !isset($_ENV['DB_name']) ||
            !isset($_ENV['DB_user']) || !isset($_ENV['DB_pass'])
        ) {
            throw new RuntimeException("Database configuration incomplete");
        }

        $dbhost = $_ENV['DB_host'];
        $dbname = $_ENV['DB_name'];
        $dbuser = $_ENV['DB_user'];
        $dbpass = $_ENV['DB_pass'];

        try {
            $this->pdo = new PDO(
                "mysql:host=$dbhost;dbname=$dbname;charset=utf8",
                $dbuser,
                $dbpass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    //get the PDO instance
    public function getConnection()
    {
        return $this->pdo;
    }

    public function userExists($chatId)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE chat_id = :chat_id");
        $stmt->execute(['chat_id' => $chatId]);
        return $stmt->fetch() > 0;
    }

    public function insertUser($chatId)
    {
        $stmt = $this->pdo->prepare("INSERT INTO users (chat_id) VALUES (:chat_id)");
        $stmt->execute(['chat_id' => $chatId]);

    }
    public function getUserLanguage($chatId)
    {
        $stmt = $this->pdo->prepare("SELECT preferred_lang FROM users WHERE chat_id = :chat_id");
        $stmt->execute(['chat_id' => $chatId]);
        return $stmt->fetchColumn() ?: 'en';
    }

    public function setUserLanguage($chatId, $lang)
    {
        $stmt = $this->pdo->prepare("UPDATE users SET preferred_lang = :lang WHERE chat_id = :chat_id");
        $stmt->execute(['chat_id' => $chatId, 'lang' => $lang]);
    }
    public function isAdmin($chatId)
    {
        $stmt = $this->pdo->prepare("SELECT is_admin FROM users WHERE chat_id = :chat_id");
        $stmt->execute(['chat_id' => $chatId]);
        return $stmt->fetchColumn() == 1;
    }
}
?>