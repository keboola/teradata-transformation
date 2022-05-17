<?php

declare(strict_types=1);

namespace Keboola\TeradataTransformation\TestTraits;

use Doctrine\DBAL\Connection;
use Keboola\Datatype\Definition\Teradata;
use Keboola\TableBackendUtils\Escaping\Exasol\ExasolQuote;
use Keboola\TableBackendUtils\Escaping\Teradata\TeradataQuote;

trait GetTablesTrait
{
    /**
     * @return array<mixed>
     */
    public function getTables(Connection $connection): array
    {
        $sqlTemplate = 'SELECT DatabaseName, TableName FROM DBC.TablesV WHERE TableKind = \'T\' and DatabaseName = %s';

        return $connection->executeQuery(
            sprintf($sqlTemplate, TeradataQuote::quote((string) getenv('TERADATA_DATABASE')))
        )->fetchAllAssociative();
    }
}
