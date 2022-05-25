<?php

declare(strict_types=1);

namespace Keboola\TeradataTransformation;

use Doctrine\DBAL\Exception\DriverException;
use Keboola\Component\BaseComponent;
use Keboola\Component\UserException;
use Keboola\TeradataTransformation\Config\Config;
use Keboola\TeradataTransformation\Config\ConfigDefinition;

class Component extends BaseComponent
{
    protected function run(): void
    {
        try {
            $connection = ConnectionFactory::createFromConfig($this->getConfig());

            $transformation = new Transformation($connection, $this->getLogger());
            $transformation->processBlocks($this->getConfig()->getBlocks());

            $manifestBuilder = new ManifestWriter($connection, $this->getManifestManager());
            $manifestBuilder->process(
                $this->getConfig()->getDatabaseSchema(),
                $this->getConfig()->getExpectedOutputTables()
            );
        } catch (DriverException $exception) {
            throw new UserException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function getConfig(): Config
    {
        /** @var Config $config */
        $config = parent::getConfig();
        return $config;
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }
}
