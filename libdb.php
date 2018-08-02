<?php
/**
 * Database Access Library.
 * Copyright (C)2009-2018 ISHIKAWA Takahiro.
 *
 * @author   ISHIKAWA Takahiro <t.ishikawa@itlabj.com>
 * @see      README.txt, LICENCE.txt (LGPL 2.1)
 * @version  9 (PHP 5.4+)
 * @since    2018-08-01
 */

/**
 * DbException class.
 *
 * @author (C)2009 ISHIKAWA Takahiro <ishikawa@itlabj.com>
 */
class DbException extends Exception {

}

/**
 * DbConn class.
 *
 * @author (C)2009 ISHIKAWA Takahiro <ishikawa@itlabj.com>
 */
class DbConn extends PDO  {
	public    static $DEFAULT_CONF	= false;
	protected static $VARTYPE 		= array('integer'=>PDO::PARAM_INT ,'double'=>PDO::PARAM_INT,'boolean'=>PDO::PARAM_BOOL,'string'=>PDO::PARAM_STR,'null'=>PDO::PARAM_NULL,'unknown type'=>PDO::PARAM_NULL);

	protected $conf;
	protected $debug = false;
	protected $rs = false;
	protected $st = false;
	protected $sql = false;

	function __construct($dsn,$user=null,$pass=null,$dopt=array()) {
		parent::__construct($dsn,$user,$pass,$dopt);
	}

	function conf($v=false) {
		if ($v!==false) $this->conf=$v; else return $this->conf;
	}

	function exec($sql) {
		if ($this->debug)
			error_log($sql."\n");
		if (($r = parent::exec(($this->sql = $sql)))===false)
			throw new DbException($this->getError());
		return $r;
	}

	function pquery($sql, $bind) {
		if ($this->rs)
			$this->rs->closeCursor();
		if ($this->debug)
			error_log($sql."; [".join(',',$bind)."] ");
		$this->rs = parent::prepare($sql);
		if ($this->rs===false || $this->rs->execute($bind)===false)
			throw new DbException($this->getError());
		return $this->rs;
	}

	function select($table, $key=false) {
		if (is_array($key)) foreach ($key as $k=>$v)
			$w[] = $k."=".PDO::quote($v);
		$this->rs = $r = $this->query("SELECT * FROM ".$table.($key ? " WHERE ".join(" AND ",$w): ""));
		if ($r===false)
			throw new DbException($this->getError());
		return $r;
	}

	function insert($table, $row) {
		$s1 = $s2 = '';
		if (!is_array($row))
			return false;
		foreach ($row as $k=>$v) {
			$s1[] = $k;
			$s2[] = $v===null ? 'NULL': PDO::quote($v);
		}
		$r = $this->exec("INSERT INTO ".$table." (".join(',',$s1).") VALUES(".join(',',$s2).");");
		if ($r===false) {
			error_log("ERROR SQL: ".$this->sql);
			throw new DbException($this->getError());
		}
		return $r;
	}

	function update($table, $row, $key) {
		$s = array(); $w = array();
		foreach ($row as $k=>$v)
			$s[] = "$k = ".($v===null?'NULL':PDO::quote($v));
		foreach ($key as $k=>$v)
			$w[] = "$k = ".($v===null?'NULL':PDO::quote($v));
		$r = $this->exec("UPDATE ".$table." SET ".join(",",$s)." WHERE ".join(" AND ",$w));
		if ($r===false) {
			error_log("ERROR SQL: ".$this->sql);
			throw new DbException($this->getError());
		}
		return $r;
	}

	function delete($table, $key) {
		$cond = '';
		if (is_array($key)) {
			foreach ($key as $k=>$v)
				$cond .= ' AND '.$k.'='.PDO::quote($v);
			$cond = " WHERE ".trim($cond, " AND ");
		}
		$r = $this->exec("DELETE FROM ".$table.$cond);
		if ($r===false)
			throw new DbException($this->getError());
		return $r;
	}

	function begin() {
		return $this->beginTransaction();
	}

	function getLastId($k=null)	{
		return $this->lastInsertId($k);
	}

	function getLastSql() {
		return $this->sql;
	}

	function getError() {
		$e=$this->errorInfo();
		return ($e?$e[2]:false);
	}

	function esape($v) {
		return $this->quote($v);
	}

//
// Data decode.
//
	static function testTrue($v, $default=null) {
		return $v ? $v : $default;
	}

	static function testNumeric($v, $default=null, $comma=',') {
		if ($comma) $v = str_replace($comma, '', $v);
		return (is_numeric($v) ? $v : ($default !== null ? $default : null));
	}

