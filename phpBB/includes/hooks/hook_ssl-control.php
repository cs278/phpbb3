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
 * Hook to various password authentications to go over a secure connection,
 * modified vars include:
 * <ul>
 * <li>Login link</li>
 * <li>Login form action</li>
 * <li>Registration link</li>
 * <li>Registration form action</li>
 * <li>UCP account settings form action</li>
 * </ul>
 * 
 * @param phpbb_hook $hook phpBB hook instance
 * @return void
 */
function hook_ssl_control(&$hook)
{
	global $template, $user;

	$url = str_replace('http://', 'https://', generate_board_url(true)) . $user->page['script_path'];

	// Login action will always be force to SSL just in case
	if (isset($template->_rootref['S_LOGIN_ACTION']))
	{
		hook_ssl_control_rewirte_var('S_LOGIN_ACTION', $url);
	}

	// The login/logout link will be forced to SSL when it's a login link
	if (isset($template->_rootref['U_LOGIN_LOGOUT']) && strpos($template->_rootref['U_LOGIN_LOGOUT'], 'mode=login') !== false)
	{
		hook_ssl_control_rewirte_var('U_LOGIN_LOGOUT', $url);
	}

	// Registrations involve passwords, we should force SSL here too.
	if (isset($template->_rootref['U_REGISTER']))
	{
		hook_ssl_control_rewirte_var('U_REGISTER', $url);
	}

	// Rewrite the UCP form action on registration or account settings
	if (isset($template->_rootref['S_UCP_ACTION']) && (
		strpos($template->_rootref['S_UCP_ACTION'], 'mode=register') !== false ||
		isset($template->_rootref['CUR_PASSWORD']))
	)
	{
		hook_ssl_control_rewirte_var('S_UCP_ACTION', $url);
	}
}

function hook_ssl_control_rewirte_var($var, $url)
{
	global $template;

	$value = $template->_rootref[$var];
	$value = (strpos($value, './') === 0) ? substr($value, 2) : $value;
	$value = ltrim($value, '/');

	$template->assign_var($var, $url . $value);
}

$phpbb_hook->register(array('template', 'display'), 'hook_ssl_control');
