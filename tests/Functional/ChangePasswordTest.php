<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ChangePasswordTest extends FunctionalTestCase
{
    public function testAnonymousUserCannotAccessPasswordChangePage(): void
    {
        $this->client->request('GET', '/change-password');

        self::assertResponseRedirects('/login');
    }

    public function testFirstConnectionUserCannotAccessAdminBeforeChangingPassword(): void
    {
        $this->loginFirstConnectionUser();

        $this->client->request('GET', '/admin/media');

        self::assertResponseRedirects('/login');
    }

    public function testPasswordChangePageRequiresMatchingPasswords(): void
    {
        $this->loginFirstConnectionUser();
        $this->client->request('GET', '/change-password');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Mettre à jour', [
            'new_password' => 'NewPassword@123!',
            'confirm_password' => 'DifferentPassword@123!',
        ]);

        self::assertResponseRedirects('/change-password');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form');
    }

    public function testUserCanChangePasswordThenAccessAdmin(): void
    {
        $user = $this->loginFirstConnectionUser();

        $this->client->request('GET', '/change-password');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Mettre à jour', [
            'new_password' => 'NewPassword@123!',
            'confirm_password' => 'NewPassword@123!',
        ]);

        self::assertResponseRedirects('/admin/media');

        $updatedUser = $this->entityManager
            ->getRepository(User::class)
            ->find($user->getId());

        self::assertInstanceOf(User::class, $updatedUser);
        self::assertFalse($updatedUser->isFirstConnection());

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        if (!$passwordHasher instanceof UserPasswordHasherInterface) {
            throw new \LogicException('Password hasher service is unavailable.');
        }
        self::assertTrue(
            $passwordHasher->isPasswordValid($updatedUser, 'NewPassword@123!')
        );

        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    private function loginFirstConnectionUser(): User
    {
        $email = 'jane@example.com';
        $password = 'User@12345!';
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);
        self::assertInstanceOf(User::class, $user);

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Connexion')->form([
            '_username' => $email,
            '_password' => $password,
        ]);

        $this->client->submit($form);
        self::assertResponseRedirects('/change-password');

        $this->client->followRedirect();
        self::assertResponseIsSuccessful();

        return $user;
    }
}