<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\LogSender\Controller\GraphQL;

use OxidSupport\Heartbeat\Component\LogSender\DataType\LogContentType;
use OxidSupport\Heartbeat\Component\LogSender\DataType\LogSource;
use OxidSupport\Heartbeat\Component\LogSender\DataType\LogSourceType;
use OxidSupport\Heartbeat\Component\LogSender\Service\LogCollectorServiceInterface;
use OxidSupport\Heartbeat\Component\LogSender\Service\LogReaderServiceInterface;
use OxidSupport\Heartbeat\Component\LogSender\Service\LogSenderStatusServiceInterface;
use OxidSupport\Heartbeat\Module\Module;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Right;

final class LogController
{
    /** Fallback ceiling when the configured max bytes is missing or invalid (1 MiB). */
    private const DEFAULT_MAX_BYTES = 1048576;

    public function __construct(
        private LogCollectorServiceInterface $logCollectorService,
        private LogReaderServiceInterface $logReaderService,
        private LogSenderStatusServiceInterface $statusService,
        private ModuleSettingServiceInterface $moduleSettingService,
    ) {
    }

    /**
     * Get all enabled log sources.
     *
     * @return LogSourceType[]
     */
    #[Query]
    #[Logged]
    #[Right('LOG_SENDER_VIEW')]
    public function logSenderSources(): array
    {
        if (!$this->statusService->isActive()) {
            return [];
        }

        $enabledSourceIds = $this->getEnabledSourceIds();
        $sources = $this->logCollectorService->getSources();

        $result = [];
        foreach ($sources as $source) {
            if (in_array($source->id, $enabledSourceIds, true) && $source->available) {
                $result[] = LogSourceType::fromLogSource($source);
            }
        }

        return $result;
    }

    /**
     * Get content from a specific log source.
     */
    #[Query]
    #[Logged]
    #[Right('LOG_SENDER_VIEW')]
    public function logSenderContent(string $sourceId, ?int $maxBytes = null): ?LogContentType
    {
        if (!$this->statusService->isActive()) {
            return null;
        }

        $enabledSourceIds = $this->getEnabledSourceIds();
        if (!in_array($sourceId, $enabledSourceIds, true)) {
            throw new \InvalidArgumentException("Source '{$sourceId}' is not enabled for sending.");
        }

        $source = $this->logCollectorService->getSourceById($sourceId);
        if (!$source->available) {
            throw new \InvalidArgumentException("Source '{$sourceId}' is not available.");
        }

        // The admin-configured max bytes is a hard ceiling, not just a default.
        // Clamp any caller-supplied value to it: a larger value would bypass the
        // limit and force the reader to load the whole file into memory (DoS).
        $configuredMax = (int) $this->moduleSettingService->getInteger(
            Module::SETTING_LOGSENDER_MAX_BYTES,
            Module::ID
        );
        if ($configuredMax <= 0) {
            $configuredMax = self::DEFAULT_MAX_BYTES;
        }

        $maxBytes = ($maxBytes === null)
            ? $configuredMax
            : max(1, min($maxBytes, $configuredMax));

        // Get the first available file from the source
        $filePath = $this->findFirstReadableFile($source);

        if ($filePath === null) {
            throw new \InvalidArgumentException("No readable file found in source '{$sourceId}'.");
        }

        $content = $this->logReaderService->readFile($filePath, $maxBytes);
        $fileInfo = $this->logReaderService->getFileInfo($filePath);
        $truncated = str_starts_with($content, '[...truncated...]');

        return new LogContentType(
            $source->id,
            $source->name,
            $filePath,
            $content,
            $fileInfo['size'],
            $fileInfo['modified'],
            $truncated,
        );
    }

    /**
     * @return string[]
     */
    private function getEnabledSourceIds(): array
    {
        try {
            $sources = $this->moduleSettingService->getCollection(
                Module::SETTING_LOGSENDER_ENABLED_SOURCES,
                Module::ID
            );
            return array_values($sources);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Find the first readable file from a log source.
     * Handles both FILE and DIRECTORY type paths.
     * For directories, returns the most recently modified file matching the pattern.
     */
    private function findFirstReadableFile(LogSource $source): ?string
    {
        foreach ($source->paths as $logPath) {
            if (!$logPath->exists() || !$logPath->isReadable()) {
                continue;
            }

            // For file paths, return directly
            if (!$logPath->isDirectory()) {
                return $logPath->path;
            }

            // For directory paths, find files matching the pattern
            $pattern = $logPath->filePattern ?? '*';
            $directory = rtrim($logPath->path, '/\\');
            $globPattern = $directory . DIRECTORY_SEPARATOR . $pattern;

            $files = glob($globPattern);
            if ($files === false || empty($files)) {
                continue;
            }

            // Filter to only actual files (not directories) that are readable
            $readableFiles = array_filter($files, fn($f) => is_file($f) && is_readable($f));
            if (empty($readableFiles)) {
                continue;
            }

            // Sort by modification time descending (newest first)
            usort($readableFiles, fn($a, $b) => filemtime($b) <=> filemtime($a));

            return $readableFiles[0];
        }

        return null;
    }
}
