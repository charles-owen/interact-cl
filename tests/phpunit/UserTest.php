<?php
/** @file
 * Unit tests for the class User
 * @cond 
 */

require_once __DIR__ . '/init.php';

use CL\Users\User;
use CL\Site\MetaData;


class UserTest extends \PHPUnit\Framework\TestCase
{
	public function test() {
		$user = new User();
		$this->assertEquals(0, $user->id);

		$meta = $user->metaData;
		$this->assertInstanceOf(MetaData::class, $meta);
	}

	public function test_export() {
		$user = new User();
		$user->name = 'Minion, Bob';
		$user->role = User::USER;
		$export = $user->export();

		$this->assertEquals(0, $export['id']);
		$this->assertEquals('Minion, Bob', $export['name']);
		$this->assertEquals(User::USER, $export['role']);
	}
}

/// @endcond
