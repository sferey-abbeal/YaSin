<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 * @codingStandardsIgnoreFile
 */
final class Version20190802094319 extends AbstractMigration
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

        $this->addSql('CREATE TABLE feedback (id INT AUTO_INCREMENT NOT NULL, user_from_id INT DEFAULT NULL, user_to_id INT DEFAULT NULL, activity_id INT DEFAULT NULL, stars INT NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_D229445820C3C701 (user_from_id), INDEX IDX_D2294458D2F7B13D (user_to_id), INDEX IDX_D229445881C06096 (activity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D229445820C3C701 FOREIGN KEY (user_from_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D2294458D2F7B13D FOREIGN KEY (user_to_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D229445881C06096 FOREIGN KEY (activity_id) REFERENCES activity (id)');
        $this->addSql('ALTER TABLE user ADD stars DOUBLE PRECISION NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE feedback');
        $this->addSql('ALTER TABLE user DROP stars');
    }
}
