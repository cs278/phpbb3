<?php
/**
*
* @package core
* @version $Id$
* @copyright (c) 2008 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* Replacement for a superglobal (like $_GET or $_POST) which calls
* trigger_error on any operation, overloads the [] operator using SPL.
* @package core
* @author naderman
*/
class deactivated_super_global implements ArrayAccess, Countable, IteratorAggregate
{
	/**
	* Holds the error message
	*/
	private $message;

	/**
	* Constructor generates an error message fitting the super global to be
	* used within the other functions.
	*
	* @param	string	$name	Name of the super global this is a replacement for - e.g. '_GET'
	*/
	public function __construct($name)
	{
		$this->message = 'Illegal use of $' . $name . '. You must use the request class or request_var() to access input data. Found in %s on line %d. This error message was generated';
	}

	/**
	* Calls trigger_error with the file and line number the super global was used in
	*/
	private function error()
	{
		$file = '';
		$line = 0;

		$backtrace = debug_backtrace();
		if (isset($backtrace[1]))
		{
			$file = $backtrace[1]['file'];
			$line = $backtrace[1]['line'];
		}
		trigger_error(sprintf($this->message, $file, $line), E_USER_ERROR);
	}

	/**
	* Part of the ArrayAccess implementation, will always result in a FATAL error
	*/
	public function offsetExists($offset)
	{
		$this->error();
	}

	/**
	* Part of the ArrayAccess implementation, will always result in a FATAL error
	*/
	public function offsetGet($offset)
	{
		$this->error();
	}

	/**
	* Part of the ArrayAccess implementation, will always result in a FATAL error
	*/
	public function offsetSet($offset, $value)
	{
		$this->error();
	}

	/**
	* Part of the ArrayAccess implementation, will always result in a FATAL error
	*/
	public function offsetUnset($offset)
	{
		$this->error();
	}

	/**
	* Part of the Countable implementation, will always result in a FATAL error
	*/
	public function count()
	{
		$this->error();
	}

	/**
	* Part of the Traversable/IteratorAggregate implementation, will always result in a FATAL error
	*/
	public function getIterator()
	{
		$this->error();
	}
}

/**
* All application input is accessed through this class. It provides a method
* to disable access to input data through super globals. This should force MOD
* authors to read about data validation.
* @package core
* @author naderman
*/
class request
{
	const POST = 0;
	const GET = 1;
	const REQUEST = 2;
	const COOKIE = 3;

	protected static $initialised = false;
	protected static $super_globals_disabled = false;

	/**
	* The names of super global variables that this class should protect
	* if super globals are disabled
	*/
	protected static $super_globals = array(request::POST => '_POST', request::GET => '_GET', request::REQUEST => '_REQUEST', request::COOKIE => '_COOKIE');

	/**
	* An associative array that has the value of super global constants as
	* keys and holds their data as values.
	*/
	protected static $input;

	/**
	* Initialises the request class, that means it stores all input data in
	* self::$input
	*/
	public static function init()
	{
		if (!self::$initialised)
		{
			foreach (self::$super_globals as $const => $super_global)
			{
				self::$input[$const] = isset($GLOBALS[$super_global]) ? $GLOBALS[$super_global] : array();
			}

			self::$initialised = true;
		}
	}

	/**
	* Resets the request class.
	* This will simply forget about all input data and read it again from the
	* super globals, if super globals were disabled, all data will be gone.
	*/
	public static function reset()
	{
		self::$input = array();
		self::$initialised = false;
		self::$super_globals_disabled = false;
	}

	/**
	* Getter for $super_globals_disabled
	* @return	bool	Whether super globals are disabled or not.
	*/
	public static function super_globals_disabled()
	{
		return self::$super_globals_disabled;
	}

	/**
	* Disables access of super globals specified in $super_globals.
	* This is achieved by overwriting the super globals with instances of
	* {@link deactivated_super_global deactivated_super_global}
	*/
	public static function disable_super_globals()
	{
		if (!self::$initialised)
		{
			self::init();
		}

		foreach (self::$super_globals as $const => $super_global)
		{
			unset($GLOBALS[$super_global]);
			$GLOBALS[$super_global] = new deactivated_super_global($super_global);
		}

		self::$super_globals_disabled = true;
	}

