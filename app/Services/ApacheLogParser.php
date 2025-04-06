<?php

namespace App\Services;

use App\Models\ApacheLog;
use Illuminate\Support\Facades\Log;

class ApacheLogParser
{
    // Регулярное выражение для парсинга Apache логов
    private const LOG_PATTERN = '/^(\S+) (\S+) (\S+) \[([\w:\/]+\s[+\-]\d{4})\] "(\S+) (\S+) (\S+)" (\d{3}) (\d+) "([^"]*)" "([^"]*)"/';

    /**
     * Parse Apache log file and store entries in database
     *
     * @param string $filePath Path to the log file
     * @throws \RuntimeException When file is not found or cannot be opened
     * @return void
     */
    public function parseFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Файл логов не найден: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Не удалось открыть файл логов: {$filePath}");
        }

        $batch = [];
        $count = 0;

        while (($line = fgets($handle)) !== false) {
            if (preg_match(self::LOG_PATTERN, $line, $matches)) {
                $logEntry = [
                    'ip_address' => $matches[1],
                    'request_method' => $matches[5],
                    'request_path' => $matches[6],
                    'status_code' => (int)$matches[8],
                    'response_size' => (int)$matches[9],
                    'referer' => $matches[10],
                    'user_agent' => $matches[11],
                    'request_time' => date('Y-m-d H:i:s', strtotime($matches[4]))
                ];

                $batch[] = $logEntry;
                $count++;

                // Сохраняем батчами по 1000 записей для оптимизации
                if ($count >= 1000) {
                    ApacheLog::insert($batch);
                    $batch = [];
                    $count = 0;
                }
            }
        }

        // Сохраняем оставшиеся записи
        if (!empty($batch)) {
            ApacheLog::insert($batch);
        }

        fclose($handle);
    }
} 