<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
	throw new Exception("403 - Access Forbidden");
}

require_once 'vendor/autoload.php';
require_once 'database.php';
require_once './utils/growth.php';

/**
 * View class
 */
class View
{
	public static $instance = null;
	private $viewsDir;
	private $loader;
	private $twig;
	private $header;
	private $base;
	private $footer;
	private $db;

	public function __construct($viewsDir)
	{
		$this->viewsDir = $viewsDir;
		$this->loader = new \Twig\Loader\FilesystemLoader($this->viewsDir);
		$this->twig = new \Twig\Environment($this->loader);

		$this->header = $this->twig->load('header.twig');
		$this->base = $this->twig->load('base.twig');
		$this->footer = $this->twig->load('footer.twig');

		$this->db = DB::getInstance("database.sqlite3");
	}

	public static function getInstance($viewsDir)
	{
		if (self::$instance == null) {
			self::$instance = new View($viewsDir);
		}

		return self::$instance;
	}

	public function render($viewName, $data = [])
	{
		$viewFile = $this->viewsDir . '/' . $viewName . '/' . $viewName . '.twig';
		if (!file_exists($viewFile)) {
			throw new Exception("View file not found: $viewFile");
		}

		echo $this->header->render(array(
			'window_title' => strtoupper($viewName),
			'user_logged_in' => $_SESSION['is_loggedin'],
			'user_role' => $_SESSION['user_role'],
			'user_name' => strtoupper($_SESSION['username']),
			'nav_items' => $this->db->execute_query("SELECT * FROM navbar_items"),
			// Add more data as needed
		));

		echo $this->base->render([
			'window_title' => strtoupper($viewName),
			'content' => sprintf('%s/%s.twig', strtolower($viewName), strtolower($viewName)),
			'vars' => $data,
			// Add more data as needed
		]);

		echo $this->footer->render();
	}

	public function render_dashboard($handler, $params)
	{	
		// Fetch data for the dashboard
		$products = $params["db"]->execute_query("SELECT * FROM products");
		$data = [
			'user' => $params["session"]->get('user'),
			'products' => $products,
			'currency' => $_SESSION['currency'],
			'sales' => $params["db"]->execute_query("
			SELECT sales.date, products.name AS 'Product', IFNULL(clients.fname, 'N/A') AS 'Client Name', sales.revenue AS 'Revenue'
			FROM sales
			LEFT JOIN clients ON sales.clientid = clients.id
			JOIN products ON sales.product = products.id
			ORDER BY sales.date DESC
			LIMIT 15
			"),
			'sales_last_week' => $params["db"]->execute_query("
			SELECT sales.date, products.name AS 'Product', IFNULL(clients.fname, 'N/A') AS 'Client Name', sales.revenue AS 'Revenue'
			FROM sales
			LEFT JOIN clients ON sales.clientid = clients.id
			JOIN products ON sales.product = products.id
			WHERE sales.date >= date('now','-7 day')
			ORDER BY sales.date DESC
			LIMIT 5
			"),
		];

		$this->render($handler, $data);
	}

	public function render_products($handler, $params)
	{
		$products = $params["db"]->execute_query("SELECT * FROM products");
		if (!empty($products)) {
			$products_header = array_keys($products[0]);
			foreach ($products as &$product) {
				$product['comments'] = json_decode($product['comments'], true);
			}
		} else {
			$products_header = [];
		}
	
		$data = [
			'user' => $params["session"]->get('user'),
			'products_header' => $products_header,
			'products_data' => $products,
			'currency' => $_SESSION['currency'],
		];
		
		$this->render($handler, $data);
	}

	public function render_sales($handler, $params)
	{
		$sales = $params["db"]->execute_query("
			SELECT sales.date AS 'Sales Date', COUNT(*) AS 'Number of Sales', SUM(sales.revenue) AS 'Revenue'
			FROM sales
			JOIN products ON sales.product = products.id
			GROUP BY sales.date");
		$sale_details = $params["db"]->execute_query("SELECT * FROM sales JOIN products ON sales.product = products.id LEFT JOIN clients ON sales.clientid = clients.id");
		$products = $params["db"]->execute_query("SELECT * FROM products");
		$comments = $params["db"]->execute_query("SELECT id, comments FROM products");
		$clients = $params["db"]->execute_query("SELECT * FROM clients");
		$data = [
			'user' => $params["session"]->get('user'),
			'currency' => $_SESSION['currency'],
			'products' => $products,
			'products_header' => array_keys($products[0]),
			'sales' => $sales,
			'sales_header' => array_keys($sales[0]),
			'sale_details' => $sale_details,
			'comments' => $comments,
			'clients' => $clients,
			// Add more data as needed
		];
		$this->render($handler, $data);
	}

	public function render_clients($handler, $params)
	{
		$clients = $params["db"]->execute_query("SELECT id, fname AS 'First Name', lname AS 'Last Name' FROM clients");
		$client_sales = $params["db"]->execute_query("SELECT * FROM sales JOIN clients ON sales.clientid = clients.id");
		$data = [
			'user' => $params["session"]->get('user'),
			'clients' => $clients,
			'clients_header' => array_keys($clients[0]),
			'client_sales' => $client_sales,
			// Add more data as needed
		];

		$this->render($handler, $data);
	}

	public function render_messages($handler, $params)
	{
		$active_messages = $params["db"]->execute_query("SELECT id, fname || ' ' || lname AS 'Full Name', email AS 'Email', phone AS 'Phone', message AS 'Message', date AS 'Date' FROM messages WHERE archived = '0'");
		$archived_messages = $params["db"]->execute_query("SELECT id, fname || ' ' || lname AS 'Full Name', email AS 'Email', phone AS 'Phone', message AS 'Message', date AS 'Date' FROM messages WHERE archived = '1'");
		$messages_header = !empty($active_messages) ? array_keys($active_messages[0]) : [];
		$data = [
			'user' => $params["session"]->get('user'),
			'all_messages' => $active_messages,
			'archived_messages' => $archived_messages,
			'messages_header' => $messages_header,
			// Add more data as needed
		];
		$this->render($handler, $data);
	}
}
