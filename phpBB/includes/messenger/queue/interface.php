<?php
/**
*
* @package phpBB3
* @copyright (c) 2010 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* Interface for a messenger queue
*
* The queue operates under a FIFO regime, where by new messages are pushed
* to the end of the queue and messages being sent are read from the top of
* the queue.
*/
interface phpbb_messenger_queue_interface
{
	/**
	* Construct a queue object
	*/
	public function __construct(array $config);

	/**
	* Push a message on to the end of the queue
	*/
	public function push(phpbb_messenger_message_interface $message);

	/**
	* Read a message from the head of the queue
	*/
	public function shift();

	/**
	* Empty the queue
	*/
	public function reset();

	/**
	* Commit the queue to storage
	*/
	public function save();
}
