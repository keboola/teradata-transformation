<?php

declare(strict_types=1);

namespace Keboola\TeradataTransformation;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Keboola\Component\UserException;
use Keboola\TeradataTransformation\Config\Block;
use Keboola\TeradataTransformation\Config\Code;
use Keboola\TeradataTransformation\Config\Script;
use Psr\Log\LoggerInterface;
use SqlFormatter;
use Throwable;

class Transformation
{
    private Connection $connection;

    private LoggerInterface $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * @param Block[] $blocks
     */
    public function processBlocks(array $blocks): void
    {
        foreach ($blocks as $block) {
            $this->processBlock($block);
        }
    }

    public function processBlock(Block $block): void
    {
        $this->logger->info(sprintf('Processing block "%s".', $block->getName()));
        foreach ($block->getCodes() as $code) {
            $this->processCode($block, $code);
        }
    }

    public function processCode(Block $block, Code $code): void
    {
        $this->logger->info(sprintf('Processing code "%s".', $code->getName()));
        foreach ($code->getScripts() as $script) {
            $this->processScript($block, $code, $script);
        }
    }

    public function processScript(Block $block, Code $code, Script $script): void
    {
        $sql = SqlFormatter::removeComments($script->getSql());
        $sqlToLog = $this->queryExcerpt($script->getSql());

        // Do not execute empty queries
        if (strlen(trim($sql)) === 0) {
            return;
        }

        // Skip select
        if (strtoupper(substr($sql, 0, 6)) === 'SELECT' &&
            !strpos(strtoupper($sql), 'INTO')) {
            $this->logger->info(sprintf('Ignoring select query "%s".', $sqlToLog));
            return;
        }

        // Run
        $this->logger->info(sprintf('Running query "%s".', $sqlToLog));
        try {
            $this->connection->executeQuery($sql);
        } catch (Throwable $exception) {
            // Unwrap to get better error message
            if ($exception instanceof Exception) {
                $exception = $exception->getPrevious() ?? $exception;
            }

            $message = sprintf(
                'Query "%s" from block "%s" and code "%s" failed: "%s"',
                $this->queryExcerpt($sqlToLog),
                $block->getName(),
                $code->getName(),
                $exception->getMessage()
            );
            throw new UserException($message, 0, $exception);
        }
    }

    private function queryExcerpt(string $query): string
    {
        if (mb_strlen($query) > 1000) {
            return mb_substr($query, 0, 500, 'UTF-8') . "\n...\n" . mb_substr($query, -500, null, 'UTF-8');
        }
        return $query;
    }
}
