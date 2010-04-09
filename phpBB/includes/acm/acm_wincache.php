<?php
/**
*
* @package acm
* @version $Id$
* @copyright (c) 2005, 2009, 2010 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

// Include the abstract base
if (!class_exists('acm_memory'))
{
	require("{$phpbb_root_path}includes/acm/acm_memory.$phpEx");
}

/**
* ACM for XCache
* @package acm
*
* To use this module you need ini_get() enabled and the following INI settings configured as follows:
* - xcache.var_size > 0
* - xcache.admin.enable_auth = off (or xcache.admin.user and xcache.admin.password set)
*
*/
class acm extends acm_memory
{
	var $extension = 'wincache';

	function acm()
	{
		parent::acm_memory();
	}

	/**
	* Purge cache data
	*
	* @return void
	*/
	function purge()
	{
		// Run before for XCache, if admin functions are disabled it will terminate execution
		parent::purge();

		wincache_ucache_clear();
	}

	/**
	* Fetch an item from the cache
	*
	* @access protected
	* @param string $var Cache key
	* @return mixed Cached data
	*/
	function _read($var)
	{
		$success = false;
	
		$result = wincache_ucache_get($this->key_prefix . $var, $success);

		return ($success) ? $result : false;
	}

	/**
	* Store data in the cache
	*
	* @access protected
	* @param string $var Cache key
	* @param mixed $data Data to store
	* @param int $ttl Time-to-live of cached data
	* @return bool True if the operation succeeded
	*/
	function _write($var, $data, $ttl = 2592000)
	{
		return wincache_ucache_set($this->key_prefix . $var, $data, $ttl);
	}

	/**
	* Remove an item from the cache
	*
	* @access protected
	* @param string $var Cache key
	* @return bool True if the operation succeeded
	*/
	function _delete($var)
	{
		return wincache_ucache_delete($this->key_prefix . $var);
	}

	/**
	* Check if a cache var exists
	*
	* @access protected
	* @param string $var Cache key
	* @return bool True if it exists, otherwise false
	*/	
	function _isset($var)
	{
		return wincache_ucache_exists($this->key_prefix . $var);
	}
}

?>