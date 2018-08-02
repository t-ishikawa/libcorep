<?php
/**
 * PHP Micro Framework & Utility Functions.
 * Copyright (C)2009-2018 ISHIKAWA Takahiro.
 *
 * @author   ISHIKAWA Takahiro <t.ishikawa@itlabj.com>
 * @see      README.txt, LICENCE.txt (LGPL 2.1)
 * @version  9 (PHP 5.4+)
 * @since    2018-08-01
 */

/* @deprecated -> Framework::FRAMEWORK_NAME */
define('FRAMEWORK_NAME'		, 'libframework');
/* @deprecated -> Framework::FRAMEWORK_VERSION */
define('FRAMEWORK_VERSION'	, '9');
/* @deprecated -> Framework::FRAMEWORK_UPDATE */
define('FRAMEWORK_UPDATE'	, '2018-08-01');

// require_once('libcommon.php');

/**
 * Framework class.
 *
 * @author (C)2009-2016 ISHIKAWA Takahiro <t.ishikawa@itlabj.com>
 */

class Framework {

	const FRAMEWORK_NAME		= 'libframework';
	const FRAMEWORK_VERSION		= '9';
	const FRAMEWORK_UPDATE		= '2018-08-01';

	use FrameworkLogTrait;

	public  $CONFIG			;
	public  $RES			;
	public  $SELF			;
	public	$AUTOLOADER		;
	public  $CONTROLLER		;
	public  $PAGE			;
	public  $VIEW       	; // Implements FrameworkViewInterface
	public  $AUTH			; // Implements FrameworkAuthInterface
	public	$SESSION		; // Implements FrameworkSessionInterface
	public  $MESSAGE		; // Implements FrameworkMessageInterface

	function __construct() {
		$this->PAGE['startup'] = microtime(true);
	}

	function init() {
		$this->SELF				= mb_basename(preg_replace('/^(.*)\?.*$/', "\${1}", $_SERVER['REQUEST_URI']));
		if ($this->SELF == '')
			$this->SELF			= 'index';
		$this->CONTROLLER		= filename($_SERVER['SCRIPT_FILENAME']);
		$this->CLASS_DIR		= './';
	}

	function autoloadControllerClass($class_name) {
		require_once($this->CLASS_DIR.strtolower(preg_replace('/Page$/','',$class_name)).'.php');
	}

	function autoloadController($class_name=false) {
		if (!$class_name)
			$class_name = $this->SELF;
		spl_autoload_register(array($this, 'autoloadControllerClass'));
		$this->CONTROLLER = basename($class_name);
		$controller_class = ucfirst($this->CONTROLLER).'Page';
		$this->VIEW = new $controller_class($this);
		$this->VIEW->initPage();
		$this->VIEW->command();
		$this->VIEW->endPage();
	}

	function loadConfig($f, $sec=false, $callback=false) {
		$a = parse_ini_file($f,$sec);
		foreach ($a as $k=>$v) if (preg_match('/^(.*)\.(array|hash)$/',$k,$m))
			$a[$m[1]] = ($m[2]=='hash') ? decode_hash($v) : decode_array($v);
		if ($callback)
			array_walk_recursive($a, $callback);
		foreach ($a as $k=>$v)
			$this->CONFIG[$k] = $v;
	}

	function loadResource($f) {
		global $RES;
		include_once($f);
		$this->RES = &$RES;
	}

	function loadAuth($auth) {
		$this->AUTH = $auth;
		$this->AUTH->init();
	}

	function redirect($url) {
		header('Location: '.$url);
		exit;
	}

	function output($inc, $P=null) {
		include($inc);
	}

	function isPost() {
		return ($_SERVER['REQUEST_METHOD']=='POST' ? true : false);
	}

}

interface FrameworkViewInterface {
	public function initPage();
	public function doFilter($P=false, $filterfunc=false);
	public function doAuth($auth=true, $role=false);
	public function command($CMD=false);
	public function output($inc, $P=false, $filterfunc=false);
	public function endPage();
}

interface FrameworkSessionInterface {
	public function init();
	public function get($k, $v);
	public function set($k, $v);
	public function clear();
}

interface FrameworkMessageInterface {
	public function init();
	public function get($type=false);
	public function set($type, $msg, $p=false);
	public function clear();
	public function output($v);
}

interface FrameworkAuthInterface {
	public function init();
	public function auth();
}

class FrameworkException extends Exception {
	function __construct($msg=null, $code=0, Exception $prev=null) {
		if (is_array($msg))
			$msg = join("\n", $msg);
		parent::__construct($msg,$code,$prev);
	}
}

trait FrameworkLogTrait {

	function log($v, $lv=LOG_WARNING) {
		syslog($lv, $v."\n");
	}
	
}

// vim: set ts=4:
?>