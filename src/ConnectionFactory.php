<?php

declare(strict_types=1);

namespace Keboola\TeradataTransformation;

use Doctrine\DBAL\Connection;
use Keboola\TableBackendUtils\Connection\Teradata\TeradataConnection;
use Keboola\TableBackendUtils\Escaping\Teradata\TeradataQuote;
use Keboola\TeradataTransformation\Config\Config;

class ConnectionFactory
{
    public static function createFromConfig(Config $config): Connection
    {
        $connection = TeradataConnection::getConnection([
            'host' => $config->getDatabaseHost(),
            'user' => $config->getDatabaseUser(),
            'password' => $config->getDatabasePassword(),
            'port' => $config->getDatabasePort(),
            'dbname' => '',
        ]);

        $connection->executeStatement(sprintf(
            'SET SESSION DATABASE %s;',
            TeradataQuote::quoteSingleIdentifier($config->getDatabaseName())
        ));

        return $connection;
    }
}
