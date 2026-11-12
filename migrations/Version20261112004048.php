<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261112004048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE app_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, full_name VARCHAR(120) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_88BDF3E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE customer_order (id INT AUTO_INCREMENT NOT NULL, reference VARCHAR(20) NOT NULL, channel VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, total_cents INT NOT NULL, created_at DATETIME NOT NULL, paid_at DATETIME DEFAULT NULL, cancelled_at DATETIME DEFAULT NULL, refunded_cents INT NOT NULL, customer_id INT DEFAULT NULL, seller_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_3B1CE6A3AEA34913 (reference), INDEX IDX_3B1CE6A39395C3F3 (customer_id), INDEX IDX_3B1CE6A38DE820D9 (seller_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(160) NOT NULL, type VARCHAR(20) NOT NULL, description LONGTEXT DEFAULT NULL, duration_minutes INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE performance (id INT AUTO_INCREMENT NOT NULL, starts_at DATETIME NOT NULL, status VARCHAR(20) NOT NULL, event_id INT NOT NULL, venue_id INT NOT NULL, INDEX IDX_82D7968171F7E88B (event_id), INDEX IDX_82D7968140A73EBA (venue_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE performance_seat (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, held_until DATETIME DEFAULT NULL, performance_id INT NOT NULL, seat_id INT NOT NULL, current_order_id INT DEFAULT NULL, INDEX idx_perfseat_status (performance_id, status), INDEX idx_perfseat_held (status, held_until), UNIQUE INDEX uniq_performance_seat (performance_id, seat_id), INDEX IDX_F5A7B85BB91ADEEE (performance_id), INDEX IDX_F5A7B85BC1DAFE35 (seat_id), INDEX IDX_F5A7B85B10E71712 (current_order_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE price (id INT AUTO_INCREMENT NOT NULL, amount_cents INT NOT NULL, performance_id INT NOT NULL, zone_id INT NOT NULL, tariff_id INT NOT NULL, UNIQUE INDEX uniq_price (performance_id, zone_id, tariff_id), INDEX IDX_CAC822D9B91ADEEE (performance_id), INDEX IDX_CAC822D99F2C3FAB (zone_id), INDEX IDX_CAC822D992348FD2 (tariff_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE seat (id INT AUTO_INCREMENT NOT NULL, row_label VARCHAR(5) NOT NULL, number INT NOT NULL, zone_id INT NOT NULL, UNIQUE INDEX uniq_seat_position (zone_id, row_label, number), INDEX IDX_3D5C36669F2C3FAB (zone_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tariff (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(30) NOT NULL, name VARCHAR(80) NOT NULL, staff_only TINYINT NOT NULL, min_quantity INT NOT NULL, UNIQUE INDEX UNIQ_9465207D77153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ticket (id INT AUTO_INCREMENT NOT NULL, price_paid_cents INT NOT NULL, status VARCHAR(20) NOT NULL, qr_token VARCHAR(64) NOT NULL, order_id INT NOT NULL, performance_seat_id INT NOT NULL, tariff_id INT NOT NULL, UNIQUE INDEX UNIQ_97A0ADA31AE26361 (qr_token), INDEX IDX_97A0ADA38D9F6D38 (order_id), INDEX IDX_97A0ADA36206166F (performance_seat_id), INDEX IDX_97A0ADA392348FD2 (tariff_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE venue (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, address VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE zone (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(80) NOT NULL, color VARCHAR(7) NOT NULL, display_order INT NOT NULL, venue_id INT NOT NULL, INDEX IDX_A0EBC00740A73EBA (venue_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_3B1CE6A39395C3F3 FOREIGN KEY (customer_id) REFERENCES app_user (id)');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_3B1CE6A38DE820D9 FOREIGN KEY (seller_id) REFERENCES app_user (id)');
        $this->addSql('ALTER TABLE performance ADD CONSTRAINT FK_82D7968171F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE performance ADD CONSTRAINT FK_82D7968140A73EBA FOREIGN KEY (venue_id) REFERENCES venue (id)');
        $this->addSql('ALTER TABLE performance_seat ADD CONSTRAINT FK_F5A7B85BB91ADEEE FOREIGN KEY (performance_id) REFERENCES performance (id)');
        $this->addSql('ALTER TABLE performance_seat ADD CONSTRAINT FK_F5A7B85BC1DAFE35 FOREIGN KEY (seat_id) REFERENCES seat (id)');
        $this->addSql('ALTER TABLE performance_seat ADD CONSTRAINT FK_F5A7B85B10E71712 FOREIGN KEY (current_order_id) REFERENCES customer_order (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE price ADD CONSTRAINT FK_CAC822D9B91ADEEE FOREIGN KEY (performance_id) REFERENCES performance (id)');
        $this->addSql('ALTER TABLE price ADD CONSTRAINT FK_CAC822D99F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id)');
        $this->addSql('ALTER TABLE price ADD CONSTRAINT FK_CAC822D992348FD2 FOREIGN KEY (tariff_id) REFERENCES tariff (id)');
        $this->addSql('ALTER TABLE seat ADD CONSTRAINT FK_3D5C36669F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA38D9F6D38 FOREIGN KEY (order_id) REFERENCES customer_order (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA36206166F FOREIGN KEY (performance_seat_id) REFERENCES performance_seat (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA392348FD2 FOREIGN KEY (tariff_id) REFERENCES tariff (id)');
        $this->addSql('ALTER TABLE zone ADD CONSTRAINT FK_A0EBC00740A73EBA FOREIGN KEY (venue_id) REFERENCES venue (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer_order DROP FOREIGN KEY FK_3B1CE6A39395C3F3');
        $this->addSql('ALTER TABLE customer_order DROP FOREIGN KEY FK_3B1CE6A38DE820D9');
        $this->addSql('ALTER TABLE performance DROP FOREIGN KEY FK_82D7968171F7E88B');
        $this->addSql('ALTER TABLE performance DROP FOREIGN KEY FK_82D7968140A73EBA');
        $this->addSql('ALTER TABLE performance_seat DROP FOREIGN KEY FK_F5A7B85BB91ADEEE');
        $this->addSql('ALTER TABLE performance_seat DROP FOREIGN KEY FK_F5A7B85BC1DAFE35');
        $this->addSql('ALTER TABLE performance_seat DROP FOREIGN KEY FK_F5A7B85B10E71712');
        $this->addSql('ALTER TABLE price DROP FOREIGN KEY FK_CAC822D9B91ADEEE');
        $this->addSql('ALTER TABLE price DROP FOREIGN KEY FK_CAC822D99F2C3FAB');
        $this->addSql('ALTER TABLE price DROP FOREIGN KEY FK_CAC822D992348FD2');
        $this->addSql('ALTER TABLE seat DROP FOREIGN KEY FK_3D5C36669F2C3FAB');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA38D9F6D38');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA36206166F');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA392348FD2');
        $this->addSql('ALTER TABLE zone DROP FOREIGN KEY FK_A0EBC00740A73EBA');
        $this->addSql('DROP TABLE app_user');
        $this->addSql('DROP TABLE customer_order');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE performance');
        $this->addSql('DROP TABLE performance_seat');
        $this->addSql('DROP TABLE price');
        $this->addSql('DROP TABLE seat');
        $this->addSql('DROP TABLE tariff');
        $this->addSql('DROP TABLE ticket');
        $this->addSql('DROP TABLE venue');
        $this->addSql('DROP TABLE zone');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
