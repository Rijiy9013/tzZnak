<?php
declare(strict_types=1);

namespace App\Console\Command;

use Doctrine\DBAL\Connection;
use Elastic\Elasticsearch\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class ReindexProductsCommand extends Command
{
    protected static $defaultName = 'app:reindex-products';
    protected static $defaultDescription = 'Rebuild Elasticsearch index from DB';

    public function __construct(
        private readonly Connection $db,
        private readonly Client     $es
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $index = 'products';

        try {
            $this->es->indices()->create(['index' => $index]);
        } catch (Throwable) {
        }

        $rows = $this->db->createQueryBuilder()
            ->select('p.*', 'GROUP_CONCAT(pc.category_id) AS category_ids')
            ->from('products', 'p')
            ->leftJoin('p', 'product_category', 'pc', 'pc.product_id = p.id')
            ->groupBy('p.id')
            ->fetchAllAssociative();

        $count = 0;
        foreach ($rows as $r) {
            $r['category_ids'] = $r['category_ids']
                ? array_map('intval', array_filter(explode(',', (string)$r['category_ids'])))
                : [];
            $this->es->index(['index' => $index, 'id' => (string)$r['id'], 'body' => $r]);
            $count++;
        }

        $output->writeln("<info>Reindexed: {$count}</info>");
        return Command::SUCCESS;
    }
}
