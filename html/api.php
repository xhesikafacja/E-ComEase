<?php
require_once 'vendor/autoload.php';
require_once 'database.php';
require_once 'session.php';
require_once 'utils/response.php';

class API
{
	private static $instance = null;
	private $db;

	function __construct(DB $db)
	{
		$this->db = $db;
	}

	public static function getInstance(DB $db)
	{
		if (self::$instance == null) {
			self::$instance = new API($db);
		}

		return self::$instance;
	}

	function checkSid()
	{
		$sid = session_id();
		$data = $this->db->execute_query("SELECT * FROM active_sessions WHERE session_id = ?", [$sid]);
		return count($data) > 0 ? true : false;
	}

	function handleRequest($property = null, $id = null)
	{
		if (!$this->checkSid()) {
			http_response_code(403);
			echo json_encode(array(
				'status' => false,
				'message' => 'Not permitted'
			));
			return;
		}

		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET':
				$this->handleGet($property, $id);
				break;
			case 'POST':
				$this->handlePost($property, $id);
				break;
			case 'DELETE':
				$this->handleDelete($property, $id);
				break;
			default:
				http_response_code(405);
				respondWithJson(false, 'Method not allowed');
				break;
		}
	}

	private function handleGet($property, $id = null)
	{
		$sql = "SELECT * FROM $property";
		if ($id != null) {
			$sql .= " WHERE id = $id";
		}

		echo json_encode($this->db->execute_query($sql));
	}

	private function handlePost($property_name, $id = null)
	{
		$query_type = ($id == null) ? 'INSERT INTO %s (%s) VALUES (%s)' : 'UPDATE %s SET %s WHERE id = %s';
		$data = $_POST;

		$update_pairs = [];
		foreach ($data as $key => $value) {
			$update_pairs[] = "$key = '$value'";
		}

		$query = sprintf(
			$query_type,
			$property_name,
			implode(', ', array_keys($data)),
			implode(', ', array_map(function ($value) {
				return "'$value'";
			}, array_values($data))),
			$id
		);

		if ($id != null) {
			$query = sprintf($query_type, $property_name, implode(', ', $update_pairs), $id);
		}

		$result = $this->db->execute_query($query);
		if ($result && $id != null) {
			return respondWithJson(true, 'Data updated successfully');
		} else {
			return respondWithJson(true, 'Data inserted successfully');
		}
	}

	private function handleDelete($property, $id)
	{
		$query = "DELETE FROM $property WHERE id = $id";
		$result = $this->db->execute_query($query);
		return respondWithJson(true, 'Data deleted successfully');
	}
}
