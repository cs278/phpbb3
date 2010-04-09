<?php
/**
*
* @package testing
* @copyright (c) 2008 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

require_once 'test_framework/framework.php';
require_once '../phpBB/includes/functions.php';

class phpbb_cache_test extends phpbb_test_case
{
	public function new_acm($type)
	{
		$this->init_test_case_helpers();
		return $this->test_case_helpers->new_acm($type);
	}

	public function getDataSet()
	{

	}

	public function test_get()
	{
		$cache = $this->new_acm('null');
	
		$this->assertFalse($this->get(md5('doNotExist:' . rand(0, PHP_INT_MAX))));
	}
}