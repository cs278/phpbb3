<?php
/**
 * Hook to control SSL usage across the board
 *
 * @author Chris Smith <toonarmy@phpbb.com>
 * @copyright (c) 2010 Chris Smith
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
 * Hook to control SSL usage across the board
 */
class phpbb_hook_ssl_control
{
	/**
	 * Force SSL on
	 */
	const ON = 1;

	/**
	 * Force SSL off
	 */
	const OFF = 2;

	/**
	 * @var array Paths matched against $user->page['page'] to control SSL status of using redirects, empty string means any other path
	 */
	protected $_paths = array(
		'adm/'					=> self::ON,
		'ucp.php?mode=register'	=> self::ON,
		'ucp.php?mode=login'	=> self::ON,
		'ucp.php?i=profile&mode=reg_details'	=> self::ON,
		
		''						=> self::OFF,
	);

	/**
	 * @var array Root template variables to rewrite to control SSL status
	 */
	protected $_vars = array(
		'S_LOGIN_ACTION'		=> self::ON,
		'U_LOGIN_LOGOUT'		=> '_var_login_logout',
		'U_REGISTER'			=> self::ON,
		'S_UCP_ACTION'			=> '_var_ucp_action',
		'U_ACP'					=> self::ON,
	);

	/**
	 * @var template Template engine to modify
	 */
	protected $_template;

	/**
	 * @var user phpBB session object
	 */
	protected $_user;

	/**
	 * @var array URL prefixes
	 */
	protected $_prefixes = array();

	/**
	 * Determine if SSL is on or off currently
	 *
	 * @return boolean True if SSL is enabled
	 */
	static protected function _getSslStatus()
	{
		return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
	}

	/**
	 * Construct the hook object
	 *
	 * @param template $template Inject a custom template object
	 * @param user $user Inject a custom user object
	 */
	public function __construct(template $template = null, user $user = null)
	{
		$this->setTemplate($template ? $template : $GLOBALS['template']);
		$this->setUser($user ? $user : $GLOBALS['user']);

		$prefix = generate_board_url(false) . '/';

		$this->_prefixes = array(
			self::OFF	=> str_replace('https://', 'http://', $prefix),
			self::ON	=> str_replace('http://', 'https://', $prefix),
		);
	}

	/**
	 * Sets the template object used for all operations
	 *
	 * @param template $template Instance of phpBB template
	 * @return phpbb_hook_ssl_control
	 */
	public function setTemplate(template $template)
	{
		$this->_template = $template;

		return $this;
	}

	/**
	 * Sets the user object used for all operations
	 *
	 * @param user $user Instance of phpBB user session
	 * @return phpbb_hook_ssl_control
	 */
	public function setUser(user $user)
	{
		$this->_user = $user;

		return $this;
	}

	/**
	 * Gets the current template object
	 *
	 * @return template Instance of phpBB template
	 */
	public function getTemplate()
	{
		return $this->_template;
	}

	/**
	 * Gets the current user object
	 *
	 * @return user Instance of phpBB user session
	 */
	public function getUser()
	{
		return $this->_user;
	}

	/**
	 * Set a path rule
	 *
	 * @param string $path Path to match against
	 * @param integer $value SSL status to force
	 * @return phpbb_hook_ssl_control
	 */
	public function setPath($path, $value)
	{
		$this->_paths[$path] = $value;

		return $this;
	}

	/**
	 * Unset a path rule
	 *
	 * @param string $path Path to match against
	 * @return phpbb_hook_ssl_control
	 */
	public function unsetPath($path)
	{
		unset($this->_paths[$path]);

		return $this;
	}

	/**
	 * Set a template var rule
	 *
	 * @param string $var Template var to rewrite
	 * @param integer $value SSL status to force
	 * @return phpbb_hook_ssl_control
	 */
	public function setVar($var, $value)
	{
		$this->_vars[$var] = $value;

		return $this;
	}

	/**
	 * Unset a template var rule
	 *
	 * @param string $var Template var to ignore
	 * @return phpbb_hook_ssl_control
	 */
	public function unsetVar($var)
	{
		unset($this->_vars[$var]);

		return $this;
	}

	/**
	 * Convenience for quick hooking
	 * 
	 * @param phpbb_hook $hook
	 * @return void
	 */
	static public function hook(&$hook)
	{
		$instance = new self;

		$instance->run($hook);
	}

	/**
	 * Run the hook
	 * 
	 * @param phpbb_hook $hook
	 * @return void
	 */
	public function run(&$hook)
	{
		// Run this one first, it might redirect us
		$this->rewrite_paths();
		$this->rewrite_vars();
	}

	protected function rewrite_vars()
	{
		foreach ($this->_vars as $var => $status)
		{
			if (empty($this->_template->_rootref[$var]))
			{
				continue;
			}

			$value = $this->_template->_rootref[$var];

			if (is_string($status) || is_array($status))
			{
				if (is_callable($status))
				{
					$status = call_user_func($status, $value, $var);
				}
				else if (is_string($status) && is_callable(array($this, $status)))
				{
					$status = call_user_func(array($this, $status), $value, $var);
				}
			}

			if ($status === self::ON || $status === self::OFF)
			{
				if ($url = $this->rewrite_url($value, $status))
				{
					$this->_template->assign_var($var, $url);
				}
			}
		}
	}

	protected function rewrite_paths()
	{
		$value = $this->_user->page['page'];

		foreach ($this->_paths as $path => $status)
		{
			if ($path && strpos($value, $path) !== 0)
			{
				continue;
			}

			if (is_string($status) || is_array($status))
			{
				if (is_callable($status))
				{
					$status = call_user_func($status, $value, $var);
				}
				else if (is_string($status) && is_callable(array($this, $status)))
				{
					$status = call_user_func(array($this, $status), $value, $var);
				}
			}

			if ($status === self::ON || $status === self::OFF)
			{
				if ($url = $this->rewrite_url($value, $status))
				{
					redirect($url);
				}
				// First path to match we break
				break;
			}
		}
	}

	protected function rewrite_url($value, $status)
	{
		if (($status === self::ON && !self::_getSslStatus()) || ($status === self::OFF && self::_getSslStatus()))
		{
			$value = (strpos($value, './') === 0) ? substr($value, 2) : $value;
			$value = ltrim($value, '/');

			// Ensure a SID is present if required
			if (defined('NEED_SID') && strpos($value, 'sid=') === false)
			{
				$value = append_sid($value);
			}

			return $this->_prefixes[$status] . $value;
		}
		return false;
	}

	protected function _var_login_logout($value, $var)
	{
		return strpos($value, 'mode=login') !== false ? self::ON : false;
	}

	protected function _var_ucp_action($value, $var)
	{
		return (strpos($value, 'mode=register') !== false || isset($this->_template->_rootref['CUR_PASSWORD'])) ? self::ON : false;
	}
}

$phpbb_hook->register(array('template', 'display'), array('phpbb_hook_ssl_control', 'hook'));
