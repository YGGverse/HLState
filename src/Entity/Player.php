<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $crc32server = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $crc32name = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $joined = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $updated = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $online = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $frags = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCrc32server(): ?string
    {
        return $this->crc32server;
    }

    public function setCrc32server(string $crc32server): static
    {
        $this->crc32server = $crc32server;

        return $this;
    }

    public function getCrc32name(): ?string
    {
        return $this->crc32name;
    }

    public function setCrc32name(string $crc32name): static
    {
        $this->crc32name = $crc32name;

        return $this;
    }

    public function getJoined(): ?string
    {
        return $this->joined;
    }

    public function setJoined(string $joined): static
    {
        $this->joined = $joined;

        return $this;
    }

    public function getUpdated(): ?string
    {
        return $this->updated;
    }

    public function setUpdated(string $updated): static
    {
        $this->updated = $updated;

        return $this;
    }

    public function getOnline(): ?string
    {
        return $this->online;
    }

    public function setOnline(string $online): static
    {
        $this->online = $online;

        return $this;
    }

    public function getFrags(): ?string
    {
        return $this->frags;
    }

    public function setFrags(string $frags): static
    {
        $this->frags = $frags;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
