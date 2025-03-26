<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();



class Database
{
    private $pdo;
    public function __construct()
    {
        $dbhost = $_ENV['DB_host'];
        $dbname = $_ENV['DB_name'];
        $dbuser = $_ENV['DB_user'];
        $dbpass = $_ENV['DB_pass'];
        try {
            $this->pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8", $dbuser, $dbpass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
}
?>