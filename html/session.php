<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    throw new Exception("403 - Access Forbidden");
}

class Session
{
    private static $instance = null;

    private function __construct()
    {
        $this->start();
    }

    public static function getInstance()
    {
        if (self::$instance == null)
        {
            self::$instance = new Session();
        }

        return self::$instance;
    }

    public function start()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    public function destroy()
    {
        session_destroy();
    }

    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function get($key)
    {
        return $_SESSION[$key] ?? null;
    }
}