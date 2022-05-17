<?php

declare(strict_types=1);

namespace Keboola\TeradataTransformation\Config;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    public function getDatabaseHost(): string
    {
        return $this->getValue(['authorization', 'workspace', 'host']);
    }

    public function getDatabaseUser(): string
    {
        return $this->getValue(['authorization', 'workspace', 'user']);
    }

    public function getDatabasePassword(): string
    {
        return $this->getValue(['authorization', 'workspace', 'password']);
    }

    public function getDatabaseName(): string
    {
        return $this->getValue(['authorization', 'workspace', 'database']);
    }

    public function getDatabaseSchema(): string
    {
        return $this->getValue(['authorization', 'workspace', 'schema']);
    }

    /**
     * @return array<mixed>
     */
    public function getBlocks(): array
    {
        return array_map(
            fn(array $data) => new Block($data),
            $this->getValue(['parameters', 'blocks'])
        );
    }

    public function getDatabasePort(): int
    {
        return $this->getValue(['authorization', 'workspace', 'port']);
    }
}
