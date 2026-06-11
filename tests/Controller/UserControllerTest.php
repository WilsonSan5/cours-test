<?php

namespace App\Tests\Controller;

use App\Entity\TodoList;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    /** @var EntityRepository<User> */
    private EntityRepository $userRepository;
    private string $path = '/user/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->userRepository = $this->manager->getRepository(User::class);

        foreach ($this->userRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('User index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $todoList = new TodoList();
        $todoList->setCreatedAt(new \DateTimeImmutable());
        $this->manager->persist($todoList);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'user[firstName]' => 'John',
            'user[lastName]' => 'Doe',
            'user[email]' => 'john.doe@example.com',
            'user[password]' => 'Pass1234!',
            'user[birthdate]' => '2010-01-01T00:00',
            'user[todoList]' => (string) $todoList->getId(),
        ]);

        self::assertResponseRedirects('/user');

        self::assertSame(1, $this->userRepository->count([]));
    }

    public function testShow(): void
    {
        $fixture = new User();
        $fixture->setFirstName('John');
        $fixture->setLastName('Doe');
        $fixture->setEmail('john.doe@example.com');
        $fixture->setPassword('Pass1234!');
        $fixture->setBirthdate(new \DateTimeImmutable('2010-01-01'));

        $todoList = new TodoList();
        $todoList->setCreatedAt(new \DateTimeImmutable());
        $this->manager->persist($todoList);

        $fixture->setTodoList($todoList);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('User');
    }

    public function testEdit(): void
    {
        $fixture = new User();
        $fixture->setFirstName('John');
        $fixture->setLastName('Doe');
        $fixture->setEmail('john.doe@example.com');
        $fixture->setPassword('Pass1234!');
        $fixture->setBirthdate(new \DateTimeImmutable('2010-01-01'));

        $todoList1 = new TodoList();
        $todoList1->setCreatedAt(new \DateTimeImmutable());
        $this->manager->persist($todoList1);

        $todoList2 = new TodoList();
        $todoList2->setCreatedAt(new \DateTimeImmutable());
        $this->manager->persist($todoList2);

        $fixture->setTodoList($todoList1);
        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'user[firstName]' => 'JohnNew',
            'user[lastName]' => 'DoeNew',
            'user[email]' => 'john.new@example.com',
            'user[password]' => 'Pass1234!',
            'user[birthdate]' => '2009-01-01T00:00',
            'user[todoList]' => (string) $todoList2->getId(),
        ]);

        self::assertResponseRedirects('/user');

        $fixture = $this->userRepository->findAll();

        self::assertSame('JohnNew', $fixture[0]->getFirstName());
        self::assertSame('DoeNew', $fixture[0]->getLastName());
        self::assertSame('john.new@example.com', $fixture[0]->getEmail());
        self::assertSame('Pass1234!', $fixture[0]->getPassword());
        self::assertEquals(new \DateTimeImmutable('2009-01-01'), $fixture[0]->getBirthdate());
        self::assertSame($todoList2->getId(), $fixture[0]->getTodoList()->getId());
    }

    public function testRemove(): void
    {
        $fixture = new User();
        $fixture->setFirstName('John');
        $fixture->setLastName('Doe');
        $fixture->setEmail('john.doe@example.com');
        $fixture->setPassword('Pass1234!');
        $fixture->setBirthdate(new \DateTimeImmutable('2010-01-01'));

        $todoList = new TodoList();
        $todoList->setCreatedAt(new \DateTimeImmutable());
        $this->manager->persist($todoList);

        $fixture->setTodoList($todoList);
        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/user');
        self::assertSame(0, $this->userRepository->count([]));
    }
}
