<?php
declare(strict_types=1);

namespace App\Console\Command;

use App\Application\Search\ElasticHealthChecker;
use App\Application\Search\ElasticProductSearch;
use App\Domain\Repository\CategoryRepositoryInterface;
use App\Domain\Repository\ProductRepositoryInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class SeedDemoCommand extends Command
{
    protected static $defaultName = 'app:seed-demo';
    protected static $defaultDescription = 'Seed demo categories & products';

    public function __construct(
        private readonly Connection                  $db,
        private readonly CategoryRepositoryInterface $catRepo,
        private readonly ProductRepositoryInterface  $prodRepo,
        private readonly ElasticProductSearch        $indexer,
        private readonly ElasticHealthChecker        $health,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Products to create', '10')
            ->addOption('purge', 'p', InputOption::VALUE_NONE, 'Purge tables before seeding');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = max(1, (int)$input->getOption('count'));
        $purge = (bool)$input->getOption('purge');

        if ($purge) {
            $output->writeln('<comment>Purging tables…</comment>');
            $this->db->executeStatement('DELETE FROM product_category');
            $this->db->executeStatement('DELETE FROM products');
            $this->db->executeStatement('DELETE FROM categories');
        }

        $names = ['Food', 'Beverages', 'Electronics', 'Books', 'Home'];
        $catIds = [];
        foreach ($names as $n) {
            $id = (int)$this->db->fetchOne('SELECT id FROM categories WHERE name = ? LIMIT 1', [$n]);
            if (!$id) $id = $this->catRepo->create($n);
            $catIds[] = $id;
        }
        $output->writeln('<info>Categories ready: ' . count($catIds) . '</info>');

        $created = 0;
        $tries = 0;
        while ($created < $count && $tries < $count * 3) {
            $tries++;
            $name = 'Product ' . ($created + 1);
            $inn = $this->generateInn10();
            $ean = $this->generateEan13();
            $desc = 'Demo product #' . ($created + 1);

            shuffle($catIds);
            $attach = array_slice($catIds, 0, random_int(1, 3));

            try {
                $id = $this->prodRepo->create([
                    'name' => $name,
                    'inn' => $inn,
                    'ean13' => $ean,
                    'description' => $desc,
                ], $attach);

                if ($this->health->isAlive()) {
                    $row = $this->prodRepo->find($id);
                    if ($row) $this->indexer->indexOne($row);
                }

                $output->writeln(sprintf('  - #%d id=%d inn=%s ean=%s [%s]',
                    $created + 1, $id, $inn, $ean, implode(',', $attach)
                ));
                $created++;
            } catch (Throwable) {
                continue;
            }
        }

        $output->writeln('<info>Seeded products: ' . $created . '</info>');
        return Command::SUCCESS;
    }

    private function generateInn10(): string
    {
        $d = [];
        for ($i = 0; $i < 9; $i++) $d[$i] = random_int(0, 9);
        $w = [2, 4, 10, 3, 5, 9, 4, 6, 8];
        $s = 0;
        for ($i = 0; $i < 9; $i++) $s += $d[$i] * $w[$i];
        $d10 = ($s % 11) % 10;
        return implode('', $d) . $d10;
    }

    private function generateEan13(): string
    {
        $d = [];
        for ($i = 0; $i < 12; $i++) $d[$i] = random_int(0, 9);
        $s = 0;
        for ($i = 0; $i < 12; $i++) $s += $d[$i] * (($i % 2) ? 3 : 1);
        $chk = (10 - ($s % 10)) % 10;
        return implode('', $d) . $chk;
    }
}
