<?php

namespace App\Tests\Controller;

use App\Entity\TodoList;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TodoListControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    /** @var EntityRepository<TodoList> */
    private EntityRepository $todoListRepository;
    private string $path = '/todo/list/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->todoListRepository = $this->manager->getRepository(TodoList::class);

        foreach ($this->todoListRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('TodoList index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmail('john.doe@example.com');
        $user->setPassword('Pass1234!');
        $user->setBirthdate(new \DateTimeImmutable('-20 years'));
        $this->manager->persist($user);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'todo_list[created_at]' => '2026-06-11 12:00:00',
            'todo_list[user_id]' => (string) $user->getId(),
        ]);

        self::assertResponseRedirects('/todo/list');

        self::assertSame(1, $this->todoListRepository->count([]));
    }

    public function testShow(): void
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmail('john.doe@example.com');
        $user->setPassword('Pass1234!');
        $user->setBirthdate(new \DateTimeImmutable('-20 years'));
        $this->manager->persist($user);
        $this->manager->flush();

        $fixture = new TodoList();
        $fixture->setCreatedAt(new \DateTimeImmutable('2026-06-11 12:00:00'));
        $fixture->setUserId($user);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('TodoList');
    }

    public function testEdit(): void
    {
        $user1 = new User();
        $user1->setFirstName('John');
        $user1->setLastName('Doe');
        $user1->setEmail('john.doe1@example.com');
        $user1->setPassword('Pass1234!');
        $user1->setBirthdate(new \DateTimeImmutable('-20 years'));
        $this->manager->persist($user1);

        $user2 = new User();
        $user2->setFirstName('Jane');
        $user2->setLastName('Doe');
        $user2->setEmail('jane.doe2@example.com');
        $user2->setPassword('Pass1234!');
        $user2->setBirthdate(new \DateTimeImmutable('-25 years'));
        $this->manager->persist($user2);

        $fixture = new TodoList();
        $fixture->setCreatedAt(new \DateTimeImmutable('2026-06-11 12:00:00'));
        $fixture->setUserId($user1);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'todo_list[created_at]' => '2026-06-12 12:00:00',
            'todo_list[user_id]' => (string) $user2->getId(),
        ]);

        self::assertResponseRedirects('/todo/list');

        $fixture = $this->todoListRepository->findAll();

        self::assertEquals(new \DateTimeImmutable('2026-06-12 12:00:00'), $fixture[0]->getCreatedAt());
        self::assertSame($user2->getId(), $fixture[0]->getUserId()->getId());
    }

    public function testRemove(): void
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmail('john.doe@example.com');
        $user->setPassword('Pass1234!');
        $user->setBirthdate(new \DateTimeImmutable('-20 years'));
        $this->manager->persist($user);

        $fixture = new TodoList();
        $fixture->setCreatedAt(new \DateTimeImmutable('2026-06-11 12:00:00'));
        $fixture->setUserId($user);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/todo/list');
        self::assertSame(0, $this->todoListRepository->count([]));
    }
}
