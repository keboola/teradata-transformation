<?php

declare(strict_types=1);

namespace Keboola\TeradataTransformation\TestTraits;

use Doctrine\DBAL\Connection;
use Keboola\TableBackendUtils\Escaping\Teradata\TeradataQuote;

trait GetTableColumnsTrait
{
    /**
     * @param array<mixed> $table
     * @return array<mixed>
     */
    public function getTableColumns(Connection $connection, array $table): array
    {
        $sqlTemplate = 'select columnname from dbc.columns where tablename = %s and databasename = %s order by %s;';

        return $connection->executeQuery(
            sprintf(
                $sqlTemplate,
                TeradataQuote::quote($table['TableName']),
                TeradataQuote::quote($table['DataBaseName']),
                TeradataQuote::quoteSingleIdentifier('ColumnId')
            )
        )->fetchAllAssociative();
    }
}
