<?php
namespace Codeception\Module;

use Laracasts\TestDummy\Factory;

class ApiHelper extends \Codeception\Module
{
	public function haveAnAccount($data = [])
	{
		$user = Factory::create('Flarum\Core\Models\User', $data);
        $user->activate();

        return $user;
	}

	public function login($identification, $password)
	{
		$this->getModule('REST')->sendPOST('/api/token', [
            'identification' => $identification,
            'password' => $password
        ]);

		$response = json_decode($this->getModule('REST')->grabResponse(), true);
		if ($response && is_array($response) && isset($response['token'])) {
			return $response['token'];
		}

		return false;
	}

	public function amAuthenticated()
	{
		$user = $this->haveAnAccount();
        $token = $this->login($user->email, 'password');
        $this->getModule('REST')->haveHttpHeader('Authorization', 'Token '.$token);

        return $user;
	}
}
