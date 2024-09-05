<?php

/**
 * Main Routing file
 */

require_once 'vendor/autoload.php';
require_once 'database.php';
require_once 'api.php';
require_once 'session.php';
require_once 'view.php';
require_once 'handlers/interfaces.php';
require_once 'handlers/handlerFactory.php';

$session = Session::getInstance();
$session->start();
if (!$session->isLoggedIn()) {
	header("Location: /login.php");
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$purifier_config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($purifier_config);

$db = DB::getInstance("database.sqlite3");
$api = API::getInstance($db);
$view = View::getInstance('./views');

$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
	$r->addGroup('/index.php', function ($r) {
		$r->addRoute('GET', '/', 'dashboard');
		$r->addRoute('GET', '/products/', 'products');
		$r->addRoute('GET', '/sales/', 'sales');
		$r->addRoute('GET', '/clients/', 'clients');
		$r->addRoute('GET', '/messages/', 'messages'); 
	});

	$r->addGroup('/index.php/api', function ($r) {
		// READ
		$r->addRoute('GET', '/{property}/', 'api');
		$r->addRoute('GET', '/{property}/{id:\d+}', 'api');

		// CREATE / UPDATE / DELETE
		$r->addRoute(['POST'], '/{property}/edit/', 'api');
		$r->addRoute(['POST', 'DELETE'], '/{property}/edit/{id:\d+}', 'api');
	});

	$r->addRoute('GET', '/index.php/logout/', 'logout');
});

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
if (false !== $pos = strpos($uri, '?')) {
	$uri = substr($uri, 0, $pos);
}

$uri = rawurldecode($uri);
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
	case FastRoute\Dispatcher::NOT_FOUND:
	case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
		$view->render('404');
		break;
	case FastRoute\Dispatcher::FOUND:
		$handler = $routeInfo[1];
		$vars = $routeInfo[2];

		if ($handler == 'handler') {
			$handler = $vars['REQUEST_URI'];
		}

		$handlerInstance = HandlerFactory::create($handler, $session, $db, $purifier, $api, $view);
		if ($handlerInstance) {
			$handlerInstance->handle($vars);
		}

		break;
}
