<?php

namespace App\Entity;

use App\Repository\ServerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServerRepository::class)]
class Server
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $crc32server = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $added = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $updated = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $online = null;

    #[ORM\Column(length: 255)]
    private ?string $host = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $port = null;

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

    public function getAdded(): ?int
    {
        return $this->added;
    }

    public function setAdded(int $added): static
    {
        $this->added = $added;

        return $this;
    }

    public function getUpdated(): ?int
    {
        return $this->updated;
    }

    public function setUpdated(int $updated): static
    {
        $this->updated = $updated;

        return $this;
    }

    public function getOnline(): ?int
    {
        return $this->online;
    }

    public function setOnline(int $online): static
    {
        $this->online = $online;

        return $this;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(string $host): static
    {
        $this->host = $host;

        return $this;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(int $port): static
    {
        $this->port = $port;

        return $this;
    }
}
