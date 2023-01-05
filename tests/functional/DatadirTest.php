<?php

declare(strict_types=1);

namespace Keboola\TeradataTransformation\FunctionalTests;

use Doctrine\DBAL\Connection;
use Keboola\Csv\CsvWriter;
use Keboola\DatadirTests\AbstractDatadirTestCase;
use Keboola\DatadirTests\DatadirTestSpecificationInterface;
use Keboola\TableBackendUtils\Escaping\Teradata\TeradataQuote;
use Keboola\TableBackendUtils\Table\Teradata\TeradataTableQueryBuilder;
use Keboola\TeradataTransformation\TestTraits\CreateConnectionTrait;
use Keboola\TeradataTransformation\TestTraits\GetTableColumnsTrait;
use Keboola\TeradataTransformation\TestTraits\GetTablesTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Throwable;

class DatadirTest extends AbstractDatadirTestCase
{
    use CreateConnectionTrait;
    use GetTablesTrait;
    use GetTableColumnsTrait;

    private const DB_DUMP_IGNORED_METADATA = [
        'COLUMN_SCHEMA',
        'COLUMN_OWNER',
        'COLUMN_OBJECT_ID',
    ];

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @dataProvider provideDatadirSpecifications
     */
    public function testDatadir(DatadirTestSpecificationInterface $specification): void
    {
        $tempDatadir = $this->getTempDatadir($specification);

        // Setup initial db state
        $this->dropAllTables();

        // Run script
        $process = $this->runScript($tempDatadir->getTmpFolder());

        // Dump database data & create statement after running the script
        $this->dumpAllTables($tempDatadir->getTmpFolder());

        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function assertDirectoryContentsSame(string $expected, string $actual): void
    {
        $this->prettifyAllManifests($actual);
        parent::assertDirectoryContentsSame($expected, $actual);
    }

    protected function dropAllTables(): void
    {
        $queryBuilder = new TeradataTableQueryBuilder();
        // Drop all tables
        $connection = $this->createConnection();

        foreach ($this->getTables($connection) as $table) {
            $connection->executeQuery(
                $queryBuilder->getDropTableCommand(
                    $table['DataBaseName'],
                    $table['TableName']
                )
            );
        }
    }


    protected function dumpAllTables(string $tmpDir): void
    {
        // Create output dir
        $dumpDir = $tmpDir . '/out/db-dump';
        $fs = new Filesystem();
        $fs->mkdir($dumpDir, 0777);

        // Create connection and get tables
        $connection = $this->createConnection();
        foreach ($this->getTables($connection) as $table) {
            $this->dumpTable($connection, $table, $dumpDir);
        }
    }

    /**
     * @param array<mixed> $table
     */
    protected function dumpTable(Connection $connection, array $table, string $dumpDir): void
    {
        // Generate create statement
        $metadata = $this->getTableColumns($connection, $table);

        // Ignore non-static keys
        $metadata = array_map(fn(array $item) => array_filter(
            $item,
            fn(string $key) => !in_array($key, self::DB_DUMP_IGNORED_METADATA, true),
            ARRAY_FILTER_USE_KEY
        ), $metadata);

        // Save create statement
        file_put_contents(
            sprintf('%s/%s.metadata.json', $dumpDir, $table['TableName']),
            json_encode($metadata, JSON_PRETTY_PRINT)
        );

        // Dump data
        $this->dumpTableData($connection, $table, $dumpDir);
    }

    /**
     * @param array<mixed> $table
     */
    protected function dumpTableData(
        Connection $connection,
        array $table,
        string $dumpDir
    ): void {
        $csv = new CsvWriter(sprintf('%s/%s.data.csv', $dumpDir, $table['TableName']));

        // Write header
        $columns = array_values(array_map(
            fn(array $col) => trim($col['ColumnName']),
            $this->getTableColumns($connection, $table)
        ));
        $csv->writeRow($columns);

        // Write data
        $data = $connection->executeQuery(sprintf(
            'SELECT %s FROM %s ORDER BY %s',
            implode(
                ', ',
                array_map(
                    fn(string $col) => TeradataQuote::quoteSingleIdentifier($col),
                    $columns
                )
            ),
            $connection->quoteIdentifier($table['TableName']),
            $connection->quoteIdentifier($columns[0])
        ))->fetchAllAssociative();
        foreach ($data as $row) {
            $csv->writeRow($row);
        }
    }

    protected function prettifyAllManifests(string $actual): void
    {
        foreach ($this->findManifests($actual . '/tables') as $file) {
            $this->prettifyJsonFile((string) $file->getRealPath());
        }
    }

    protected function prettifyJsonFile(string $path): void
    {
        $json = (string) file_get_contents($path);
        try {
            file_put_contents($path, (string) json_encode(json_decode($json), JSON_PRETTY_PRINT));
        } catch (Throwable $e) {
            // If a problem occurs, preserve the original contents
            file_put_contents($path, $json);
        }
    }

    protected function findManifests(string $dir): Finder
    {
        $finder = new Finder();
        return $finder->files()->in($dir)->name(['~.*\.manifest~']);
    }
}
