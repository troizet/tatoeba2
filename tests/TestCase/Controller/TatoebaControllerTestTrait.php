<?php
namespace App\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;

trait TatoebaControllerTestTrait {
    private function logInAs($username) {
        $users = TableRegistry::get('Users');
        $user = $users->findByUsername($username)->first();
        $this->session(['Auth' => ['User' => $user->toArray()]]);
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    public function assertAccessUrlAs($url, $user, $response) {
        if ($user) {
            $who = "user '$user'";
            $this->logInAs($user);
        } else {
            $who = "guest";
        }

        $this->get($url);

        if (is_string($response)) {
            $this->assertRedirect($response, "Failed asserting that $who is being redirected "
                                            ."to '$response' when trying to access '$url'.");
        } elseif ($response) {
            $this->assertResponseOk("Failed asserting that $who can access '$url'.");
        } else {
            $this->assertResponseError("Failed asserting that $who cannot access '$url'.");
        }
    }
}
