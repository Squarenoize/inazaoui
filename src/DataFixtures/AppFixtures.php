<?php

namespace App\DataFixtures;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create Users
        $admin = new User();
        $admin->setName('Admin User');
        $admin->setEmail('admin@example.com');
        $admin->setDescription('Administrator account');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin@12345!'));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setFirstConnection(false);
        $manager->persist($admin);

        $user1 = new User();
        $user1->setName('John Doe');
        $user1->setEmail('john@example.com');
        $user1->setDescription('A regular user');
        $user1->setPassword($this->passwordHasher->hashPassword($user1, 'User@12345!'));
        $user1->setRoles(['ROLE_USER']);
        $user1->setFirstConnection(false);
        $manager->persist($user1);

        $user2 = new User();
        $user2->setName('Jane Smith');
        $user2->setEmail('jane@example.com');
        $user2->setDescription('Another user');
        $user2->setPassword($this->passwordHasher->hashPassword($user2, 'User@12345!'));
        $user2->setRoles(['ROLE_USER']);
        $user2->setFirstConnection(true);
        $manager->persist($user2);

        $inactiveUser = new User();
        $inactiveUser->setName('Inactive User');
        $inactiveUser->setEmail('inactive@example.com');
        $inactiveUser->setDescription('Deactivated account');
        $inactiveUser->setPassword($this->passwordHasher->hashPassword($inactiveUser, 'User@12345!'));
        $inactiveUser->setRoles(['ROLE_USER']);
        $inactiveUser->setFirstConnection(false);
        $inactiveUser->setDeactivationDate(new \DateTimeImmutable('2024-01-01'));
        $manager->persist($inactiveUser);

        // Create Albums
        $albumVacation = new Album();
        $albumVacation->setName('Vacances 2024');
        $manager->persist($albumVacation);

        $albumPortrait = new Album();
        $albumPortrait->setName('Portraits');
        $manager->persist($albumPortrait);

        $albumNature = new Album();
        $albumNature->setName('Nature');
        $manager->persist($albumNature);

        // Create Medias
        $media1 = new Media();
        $media1->setTitle('Beach Sunset');
        $media1->setPath('uploads/media/beach-sunset.jpg');
        $media1->setUser($user1);
        $media1->setAlbum(null);  // Guest without album
        $manager->persist($media1);

        $media2 = new Media();
        $media2->setTitle('Mountain Peak');
        $media2->setPath('uploads/media/mountain-peak.jpg');
        $media2->setUser($user1);
        $media2->setAlbum(null);  // Guest without album
        $manager->persist($media2);

        $media3 = new Media();
        $media3->setTitle('Portrait - Jane');
        $media3->setPath('uploads/media/portrait-jane.jpg');
        $media3->setUser($user2);
        $media3->setAlbum(null);  // Guest without album
        $manager->persist($media3);

        $media4 = new Media();
        $media4->setTitle('Sunset Over Lake');
        $media4->setPath('uploads/media/sunset-lake.jpg');
        $media4->setUser($user2);
        $media4->setAlbum(null);  // Guest without album
        $manager->persist($media4);

        // Admin medias can have albums for testing
        $media5 = new Media();
        $media5->setTitle('Forest Path');
        $media5->setPath('uploads/media/forest-path.jpg');
        $media5->setUser($admin);
        $media5->setAlbum($albumNature);
        $manager->persist($media5);

        $media6 = new Media();
        $media6->setTitle('Admin Portrait');
        $media6->setPath('uploads/media/admin-portrait.jpg');
        $media6->setUser($admin);
        $media6->setAlbum($albumPortrait);
        $manager->persist($media6);

        $manager->flush();
    }
}
