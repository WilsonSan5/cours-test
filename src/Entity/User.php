<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $birthdate = null;

    #[ORM\OneToOne(mappedBy: 'user_id', cascade: ['persist', 'remove'])]
    private ?TodoList $todoList = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getBirthdate(): ?\DateTimeImmutable
    {
        return $this->birthdate;
    }

    public function setBirthdate(\DateTimeImmutable $birthdate): static
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getTodoList(): ?TodoList
    {
        return $this->todoList;
    }

    public function setTodoList(?TodoList $todoList): static
    {
        if($this->isValid() == false){
            throw new \Exception("User is not valid");
        }

        // unset the owning side of the relation if necessary
        if ($this->todoList !== null && $this->todoList !== $todoList) {
            $this->todoList->setUserId(null);
        }

        // set the owning side of the relation if necessary
        if ($todoList !== null && $todoList->getUserId() !== $this) {
            $todoList->setUserId($this);
        }

        $this->todoList = $todoList;

        return $this;
    }

    public function isValid():bool 
    {
        if($this->getEmail() == null || !filter_var($this->getEmail(), FILTER_VALIDATE_EMAIL)){
            return false;
        }

        if($this->getFirstname() == null || $this->getLastname() == null){
            return false;
        }

        if($this->passwordIsValid() == false) {
            return false;
        }

        // if($this->getBirthdate() == null || !DateTimeImmutable::createFromFormat('Y-m-d', $this->getBirthdate())){
        //     return false;
        // }

        $today = new DateTimeImmutable();
        $birthdate = $this->getBirthdate();
        if($today->diff($birthdate)->y < 13){
            return false;
        }
        return true;
    }

    public function passwordIsValid():bool 
    {
        // password length: between 8 and 40 chars
        $password = $this->getPassword();
        if ($password === null) {
            return false;
        }
        $len = strlen($password);
        if ($len < 8 || $len > 40) {
            return false;
        }
        // must contain at least one uppercase, one lowercase and one digit
        if (!preg_match('/(?=.*[A-Z])(?=.*[a-z])(?=.*\d)/', $password)) {
            return false;
        }
        return true;
    }
}
