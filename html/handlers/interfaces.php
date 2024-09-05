<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
	throw new Exception("403 - Access Forbidden");
}

require_once 'session.php';
require_once 'database.php';

/**
 * Handler interface
 */
interface Handler
{
	public function handle($vars);
}

/**
 * Logout handler
 */
class LogoutHandler implements Handler
{
	private $session;
	private $db;

	public function __construct(Session $session, DB $db)
	{
		$this->session = $session;
		$this->db = $db;
	}

	public function handle($vars)
	{
		$this->session->destroy();
		$this->db->destroy_session_id($_SESSION['user_id']);
		$this->db->close_connection();
		header("Location: /login.php");
	}
}

/**
 * API handler
 */
class ApiHandler implements Handler
{
	private $purifier;
	private $api;

	public function __construct($purifier, $api)
	{
		$this->purifier = $purifier;
		$this->api = $api;
	}

	public function handle($vars)
	{
		$property = $this->purifier->purify($vars['property']);
		$id = isset($vars['id']) ? $this->purifier->purify($vars['id']) : null;
		
		$this->api->handleRequest($property, $id);
	}
}
