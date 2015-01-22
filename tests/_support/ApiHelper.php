<?php
namespace Codeception\Module;

use Laracasts\TestDummy\Factory;
use Auth;
use DB;

class ApiHelper extends \Codeception\Module
{
	public function haveAnAccount($data = [])
	{
		return Factory::create('Flarum\Core\Users\User', $data);
	}

	public function login($identifier, $password)
	{
		$this->getModule('REST')->sendPOST('/api/auth/login', ['identifier' => $identifier, 'password' => $password]);

		$response = json_decode($this->getModule('REST')->grabResponse(), true);
		if ($response && is_array($response) && isset($response['token'])) {
			return $response['token'];
		}

		return false;
	}

	public function amAuthenticated()
	{
		$user = $this->haveAnAccount();
		$user->groups()->attach(3); // Add member group
		Auth::onceUsingId($user->id);

        return $user;
	}
}
