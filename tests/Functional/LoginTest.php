<?php

namespace App\Tests\Functional;

use App\Entity\User;

class LoginTest extends FunctionalTestCase
{
    public function testAdminCanLogIn(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Connexion')->form([
            '_username' => 'admin@example.com',
            '_password' => 'Admin@12345!',
        ]);

        $this->client->submit($form);

        self::assertResponseRedirects('/admin/media');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    public function testGuestUsingFirstConnectionIsRedirectedToPasswordChange(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Connexion')->form([
            '_username' => 'jane@example.com',
            '_password' => 'User@12345!',
        ]);

        $this->client->submit($form);

        self::assertResponseRedirects('/change-password');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form');
    }

    public function testInvalidCredentialsDisplayAnError(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Connexion')->form([
            '_username' => 'unknown@example.com',
            '_password' => 'WrongPassword!',
        ]);

        $this->client->submit($form);

        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.alert-danger');
    }
}
