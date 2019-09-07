<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 * @codingStandardsIgnoreFile
 */
final class Version20190812102815 extends AbstractMigration
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

        $this->addSql('ALTER TABLE user ADD project_manager_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64960984F51 FOREIGN KEY (project_manager_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_8D93D64960984F51 ON user (project_manager_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64960984F51');
        $this->addSql('DROP INDEX IDX_8D93D64960984F51 ON user');
        $this->addSql('ALTER TABLE user DROP project_manager_id');
    }
}
