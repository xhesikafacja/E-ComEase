<?php

/**
 * Login logic
 */

require_once 'vendor/autoload.php';
require_once 'database.php';
require_once 'session.php';
require_once 'utils/response.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$purifier_config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($purifier_config);

$db = DB::getInstance("database.sqlite3");
$session = Session::getInstance();
$session->start();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	$loader = new \Twig\Loader\FilesystemLoader('views');
	$twig = new \Twig\Environment($loader);

	if (isset($_SESSION['is_loggedin'])) {
		header("Location: /index.php/");
		die();
	}

	$header = $twig->load('/header.twig');
	$template = $twig->load('/login/login.twig');

	echo $header->render(array(
		'window_title' => 'Login',
		'user_logged_in' => false
	));
	echo $template->render(array(
		'title' => 'Login',
		'content' => 'Login'
	));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$username = $purifier->purify($_POST['username']);
	$password = $purifier->purify($_POST['password']);

	$result = $db->execute_query("SELECT * FROM users WHERE username = ? LIMIT 1", [$username]);

	if (count($result) > 0) {
		$row = $result[0];
		if (password_verify($password, $row['password'])) {
			try {
				$_SESSION['user_id'] = $row['id'];
				$_SESSION['user_role'] = $row['is_admin'] == 1 ? 'admin' : 'user';
				$_SESSION['group_id'] = $row['group'];
				$_SESSION['username'] = $username;
				$_SESSION['is_loggedin'] = true;
				$_SESSION['currency'] = $row['currency'];
				$db->create_session_id($row['id']);

				setcookie('username', $username, time() + 3600, '/');
				respondWithJson(true, 'Login successful');
			} catch (Exception $e) {
				respondWithJson(false, $e->getMessage());
			}
		} else {
			respondWithJson(false, 'Wrong Credentials');
		}
	} else {
		respondWithJson(false, 'User not found');
	}
}
