<?php
declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250903_000001_init extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Init tables: categories, products, product_category';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->addSql("CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            inn VARCHAR(12) NOT NULL,
            ean13 VARCHAR(13) NOT NULL,
            description TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uniq_inn_ean (inn, ean13),
            INDEX idx_products_inn (inn),
            INDEX idx_products_ean13 (ean13)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->addSql("CREATE TABLE IF NOT EXISTS product_category (
            product_id INT NOT NULL,
            category_id INT NOT NULL,
            PRIMARY KEY(product_id, category_id),
            CONSTRAINT fk_pc_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            CONSTRAINT fk_pc_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS product_category");
        $this->addSql("DROP TABLE IF EXISTS products");
        $this->addSql("DROP TABLE IF EXISTS categories");
    }
}
