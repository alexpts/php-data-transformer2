<?php
declare(strict_types = 1);

class ChildModel
{
    protected $id;
    /** @var string */
    protected $name;
    /** @var string */
    protected $login;
    /** @var DateTime */
    protected $creAt;
    /** @var bool */
    protected $active;

    public function __construct()
    {
        $this->creAt = new DateTime;
    }

    public function __toString(): string
    {
       return (string)$this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /** @var string */
    protected $email;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    public function getCreAt(): DateTime
    {
        return $this->creAt;
    }

    public function setCreAt(DateTime $creAt): void
    {
        $this->creAt = $creAt;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getCreAtTimestamp(): int
    {
        return $this->creAt->getTimestamp();
    }

    public function getTitleName(string $title): string
    {
        return $title . ' ' . $this->name;
    }

    public function setTitleName(string $name, string $suffix): void
    {
        $this->name = $name . ' ' . $suffix;
    }
}
