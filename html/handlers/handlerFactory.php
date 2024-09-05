<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
	throw new Exception("403 - Access Forbidden");
}

/**
 * Handler factory
 */
class HandlerFactory
{
	public static function create($handler, $session = null, $db = null, $purifier = null, $api = null, $view = null)
	{	
        $params = ['session' => $session, 'db' => $db, 'purifier' => $purifier, 'api' => $api, 'view' => $view];

		switch ($handler) {
			case 'logout':
				return new LogoutHandler($session, $db);
			case 'api':
				return new ApiHandler($purifier, $api);
			default:
				$method = 'render_' . $handler;
				if (method_exists($view, $method)) {
					$view->$method($handler, $params);
				} else {
					$view->render('404');
				}
				break;
		}
	}
}