	static function testTimestamp($v, $default=null) {
		return (strtotime($v)!==false && preg_match('/^[^0][0-9]{0,3}-[0-9]{1,2}-[0-9]{1,2}/', $v) ? $v : $default);
	}

	static function testDate($v, $default=null) {
		return (strtotime($v)!==false && preg_match('/^[^0][0-9]{0,3}-[0-9]{1,2}-[0-9]{1,2}/', $v) ? $v : $default);
	}

	static function encodeArray($a) {
		if (is_array($a)) {
			foreach($a as $k=>$v) {
				$a[$k] = str_replace(array(',','"'),array('&comma;','&quot;'),$v);
				if (!is_numeric($v)) $a[$k] = '"'.$v.'"';
			}
			return '{'.join(',',$a).'}';
		} else
			return false;
	}

	static function decodeArray($s,$split=false) {
		$a=explode(',',preg_replace("/\{(.*)\}/u","\${1}",$s));
		if (is_array($a))
			foreach($a as $k=>$v) { $a[$k] = str_replace(array('&comma;','&quot;'),array(',','"'),trim($v,'"')); }
		if ($split) foreach($a as $k=>$v) $a[$k]=explode($split,$v);
		return $a;
	}

	static function encodeKV($a) {
		if (is_array($a)) foreach ($a as $k=>$v)
			$b[] = str_replace('=','&equal;',$k).'='.str_replace(',','&comma;',$v);
		return is_array($b) ? join(',',$b) : false;
	}

	static function decodeKV($s) {
		if ($a = explode(',', $s))
			foreach ($a as $v)
				if (preg_match('/^(.*?)=(.*)$/', $v, $m)) {
					$b[str_replace('&equal;','=',$m[1])] = str_replace('&comma;',',',$m[2]);
				}
		return is_array($b) ? $b : false;
	}

	static function encodeFlag($a) {
		$r=0; if (is_array($a)) foreach ($a as $k=>$v) if ($v) $r |= pow(2,((int)$k)); return $r;
	}

	static function decodeFlag($v) {
		$i=0; while ($v>1) { $r[++$i] = $v & 2 ? 1 : 0; $v >>= 1; } return $r;
	}

	//
	// Util:
	//

	function push(&$a, $v) {
		$a[] = $v;
		return '?';
	}

	function joinColumn($prefix, $alias, $columns, $r='') {
		foreach($columns as $k)
			$r .= " ,{$alias}.{$k} {$prefix}_{$k}";
		return $r;
	}

	function debug($v)	{
		if ($v!==null) $this->debug=$v; else return $this->debug;
	}

	function setOrder($sortkey, $pager) {
		$order = ['', 'asc','desc'];
		$o = '';
		if ($sort = $sortkey[$pager['sort']]) {
			$o .= " ORDER BY ".$sort.' '
				. ($pager['order'] ? $order[$pager['order']] : '').' '
			;
		}
		if (is_posint($pager['offset']) || $pager['offset'] === '0')
			$o .= " OFFSET ".$pager['offset'].' ';
		if (is_posint($pager['length']))
			$o .= "  LIMIT ".$pager['length'].' ';
		return $o;
	}

	//
	//
	//

	static function getInstance($o=false, $newlink=false) {
		$p = false;
		if ($o === false && self::$DEFAULT_CONF)
			$o = self::$DEFAULT_CONF;
		if ($o instanceof DbConn)
			$r = self::getInstance($o->conf(), $newlink);
		else if (is_array($o) || (is_string($o) && ($p=parse_ini_file($o)))) {
			$a = ($p ? $p : $o);
			$dopt = (isset($a['db.opt']) && is_array($a['db.opt'])) ? $a['db.opt'] : array();
			if ($a['db.type'] == 'pgsql')
				$r = new self("pgsql:host={$a['db.host']};".($a['db.port']?"port={$a['db.port']};":'')."dbname={$a['db.name']}",$a['db.user'],$a['db.password'],$dopt);
			else if ($a['db.type'] == 'mysql')
				$r = new self("mysql:host={$a['db.host']};".($a['db.port']?"port={$a['db.port']};":'')."dbname={$a['db.name']}",$a['db.user'],$a['db.password'],$dopt);
			else if ($a['db.type'] == 'sqlite')
				$r = new self("sqlite:host={$a['db.host']};dbname={$a['db.name']}",null,null,$dopt);
		}
		if ($r) {
			$r->conf($a);
			if ($a['db.debug'])
				$r->debug = true;
			$r->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		}
		return $r ? $r : false;
	}

	function log($msg, $level=LOG_WARNING) {
		syslog($level,  $_SERVER['REMOTE_ADDR'].' - [libdb]'.$msg);
	}
}

// vim: set ts=4:
?>