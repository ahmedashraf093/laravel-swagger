<?php

namespace RonasIT\Support\AutoDoc\Drivers;

use RonasIT\Support\AutoDoc\Interfaces\SwaggerDriverInterface;
use RuntimeException;

abstract class BaseDriver implements SwaggerDriverInterface
{
    protected string $tempFilePath;

    public function __construct()
    {
        $this->tempFilePath = storage_path('temp_documentation.json');
    }

    public function saveTmpData($data): void
    {
        $this->withTmpFileHandle('c+', function ($handle) use ($data) {
            $this->lockHandle($handle, LOCK_EX);

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($data, JSON_THROW_ON_ERROR));

            flock($handle, LOCK_UN);
        });
    }

    public function getTmpData(): ?array
    {
        return $this->withTmpFileHandle('r', function ($handle) {
            $this->lockHandle($handle, LOCK_SH);

            $content = stream_get_contents($handle);

            flock($handle, LOCK_UN);

            return json_decode($content, true);
        });
    }

    protected function clearTmpData(): void
    {
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
    }

    protected function withTmpFileHandle(string $mode, callable $callback): mixed
    {
        $handle = @fopen($this->tempFilePath, $mode);

        if ($handle === false) {
            return null;
        }

        try {
            return $callback($handle);
        } finally {
            fclose($handle);
        }
    }

    protected function lockHandle($handle, int $operation): void
    {
        if (!flock($handle, $operation)) {
            throw new RuntimeException("Failed to lock the file: {$this->tempFilePath}");
        }
    }
}
