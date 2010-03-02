<?php
/**
*
* @package phpBB3
* @copyright (c) 2010 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* Interface for a messenger message
*
* A message is a single instance of communication
*/
interface phpbb_messenger_message_interface
{
	/**
	* Construct a message
	*/
	public function __construct();
}
