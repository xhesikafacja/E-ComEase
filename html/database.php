<?php
require_once 'vendor/autoload.php';

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
	throw new Exception("403 - Access Forbidden");
}

class DB
{
	private static $instance = null;
	private $conn;
	private $purifier_config;
	private $purifier;

	private function __construct($database_path)
	{
		$this->create_db_conn($database_path);
		$this->purifier_config = HTMLPurifier_Config::createDefault();
		$this->purifier = new HTMLPurifier($this->purifier_config);
	}

	public static function getInstance($database_path)
	{
		if (self::$instance == null) {
			self::$instance = new DB($database_path);
		}
		return self::$instance;
	}

	function create_db_conn($database_path)
	{
		try {
			$this->conn = new PDO("sqlite:" . $database_path);
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			throw new Exception("Connection failed: " . $e->getMessage());
		}
	}

	function execute_query($sql, $params = [])
	{
		try {
			$stmt = $this->conn->prepare($sql);
			if (!$stmt) {
				throw new Exception("Failed to prepare statement: " . implode(", ", $this->conn->errorInfo()));
			}			
			foreach ($params as $key => $val) {
				$stmt->bindValue($key + 1, $val);
			}
			if (!$stmt->execute()) {
				throw new Exception("Failed to execute statement: " . implode(", ", $stmt->errorInfo()));
			}
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			throw new Exception("Query failed: " . $e->getMessage());
		}
	}

	public function errorInfo()
	{
		return $this->conn->errorInfo();
	}

	function close_connection()
	{
		$this->conn = null;
	}

	function update_last_login($uid)
	{
		$this->execute_query("UPDATE users SET last_login = datetime('now') WHERE id = ?", [$uid]);
	}

	function create_session_id($uid)
	{
		$this->update_last_login($uid);
		$this->execute_query("INSERT INTO `active_sessions` (uid, session_id) VALUES (?, ?)", [$uid, session_id()]);
	}

	function destroy_session_id($uid)
	{
		$this->execute_query("DELETE FROM `active_sessions` WHERE uid = ?", [$uid]);
	}
}
