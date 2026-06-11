<?php

use PHPUnit\Framework\TestCase;
use App\Entity\User;

class UserTest extends TestCase
{

    private User $user;

    public function setup(): void
    {

        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setFirstName('John');
        $this->user->setLastName('Doe');
        $this->user->setPassword('Password123');
        $this->user->setBirthdate(new \DateTimeImmutable('2000-01-01'));
    }
    public function testUserIsValid()
    {
        $this->assertTrue($this->user->isValid());
    }

    public function testUserEmailNotValid()
    {
        $this->user->setEmail('invalid-email');
        $this->assertFalse($this->user->isValid());
    }

    public function testUserPasswordTooShort()
    {
        $this->user->setPassword('short');
        $this->assertFalse($this->user->passwordIsValid());
    }

    public function testUserPasswordTooLong()
    {
        $this->user->setPassword(str_repeat('a', 41));
        $this->assertFalse($this->user->passwordIsValid());
    }

    public function testUserPasswordRegex()
    {
        $this->user->setPassword('password213');
        $this->assertFalse($this->user->passwordIsValid());
    }

    public function testUserIsTooYoung(){
        $this->user->setBirthdate((new \DateTimeImmutable())->sub(new \DateInterval('P12Y')));
        $this->assertFalse($this->user->isValid());
    }


}
