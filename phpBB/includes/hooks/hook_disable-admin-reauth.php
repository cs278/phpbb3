<?php
/**
 * Hook to disable administrator reauthentication in phpBB3
 *
 * @author Chris Smith <toonarmy@phpbb.com>
 * @copyright (c) 2009 Chris Smith
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
 * This hook disables the ACPs re-authentication
 * The intended use for this hook is development environments,
 * use in a live scenario is reckless.
 *
 * @param phpbb_hook $hook phpBB hook instance
 * @return void
 */
function hook_disable_admin_reauth(&$hook)
{
	global $user, $auth;

	if (empty($user->data['session_admin']) && $auth->acl_get('a_'))
	{
		$user->data['session_admin'] = 1;
	}
}

$phpbb_hook->register('phpbb_user_session_handler', 'hook_disable_admin_reauth');

