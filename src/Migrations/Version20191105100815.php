<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 * @codingStandardsIgnoreFile
 */
final class Version20191105100815 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activity_to_activity_type DROP FOREIGN KEY FK_E4E86685C51EFA73');
        $this->addSql('DROP TABLE activity_to_activity_type');
        $this->addSql('DROP TABLE activity_type');
        $this->addSql('ALTER TABLE activity DROP application_deadline, DROP final_deadline, DROP status');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE activity_to_activity_type (activity_id INT NOT NULL, activity_type_id INT NOT NULL, INDEX IDX_E4E8668581C06096 (activity_id), INDEX IDX_E4E86685C51EFA73 (activity_type_id), PRIMARY KEY(activity_id, activity_type_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE activity_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, description LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE activity_to_activity_type ADD CONSTRAINT FK_E4E8668581C06096 FOREIGN KEY (activity_id) REFERENCES activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity_to_activity_type ADD CONSTRAINT FK_E4E86685C51EFA73 FOREIGN KEY (activity_type_id) REFERENCES activity_type (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity ADD application_deadline DATETIME NOT NULL, ADD final_deadline DATETIME NOT NULL, ADD status INT NOT NULL');
    }
}
