<?php
/**
*
* @package dbal
* @copyright (c) 2005, 2011 phpBB Group
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

include_once($phpbb_root_path . 'includes/db/dbal.' . $phpEx);

/**
* SQLite3 database abstraction layer
*
* This file is largely based upon
* <http://www.phpbb.com/community/viewtopic.php?f=70&t=1059695>
*
* @package dbal
* @author Boris Berdichevski <borisba@borisba.com>
*/
class dbal_sqlite3 extends dbal
{
	var $db;

	/**
	* Connect to server
	*/
	function sql_connect($sqlserver, $sqluser, $sqlpassword, $database, $port = false, $persistency = false, $new_link = false)
	{
		$this->server		= $sqlserver;
		$this->persistency	= $persistency;

		try
		{
			$this->db = new SQLite3($this->server);
			$this->db->exec('PRAGMA short_column_names = 1');
		}
		catch (Exception $error)
		{
			return array(
				'code'		=> $error->getCode(),
				'message' => $error->getMessage(),
			);
		}

		return true;
	}

	/**
	* Version information about used database
	*
	* @param bool $raw if true, only return the fetched sql_server_version
	* @param bool $use_cache if true, it is safe to retrieve the stored value from the cache
	* @return string sql server version
	*/
	function sql_server_info($raw = false, $use_cache = true)
	{
		global $cache;

		if (!$use_cache || empty($cache) || ($this->sql_server_version = $cache->get('sqlite3_version')) === false)
		{
			$vers = SQLite3::version();

			$this->sql_server_version = $vers['versionString'];

			if (!empty($cache) && $use_cache)
			{
				$cache->put('sqlite3_version', $this->sql_server_version);
			}
		}

		return ($raw) ? $this->sql_server_version : 'SQLite ' . $this->sql_server_version;
	}

	/**
	* SQL Transaction
	*/
	function _sql_transaction($status = 'begin')
	{
		switch ($status)
		{
			case 'begin':
				return $this->db->query('BEGIN');
			break;

			case 'commit':
				return $this->db->query('COMMIT');
			break;

			case 'rollback':
				return $this->db->query('ROLLBACK');
			break;
		}

		return true;
	}

	/**
	* Base query method
	*
	* @param	string	$query		Contains the SQL query which shall be executed
	* @param	int		$cache_ttl	Either 0 to avoid caching or the time in seconds which the result shall be kept in cache
	* @return	mixed				When casted to bool the returned value returns true on success and false on failure
	*
	*/
	function sql_query($query = '', $cache_ttl = 0)
	{
		global $cache;

		if ($query != '')
		{
			// EXPLAIN only in extra debug mode
			if (defined('DEBUG_EXTRA'))
			{
				$this->sql_report('start', $query);
			}

			$this->query_result = ($cache_ttl && method_exists($cache, 'sql_load')) ? $cache->sql_load($query) : false;

			$this->sql_add_num_queries($this->query_result);

			if ($this->query_result === false)
			{
				$this->query_result = @$this->db->query($query);

				if ($this->query_result === false)
				{
					$this->sql_error($query);
				}

				if (defined('DEBUG_EXTRA'))
				{
					$this->sql_report('stop', $query);
				}

				if ($cache_ttl && method_exists($cache, 'sql_save'))
				{
					$this->open_queries[(int) $this->query_result] = $this->query_result;

					$cache->sql_save($query, $this->query_result, $cache_ttl);
				}
				else if (is_object($this->query_result))
				{
					$this->open_queries[spl_object_hash($this->query_result)] = $this->query_result;
				}
			}
			else if (defined('DEBUG_EXTRA'))
			{
				$this->sql_report('fromcache', $query);
			}
		}
		else
		{
			return false;
		}

		return ($this->query_result) ? $this->query_result : false;
	}

	/**
	* Build LIMIT query
	*/
	function _sql_query_limit($query, $total, $offset = 0, $cache_ttl = 0)
	{
		$this->query_result = false;

		// if $total is set to 0 we do not want to limit the number of rows
		if ($total == 0)
		{
			$total = -1;
		}

		$query .= "\n LIMIT " . ((!empty($offset)) ? $offset . ', ' . $total : $total);

		return $this->sql_query($query, $cache_ttl);
	}

