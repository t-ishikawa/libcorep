<?php 
/**
 * Common Util.
 * Copyright (C)2017-2018 ISHIKAWA Takahiro
 *
 * @author   ISHIKAWA Takahiro <t.ishikawa@itlabj.com>
 * @see      README.txt, LICENCE.txt (LGPL 2.1)
 * @version  9 (PHP 5.4+)
 * @since    2018-08-01
 */

function is_posnum($v) {
	return (is_numeric($v) && $v > 0) ? true : false;
}

function is_posint($v) {
	return is_intnum($v) && $v > 0 ? true : false;
}

function is_intnum($v) {
	return (filter_var($v, FILTER_VALIDATE_INT) !== false) ? true : false; 
}

function to_numeric($v, $sep=',') {
	return str_replace($sep, '', mb_trim($v));
}

function array_sum_safe($a, $keys=false, $sep=',') {
	$r = 0;
	if (!is_array($keys)) $keys = array_keys($a);
	foreach ($keys as $k)
		if (($v = to_numeric($a[$k], $sep)) && is_numeric($v))
			$r += ($v);
	return $r;
}

/* @deprecated -> array_replace() */
function array_overwrite($a, $b) {
	return array_replace($a, $b);
}

/* @deprecated -> array_replace_recursive() */
function array_overwrite_recursive($a, $b) {
	return array_replace_recursive($a, $b);
}

function mb_trim($v, $regex=false) {
	return mb_ltrim(mb_rtrim($v));
}

function mb_ltrim($v, $regex='\s\t\n\r\0\x0B') {
	return preg_replace('/^['.$regex.']*/msu', "\${1}", $v);
}

function mb_rtrim($v, $regex='\s\t\n\r\0\x0B') {
	return preg_replace('/['.$regex.']*$/msu', "\${1}", $v);
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

function formatdate($format, $ts) {
	$tm = strtotime($ts);
	if ((version_compare(PHP_VERSION, '5.1.0') >= 0) && $tm === -1)
		$tm = false;
	return ($tm !== false ? date($format, $tm) : false);
}

/* @deprecated -> money_format() */
function formatnum($v, $format='%!.0n') {
	return (is_numeric($v) ? money_format($format, $v) : false);
}

function formatunit($v, $unit_a=['K','M','G','T'], $div=1000, $format='%!.0n') {
	$r = $v;
	foreach ($unit_a as $i=>$unit)
		if ($v >= ($vv = pow($div,$i)))
			$r = money_format(($i==0?'%!n':$format), $v / $vv).$unit;
	return $r;
}

function splitword($s) {
	if ($a = preg_split('/[\s\t\n\r\0\x0B]/msu', $s))
		foreach ($a as $k=>$v)
			if ($b = mb_trim($v))
				$r[] = $b;
	return empty($r) ? false : $r;
}

// vim: set ts=4:
?>