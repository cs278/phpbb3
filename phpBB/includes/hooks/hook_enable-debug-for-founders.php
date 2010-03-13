<?php
/**
 * Hook to enable DEBUG and DEBUG_EXTRA modes in phpBB3 for founder users.
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
 * This hook enables the debug mode for founders
 *
 * @param phpbb_hook $hook phpBB hook instance
 * @return void
 */
function hook_enable_debug_for_founders(&$hook)
{
	global $user;

	if ($user->data['user_type'] == USER_FOUNDER)
	{
		// Be careful when defining the constants

		if (!defined('DEBUG'))
		{
			define('DEBUG', true);
		}

		if (!defined('DEBUG_EXTRA'))
		{
			define('DEBUG_EXTRA', true);
		}
	}
}

$phpbb_hook->register('phpbb_user_session_handler', 'hook_enable_debug_for_founders');
