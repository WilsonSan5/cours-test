<?php

namespace App\Entity;

use App\Repository\ListItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ListItemRepository::class)]
class ListItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\ManyToOne(inversedBy: 'listItems')]
    private ?TodoList $TodoList = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getTodoList(): ?TodoList
    {
        return $this->TodoList;
    }

    public function setTodoList(?TodoList $TodoList): static
    {
        $this->TodoList = $TodoList;

        return $this;
    }

    public function isValid(): bool
    {
        if ($this->title === null || trim($this->title) === '') {
            return false;
        }

        if (strlen($this->title) > 1000) {
            return false;
        }

        if ($this->created_at === null) {
            return false;
        }

        return true;
    }
}

