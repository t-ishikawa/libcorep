<?php
/**
 * Authlib.
 * Copyright (C)2009-2018 ISHIKAWA Takahiro.
 *
 * @author   ISHIKAWA Takahiro <t.ishikawa@itlabj.com>
 * @see      README.txt, LICENCE.txt (LGPL 2.1)
 * @version  9 (PHP 5.4+)
 * @since    2018-08-01
 */

/**
 * Auth Exception.
 *
 * @author (C)2009 ISHIKAWA Takahiro <ishikawa@itlabj.com>
 */
class AuthException extends Exception {

}

/**
 * Auth core class (abstract).
 *
 * @author (C)2009 ISHIKAWA Takahiro <ishikawa@itlabj.com>
 */
abstract class Auth {
	protected $skey = false;

	function __construct($skey='app key') {
		$this->skey = $skey;
	}

	function getPasswordHash($id, $pass) { 
		return false;
	}

	function log($msg, $level=LOG_WARNING) {
		syslog($level,  $_SERVER['REMOTE_ADDR'].' - [libauth]'.$msg);
	}
}

// vim: set ts=4:
?>