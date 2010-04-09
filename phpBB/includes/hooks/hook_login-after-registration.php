<?php
/**
 * Hook to login users who successfully complete the registration process
 * and don't require any form of activation.
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
 * This hook logs in a successfully registered user 
 *
 * @param phpbb_hook $hook phpBB hook instance
 * @return void
 */
function hook_login_after_registration(&$hook)
{
	global $template, $user, $config, $auth, $db;

	if ($config['require_activation'] == USER_ACTIVATION_NONE)
	{
		$username = utf8_normalize_nfc(request_var('username', '', true));

		if ($username)
		{
			$sql = 'SELECT *
				FROM ' . USERS_TABLE . "
				WHERE username = '" . $db->sql_escape($username) . "'
					AND user_active <> " . USER_INACTIVE;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			
			if ($row)
			{
				$password = request_var('new_password', '', true);
				
				$result = $auth->login($username, $password);
				
				if ($result['status'] == LOGIN_SUCCESS)
				{
					redirect(append_sid($result));
				}
			}
		}
	}
}

/**
 * Only register the hook for registration.
 * @todo A better check
 */
if ($config['require_activation'] == USER_ACTIVATION_NONE && isset($_POST['submit']) && isset($_POST['agreed']) && isset($_POST['username']))
{
	$phpbb_hook->register(array('template', 'display'), 'hook_login_after_registration');
}