	/**
	* Enables access of super globals specified in $super_globals if they were
	* disabled by {@link disable_super_globals disable_super_globals}.
	* This is achieved by making the super globals point to the data stored
	* within this class in {@link input input}.
	*/
	public static function enable_super_globals()
	{
		if (!self::$initialised)
		{
			self::init();
		}

		if (self::$super_globals_disabled)
		{
			foreach (self::$super_globals as $const => $super_global)
			{
				$GLOBALS[$super_global] = self::$input[$const];
			}

			self::$super_globals_disabled = false;
		}
	}

	/**
	* Recursively applies addslashes to a variable.
	*
	* @param	mixed	$var	Variable passed by reference to which slashes
	*							will be added.
	*/
	protected static function addslashes_recursively(&$var)
	{
		if (is_string($var))
		{
			$var = addslashes($var);
		}
		else if (is_array($var))
		{
			$var_copy = $var;
			foreach ($var_copy as $key => $value)
			{
				if (is_string($key))
				{
					$key = addslashes($key);
				}
				self::addslashes_recursively($var[$key]);
			}
		}
	}

	/**
	* This function allows overwriting or setting a value in one of the super
	* global arrays.
	* Changes which are performed on the super globals directly will not have
	* any effect on the results of other methods this class provides. Using
	* this function should be avoided if possible! It will consume twice the
	* the amount of memory of the value
	*
	* @param	string	$var_name	The name of the variable that shall be
	*								overwritten
	* @param	mixed	$value		The value which the variable shall contain.
	*								If this is null the variable will be unset.
	* @param	request::POST|request::GET|request::REQUEST|request::COOKIE	$super_global
	*								Specifies which super global shall be changed
	*/
	public static function overwrite($var_name, $value, $super_global = request::REQUEST)
	{
		if (!self::$initialised)
		{
			self::init();
		}

		if (!isset(self::$super_globals[$super_global]))
		{
			return;
		}

		if (STRIP)
		{
			self::addslashes_recursively($value);
		}

		// setting to null means unsetting
		if ($value === null)
		{
			unset(self::$input[$super_global][$var_name]);
			if (!self::super_globals_disabled())
			{
				unset($GLOBALS[self::$super_globals[$super_global]][$var_name]);
			}
		}
		else
		{
			self::$input[$super_global][$var_name] = $value;
			if (!self::super_globals_disabled())
			{
				$GLOBALS[self::$super_globals[$super_global]][$var_name] = $value;
			}
		}

		if (!self::super_globals_disabled())
		{
			unset($GLOBALS[self::$super_globals[$super_global]][$var_name]);
			$GLOBALS[self::$super_globals[$super_global]][$var_name] = $value;
		}
	}

	/**
	* Recursively sets a variable to a given type using {@link set_var set_var}
	* This function is only used from within {@link request::variable request::variable}.
	*
	* @param	string	$var		The value which shall be sanitised (passed
									by reference).
	* @param	mixed	$default	Specifies the type $var shall have. If it
	*								is an array and $var is not one, then an
	*								empty array is returned. Otherwise var
	*								is cast to the same type, and if $default
	*								is an array all keys and values are cast
	*								recursively using this function too.
	* @param	bool	$multibyte	Indicates whether string values may contain
	*								UTF-8 characters. Default is false, causing
	*								all bytes outside the ASCII range (0-127)
	*								to be replaced with question marks.
	*/
	protected static function recursive_set_var(&$var, $default, $multibyte)
	{
		if (is_array($var) !== is_array($default))
		{
			$var = (is_array($default)) ? array() : $default;
			return;
		}

		if (!is_array($default))
		{
			$type = gettype($default);
			set_var($var, $var, $type, $multibyte);
		}
		else
		{
			// make sure there is at least one key/value pair to use get the
			// types from
			if (!sizeof($default))
			{
				$var = array();
				return;
			}

			list($default_key, $default_value) = each($default);
			$value_type = gettype($default_value);
			$key_type = gettype($default_key);

			$_var = $var;
			$var = array();

			foreach ($_var as $k => $v)
			{
				set_var($k, $k, $key_type, $multibyte);

				self::recursive_set_var($v, $default_value, $multibyte);
				set_var($var[$k], $v, $value_type, $multibyte);
			}
		}
	}

