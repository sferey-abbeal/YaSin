<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 * @codingStandardsIgnoreFile
 */
final class Version20190730140001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activity ADD cover_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_AC74095A922726E9 FOREIGN KEY (cover_id) REFERENCES image (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AC74095A922726E9 ON activity (cover_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_AC74095A922726E9');
        $this->addSql('DROP INDEX UNIQ_AC74095A922726E9 ON activity');
        $this->addSql('ALTER TABLE activity DROP cover_id');
    }
}
