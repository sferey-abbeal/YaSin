<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 * @codingStandardsIgnoreFile
 */
final class Version20190627084936 extends AbstractMigration
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

        $this->addSql('CREATE TABLE activity_user (id INT AUTO_INCREMENT NOT NULL, activity_id INT DEFAULT NULL, user_id INT DEFAULT NULL, type INT NOT NULL, INDEX IDX_8E570DDB81C06096 (activity_id), INDEX IDX_8E570DDBA76ED395 (user_id), UNIQUE INDEX UNIQ_8E570DDB81C06096A76ED395 (activity_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE activity_user ADD CONSTRAINT FK_8E570DDB81C06096 FOREIGN KEY (activity_id) REFERENCES activity (id)');
        $this->addSql('ALTER TABLE activity_user ADD CONSTRAINT FK_8E570DDBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE activity_user');
    }
}
