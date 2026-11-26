<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ChangePasswordTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    /** @var list<string> */
    private array $createdEmails = [];

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->createdEmails as $email) {
            $user = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy(['email' => $email]);

            if ($user !== null) {
                $this->entityManager->remove($user);
            }
        }

        $this->entityManager->flush();
        parent::tearDown();
    }

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
        self::assertTrue(
            $passwordHasher->isPasswordValid($updatedUser, 'NewPassword@123!')
        );

        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    private function loginFirstConnectionUser(): User
    {
        $email = 'change-password-functional@example.com';
        $password = 'OldPassword@123!';
        $user = $this->createUser($email, $password);

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

    private function createUser(string $email, string $plainPassword): User
    {
        $existingUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existingUser !== null) {
            $this->entityManager->remove($existingUser);
            $this->entityManager->flush();
        }

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setName('Change Password Test');
        $user->setEmail($email);
        $user->setDescription('User created for password change tests');
        $user->setRoles(['ROLE_USER']);
        $user->setFirstConnection(true);
        $user->setDeactivationDate(null);
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->createdEmails[] = $email;

        return $user;
    }
}