	/**
	* Central type safe input handling function.
	* All variables in GET or POST requests should be retrieved through this
	* function to maximise security.
	*
	* @param	string|array	$var_name	The form variable's name from which data
	*								shall be retrieved. If the value is an array this
	*								may be an array of indizes which will give direct
	*								access to a value at any depth. E.g. if the value
	*								of "var" is array(1 => "a") then specifying
	*								array("var", 1) as the name will return "a".
	* @param	mixed	$default	A default value that is returned if the variable
	*								was not set. This function will always return a
	*								a value of the same type as the default.
	* @param	bool	$multibyte	If $default is a string this paramater has to be
	*								true if the variable may contain any UTF-8 characters
	*								Default is false, causing all bytes outside the ASCII
	*								range (0-127) to be replaced with question marks
	* @param	request::POST|request::GET|request::REQUEST|request::COOKIE	$super_global
	*								Specifies which super global should be used
	* @return	mixed				The value of $_REQUEST[$var_name] run through
	*								{@link set_var set_var} to ensure that the type is the
	*								the same as that of $default. If the variable is not set
	*								$default is returned.
	*/
	public static function variable($var_name, $default, $multibyte = false, $super_global = request::REQUEST)
	{
		$path = false;

		if (!self::$initialised)
		{
			self::init();
		}

		// deep direct access to multi dimensional arrays
		if (is_array($var_name))
		{
			$path = $var_name;
			// make sure at least the variable name is specified
			if (!sizeof($path))
			{
				return (is_array($default)) ? array() : $default;
			}
			// the variable name is the first element on the path
			$var_name = array_shift($path);
		}

		if (!isset(self::$input[$super_global][$var_name]))
		{
			return (is_array($default)) ? array() : $default;
		}
		$var = self::$input[$super_global][$var_name];

		// make sure cookie does not overwrite get/post
		if ($super_global != request::COOKIE && isset(self::$input[request::COOKIE][$var_name]))
		{
			if (!isset(self::$input[request::GET][$var_name]) && !isset(self::$input[request::POST][$var_name]))
			{
				return (is_array($default)) ? array() : $default;
			}
			$var = isset(self::$input[request::POST][$var_name]) ? self::$input[request::POST][$var_name] : self::$input[request::GET][$var_name];
		}

		if ($path)
		{
			// walk through the array structure and find the element we are looking for
			foreach ($path as $key)
			{
				if (is_array($var) && isset($var[$key]))
				{
					$var = $var[$key];
				}
				else
				{
					return (is_array($default)) ? array() : $default;
				}
			}
		}

		self::recursive_set_var($var, $default, $multibyte);

		return $var;
	}

	/**
	* Checks whether a certain variable was sent via POST.
	* To make sure that a request was sent using POST you should call this function
	* on at least one variable.
	*
	* @param	string	$name	The name of the form variable which should have a
	*							_p suffix to indicate the check in the code that
	*							creates the form too.
	* @return	bool			True if the variable was set in a POST request,
	*							false otherwise.
	*/
	public static function is_set_post($name)
	{
		return self::is_set($name, request::POST);
	}

	/**
	* Checks whether a certain variable is set in one of the super global
	* arrays.
	*
	* @param	string	$var			Name of the variable
	* @param	request::POST|request::GET|request::REQUEST|request::COOKIE	$super_global
	*									Specifies the super global which shall be checked
	* @return	bool					True if the variable was sent as input
	*/
	public static function is_set($var, $super_global = request::REQUEST)
	{
		if (!self::$initialised)
		{
			self::init();
		}

		return isset(self::$input[$super_global][$var]);
	}

	/**
	* Returns all variable names for a given super global
	*
	* @param	request::POST|request::GET|request::REQUEST|request::COOKIE	$super_global
	*					The super global from which names shall be taken
	* @return	array	All variable names that are set for the super global.
	*					Pay attention when using these, they are unsanitised!
	*/
	public static function variable_names($super_global = request::REQUEST)
	{
		if (!self::$initialised)
		{
			self::init();
		}

		if (!isset(self::$input[$super_global]))
		{
			return array();
		}

		return array_keys(self::$input[$super_global]);
	}
}

?>