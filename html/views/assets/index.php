<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
	throw new Exception("403 - Access Forbidden");
}
