<?php
/**
 * Hook to force logins to be over SSL, this will work for normal user
 * authentication and passworded forum authentication.
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
 * Hook to force the login over SSL
 *
 * @param phpbb_hook $hook phpBB hook instance
 * @return void
 */
function hook_ssl_login(&$hook)
{
	global $template;

	if (!isset($template->_rootref['S_LOGIN_ACTION']))
	{
		return;
	}

	$s_action = str_replace('http://', 'https://', generate_board_url()) . $template->_rootref['S_LOGIN_ACTION'];

	// Replace S_LOGIN_ACTION with our modified version
	$template->assign_var('S_LOGIN_ACTION', $s_action);
}

$phpbb_hook->register(array('template', 'display'), 'hook_ssl_login');
