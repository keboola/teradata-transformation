<?php

declare(strict_types=1);

namespace Keboola\TeradataTransformation;

use Doctrine\DBAL\Connection;
use Keboola\Component\Manifest\ManifestManager;
use Keboola\Component\Manifest\ManifestManager\Options\OutTableManifestOptions;
use Keboola\Component\UserException;
use Keboola\Datatype\Definition\Teradata;
use Keboola\TableBackendUtils\Column\ColumnInterface;
use Keboola\TableBackendUtils\Table\Teradata\TeradataTableReflection;

class ManifestWriter
{
    private Connection $connection;

    private ManifestManager $manifestManager;

    public function __construct(Connection $connection, ManifestManager $manifestManager)
    {
        $this->connection = $connection;
        $this->manifestManager = $manifestManager;
    }

    /**
     * @param array<mixed> $outputMappingTables
     */
    public function process(string $dbName, array $outputMappingTables): void
    {
        $missingTables = [];
        foreach ($outputMappingTables as $outputMappingTable) {
            $tableName = $outputMappingTable['source'];
            if (!$this->processTable($dbName, $tableName)) {
                $missingTables[] = $tableName;
            }
        }

        // Are there any missing tables?
        if ($missingTables) {
            throw new UserException(sprintf(
                '%s "%s" specified in output were not created by the transformation.',
                count($missingTables) === 1 ? 'Table' : 'Tables',
                implode('", "', $missingTables)
            ));
        }
    }

    private function processTable(string $dbName, string $tableName): bool
    {
        $tableReflection = new TeradataTableReflection($this->connection, $dbName, $tableName);
        $columns = $tableReflection->getColumnsDefinitions();
        if ($columns->count() === 0) {
            // Table is missing
            return false;
        }

        $metadata = [];
        /** @var ColumnInterface  $column */
        foreach ($columns as $column) {
            $name = $column->getColumnName();
            $type = $column->getColumnDefinition();
            assert($type instanceof Teradata);
            $metadata[$name] = $type->toMetadata();
        }

        $data = new OutTableManifestOptions();
        $data->setColumns(array_keys($metadata));
        $data->setColumnMetadata($metadata);
        $this->manifestManager->writeTableManifest($tableName, $data);
        return true;
    }
}
