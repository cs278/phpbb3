<?php
/**
 * Hook to disable the delayed redirects phpBB produces after various operations
 * these are usually success messages and as such many users prefer not to see
 * them.
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
 * This hook disables the delayed redirects used by phpBB.
 *
 * @param phpbb_hook $hook phpBB hook instance
 * @return void
 */
function hook_disable_delayed_redirects(&$hook)
{
	global $template, $user;

	if (!isset($template->_rootref['MESSAGE_TEXT']) || !isset($template->_rootref['META']))
	{
		return;
	}

	//'<meta http-equiv="refresh" content="' . $time . ';url=' . $url . '" />')
	if (preg_match('#<meta http-equiv="refresh" content="[0-9]+;url=(.+?)" />#', $template->_rootref['META'], $match))
	{
		// HTML entitied
		$url = str_replace('&amp;', '&', $match[1]);

		// Show messages from pages that return to the same page,
		// otherwise there is no feedback that anything changed
		// which makes the UCP preferences and other places seem
		// to be broken.
		if (generate_board_url() . '/' . $user->page['page'] !== $url)
		{
			redirect($url);
			exit; // Implicit
		}
	}
}

/**
 * Only register the hook for normal pages, not administration pages.
 */
if (!defined('ADMIN_START'))
{
	$phpbb_hook->register(array('template', 'display'), 'hook_disable_delayed_redirects');
}
