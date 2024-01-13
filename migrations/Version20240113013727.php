<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240113013727 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE server ADD COLUMN name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__server AS SELECT id, crc32server, added, updated, online, host, port FROM server');
        $this->addSql('DROP TABLE server');
        $this->addSql('CREATE TABLE server (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, crc32server BIGINT NOT NULL, added BIGINT NOT NULL, updated BIGINT NOT NULL, online BIGINT NOT NULL, host VARCHAR(255) NOT NULL, port INTEGER NOT NULL)');
        $this->addSql('INSERT INTO server (id, crc32server, added, updated, online, host, port) SELECT id, crc32server, added, updated, online, host, port FROM __temp__server');
        $this->addSql('DROP TABLE __temp__server');
    }
}
