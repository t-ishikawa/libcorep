<?php
/**
 * PHP Micro Framework & Utility Functions.
 * Copyright (C)2009-2015 ISHIKAWA Takahiro.
 *
 * @author   ISHIKAWA Takahiro <t.ishikawa@itlabj.com>
 * @see      README.txt, LICENCE.txt (LGPL 2.1)
 * @version  8  (PHP 5.3 or above required)
 */

define('FRAMEWORK_NAME'		, 'libframework');
define('FRAMEWORK_VERSION'	, '8');
define('FRAMEWORK_UPDATE'	, '2016-01-17');

/**
 * Framework class.
 * 
 * @author (C)2009-2016 ISHIKAWA Takahiro <t.ishikawa@itlabj.com>
 */

class Framework {
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

	function init() {
		$this->PAGE['startup']	= microtime(true);
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

	function log($v, $lv=LOG_WARNING) {
		syslog($lv, $v."\n"); 
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

//
// Common functions:
//

function is_posint($v) {
	return (is_numeric($v) && floor($v)==$v && $v>0) ? true : false;
}

function is_posnum($v) {
	return (is_numeric($v) && $v>0) ? true : false;
}

function array_sum_safe($a, $keys=false) {
	$r = 0;
	if (!is_array($keys)) $keys = array_keys($a);
	foreach ($keys as $k) if (($v=str_replace(',','',trim($a[$k]))) && is_numeric($v))
		$r += $v;
	return $r;
}

function array_overwrite($a, $b) {
	if (!is_array($b)) return false;
	foreach ($b as $k=>$v) 
		$a[$k] = $v;
	return $a;
}

function array_overwrite_recursive($a, $b) {
	if (!is_array($b)) return false;
	foreach ($b as $k=>$v) {
		if (is_array($a[$k]))
			$a[$k] = ($vv = array_overwrite_recursive($a[$k], $v)) ? $vv : $v;
		else
			$a[$k] = $v;
	}
	return $a;
}

function mb_trim($v) {
	return preg_replace("/^[\s\r\n\t　](.*)[\s\r\n\t　]$/msu", "\${1}", $v);
}

function mb_basename($v) {
	return preg_replace("/^.*\/(.*?)$/u", "\${1}", $v);
}

function filename($v) {
	return preg_replace('/^(.*)\.(.*?)$/', "\${1}", mb_basename($v));
}

function fileext($v) {
	return preg_replace('/^(.*)\.(.*?)$/', "\${2}", mb_basename($v));
}

function formatdate($fmt, $ts) {
	return $ts ? date($fmt, strtotime($ts)) : null;
}

function formatnum($v, $format='%!.0n') {
	return (is_numeric($v) ? money_format($format, $v) : false);
}

function formatunit($v, $unit_a, $div=1000, $format='%!.0n') {
	$r = $v;
	foreach ($unit_a as $i=>$unit)
		if ($v >= ($vv = pow($div,$i)))
			$r = formatnum($v / $vv, ($i==0?'%!n':$format)).$unit;
	return $r;
}

function splitword($s) {
	if ($a = preg_split('/[　 \r\n\t]/msu',$s))
		foreach ($a as $k=>$v)
			if ($b = mb_trim($v))
				$r[] = $b;
	return empty($r) ? false : $r;
}

function decode_array($a) {
	if ($b = explode(',', $a)) foreach ($b as $v)
		$r[] = str_replace('&comma;',',',trim($v));
	return !empty($r) ? $r : false;
}

function decode_hash($kv) {
	if ($b = explode(',', $kv)) foreach ($b as $v)
		if (preg_match('/^(.*?)=(.*)$/', $v, $m))
			$r[$m[1]] = str_replace('&equal;','=',$m[2]);
	return !empty($r) ? $r : false;
}

// vim: set ts=4:
?>