<?php
/**@file
 * PHP Simple Framework & Utility Functions.
 * Copyright (C)2009-2015 ISHIKAWA Takahiro.
 *
 * @author   ISHIKAWA Takahiro <t.ishikawa@itlabj.com>
 * @see      README.txt, LICENCE.txt (LGPL 2.1)
 * @version  7.2  (PHP 5.3 or above required)
 */

define('FRAMEWORK_NAME'		, 'libframework');
define('FRAMEWORK_VERSION'	, '7.2');
define('FRAMEWORK_UPDATE'	, '2015-02-18');

/**
 * Framework class.
 * 
 * @author (C)2009 ISHIKAWA Takahiro <t.ishikawa@itlabj.com>
 */
class Framework {
	static $SS			= 'FRAMEWORK';
	static $PAGE		= array();
	static $CONFIG		;
	static $CONST		;
	static $SELF		;
	static $CONTROLLER	;

	static function init() {
		self::$PAGE['startup']	= microtime(true);
		self::$SELF				= mb_basename(preg_replace('/^(.*)\?.*$/', "\${1}", $_SERVER['REQUEST_URI']));
		if (self::$SELF == '')
			self::$SELF			= 'index';
		self::$CONTROLLER		= filename($_SERVER['SCRIPT_FILENAME']);
	}
	static function loadConfig($f, $sec=false) {
		$a = parse_ini_file($f,$sec);
		foreach ($a as $k=>&$v) if (preg_match('/^(.*)\.(array|hash)$/',$k,$m)) {
			$b = explode(',',trim($v));
			foreach ($b as $v2) {
				if ($m[2]=='hash') { 
					$c = preg_split('/=/',$v2); $a[$m[1]][$c[0]] = preg_replace('/&equal;/','=',$c[1]); 
				} else $a[$m[1]][] = preg_replace('/&comma;/',',',$v2);
			}
		}
		self::$CONFIG = $a;
	}
	static function loadResource($lang) {
		include_once('resource.'.$lang.'.php');
		self::$CONST = $CONST;
	}
	static function redirect($url) {
		header('Location: '.$url);
		exit;
	}
	static function output($inc, $P=null) {
		include($inc);
	}
	static function isPost() {
		return ($_SERVER['REQUEST_METHOD']=='POST' ? true : false);
	}
	static function nl2br($s) {
		return str_replace("\n", "<br>", $s);
	}
	static function log($v, $lv=LOG_WARNING) {
		syslog($lv, $v."\n"); 
	}
}
class FrameworkException extends Exception {
	function __construct($msg=null, $code=0, Exception $prev=null) {
		if (is_array($msg)) 
			$msg = join("\n", $msg);
		parent::__construct($msg,$code,$prev);
	}
}

function is_posint($v) {
	return (is_numeric($v) && floor($v)==$v && $v>0) ? true : false;
}
function is_posnum($v) {
	return (is_numeric($v) && $v>0) ? true : false;
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
function formatdate($fmt, $ts) {
	return $ts ? date($fmt, strtotime($ts)) : null;
}
function splitword($s) {
	if ($a = preg_split('/[　 \r\n\t]/msu',$s))
		foreach ($a as $k=>$v)
			if ($b = mb_trim($v))
				$r[] = $b;
	return empty($r) ? false : $r;
}
// vim: ts=4 
?>