	/**
	* Return number of affected rows
	*/
	function sql_affectedrows()
	{
		return ($this->db instanceof SQLite3) ? $this->db->changes() : false;
	}

	/**
	* Fetch current row
	*/
	function sql_fetchrow($query_id = false)
	{
		global $cache;

		if ($query_id === false)
		{
			$query_id = $this->query_result;
		}

		if (!is_object($query_id) && isset($cache->sql_rowset[$query_id]))
		{
			return $cache->sql_fetchrow($query_id);
		}

		if (!is_object($query_id))
		{
			return false;
		}

		return $query_id->fetchArray(SQLITE3_ASSOC);
	}

	/**
	* Seek to given row number
	* rownum is zero-based
	*/
	function sql_rowseek($rownum, &$query_id)
	{
		global $cache;

		if ($query_id === false)
		{
			$query_id = $this->query_result;
		}

		if (!is_object($query_id) && isset($cache->sql_rowset[$query_id]))
		{
			return $cache->sql_rowseek($rownum, $query_id);
		}

		// @todo Implement seeking the hard way.
		throw new BadMethodCallException('sqlite3 does not support row seeking.');
	}

	/**
	* Get last inserted id after insert statement
	*/
	function sql_nextid()
	{
		return $this->db->lastInsertRowID();
	}

	/**
	* Free sql result
	*/
	function sql_freeresult($query_id = false)
	{
		global $cache;

		if ($query_id === false)
		{
			$query_id = $this->query_result;
		 }

		if (isset($cache->sql_rowset[$query_id]))
		{
			return $cache->sql_freeresult($query_id);
		}

		if (is_object($query_id) && isset($this->open_queries[spl_object_hash($query_id)]))
		{
			unset($this->open_queries[spl_object_hash($query_id)]);
			$query_id->finalize();
		}

		return true;
	}

	/**
	* Escape string used in sql query
	*/
	function sql_escape($msg)
	{
		return SQLite3::escapeString($msg);
	}

	/**
	* Correctly adjust LIKE expression for special characters
	* For SQLite an underscore is a not-known character... this may change with SQLite3
	*
	* @todo Does this still stand for SQLite3?
	*/
	function sql_like_expression($expression)
	{
		// Unlike LIKE, GLOB is case sensitive (unfortunatly). SQLite users need to live with it!
		// We only catch * and ? here, not the character map possible on file globbing.
		$expression = str_replace(array(chr(0) . '_', chr(0) . '%'), array(chr(0) . '?', chr(0) . '*'), $expression);

		$expression = str_replace(array('?', '*'), array("\?", "\*"), $expression);
		$expression = str_replace(array(chr(0) . "\?", chr(0) . "\*"), array('?', '*'), $expression);

		return 'GLOB \'' . $this->sql_escape($expression) . '\'';
	}

	/**
	* return sql error array
	*/
	function _sql_error()
	{
		return array(
			'message'	=> $this->db->lastErrorMsg(),
			'code'		=> $this->db->lastErrorCode()
		);
	}

	/**
	* Build db-specific query data
	*/
	function _sql_custom_build($stage, $data)
	{
		return $data;
	}

	/**
	* Close sql connection
	*/
	function _sql_close()
	{
		return $this->db->close();
	}

	/**
	* Build db-specific report
	*/
	function _sql_report($mode, $query = '')
	{
		switch ($mode)
		{
			case 'start':
			break;

			case 'fromcache':
				$endtime = microtime(true);

				$results = $this->db->query( $query);

				while ($result= $results->fetchArray(SQLITE3_ASSOC))
				{
					// Take the time spent on parsing rows into account
				}

				$splittime = explode(' ', microtime());
				$splittime = $splittime[0] + $splittime[1];

				$this->sql_report('record_fromcache', $query, $endtime, $splittime);

				$results->finalize();
			break;
		}
	}
}
