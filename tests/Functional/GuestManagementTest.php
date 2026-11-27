<?php

namespace App\Tests\Functional;

use App\Entity\User;

class GuestManagementTest extends FunctionalTestCase
{
    public function testAdminCanAddGuest(): void
    {
        $admin = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'admin@example.com']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/admin/guest/add');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Ajouter')->form([
            'user[name]' => 'Invité Fonctionnel',
            'user[description]' => 'Compte créé par un test',
            'user[email]' => 'new-functional-guest@example.com',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/guest');

        $guest = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'new-functional-guest@example.com']);

        self::assertNotNull($guest);
        self::assertSame('Invité Fonctionnel', $guest->getName());
        self::assertContains('ROLE_USER', $guest->getRoles());
        self::assertTrue($guest->isFirstConnection());
        self::assertNull($guest->getDeactivationDate());
    }

    public function testAdminCanDeactivateReactivateAndDeleteGuest(): void
    {
        $admin = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'admin@example.com']);
        self::assertInstanceOf(User::class, $admin);

        $guest = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'john@example.com']);
        self::assertInstanceOf(User::class, $guest);
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/admin/guest');
        self::assertResponseIsSuccessful();

        $deactivateForm = $crawler
            ->filter(sprintf('form[action="/admin/guest/deactivate/%d"]', $guest->getId()))
            ->form();
        $this->client->submit($deactivateForm);

        self::assertResponseRedirects('/admin/guest');
        $deactivatedGuest = $this->entityManager
            ->getRepository(User::class)
            ->find($guest->getId());
        self::assertInstanceOf(User::class, $deactivatedGuest);
        $this->entityManager->refresh($deactivatedGuest);
        self::assertNotNull($deactivatedGuest->getDeactivationDate());

        $crawler = $this->client->followRedirect();
        $reactivateForm = $crawler
            ->filter(sprintf('form[action="/admin/guest/reactivate/%d"]', $guest->getId()))
            ->form();
        $this->client->submit($reactivateForm);

        self::assertResponseRedirects('/admin/guest');
        $reactivatedGuest = $this->entityManager
            ->getRepository(User::class)
            ->find($guest->getId());
        self::assertInstanceOf(User::class, $reactivatedGuest);
        $this->entityManager->refresh($reactivatedGuest);
        self::assertNull($reactivatedGuest->getDeactivationDate());

        $crawler = $this->client->followRedirect();
        $deleteForm = $crawler
            ->filter(sprintf('form[action="/admin/guest/delete/%d"]', $guest->getId()))
            ->form();
        $this->client->submit($deleteForm);

        self::assertResponseRedirects('/admin/guest');
        $this->entityManager->clear();
        $deletedGuest = $this->entityManager
            ->getRepository(User::class)
            ->find($guest->getId());
        self::assertNull($deletedGuest);
    }
}
