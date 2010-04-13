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

class phpbb_filesystem_phpbb_own_realpath_test extends phpbb_test_case
{
	private $cwd;
	private $old_cwd;

	public function setUp()
	{
		$this->old_cwd = getcwd();
		$this->cwd = $this->getTempDir();
		$this->dir = basename($this->cwd);

		chdir($this->cwd);
	}

	public function tearDown()
	{
		chdir($this->old_cwd);
		shell_exec('rm -r ' . escapeshellarg($this->cwd));
	}

	protected function getTempDir()
	{
		$path =  trim(shell_exec('mktemp -d'));

		return $path;
	}

	protected function requires($ability)
	{
		if (func_num_args() > 1)
		{
			$ability = func_get_args();
		}

		if (is_array($ability))
		{
			array_map(array($this, 'requires'), $ability);
			return;
		}

		if (!$this->hasAbility($ability))
		{
			$this->markTestSkipped('Requires: ' . $ability);
		}
	}

	protected function hasAbility($ability)
	{
		switch ($ability)
		{
			case 'symlink':
				return function_exists('symlink');
			break;

			case 'link':
			case 'hardlink':
				return function_exists('link');
			break;

			case 'posix':
				return DIRECTORY_SEPARATOR === '/';
			break;

			case 'windows':
				return stripos(PHP_OS, 'WIN') === 0;
			break;

			case 'realpath':
				return function_exists('realpath');
			break;

			default:
				throw new InvalidArgumentException((string) $ability);
		}
	}

	static public function getPaths()
	{
		return array(
			array('/'),
			array('/.'),
			array('/..'),
			array('////../'),
			array('/etc'),
			array('C:/'),
			array('/bin/sh'),

			array('/usr/./local/bin/../sbin/../..///././//./bin/../sbin/'),
			array('/boot/../usr/../root/../home/../tmp/..'),
			array('/usr/../root/././home/..'),
			array('/usr/../../../bin/'),

			array('.'),
			array('../.'),
			array('./././././'),
		);
	}

	/**
	 * @dataProvider getPaths
	 */
	public function test_compare($path)
	{
		$this->requires('realpath');

		$this->assertEquals(realpath($path), phpbb_own_realpath($path));
	}

	public function test_bug_9533()
	{
		$this->requires('symlink', 'posix', 'realpath');

		symlink('/', 'link');
		$this->assertEquals('/', phpbb_own_realpath('link'), 'Correctly resolve symlinks pointing to /');
		unlink('link');

		symlink('.', 'link');
		$this->assertEquals(getcwd(), phpbb_own_realpath('link'), 'Correctly resolve symlinks pointing to .');
		unlink('link');

		symlink('..', 'link');
		$this->assertEquals(realpath(getcwd() . '/..'), phpbb_own_realpath('link'), 'Correctly resolve symlinks pointing to ..');
		unlink('link');

		touch('file');

		symlink('file', 'link');
		$this->assertEquals(getcwd() . '/file', phpbb_own_realpath('file'), 'Correctly resolve symlinks pointing to a relative file');
		unlink('link');

		symlink('./file', 'link');
		$this->assertEquals(getcwd() . '/file', phpbb_own_realpath('file'), 'Correctly resolve symlinks pointing to a relative file');
		unlink('link');

		symlink('../' . $this->dir . '/file', 'link');
		$this->assertEquals(getcwd() . '/file', phpbb_own_realpath('file'), 'Correctly resolve symlinks pointing to a relative file');
		unlink('link');

		unlink('file');
	}
}
