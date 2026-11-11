<?php
declare(strict_types=1);

/**
 * Vercel adapter for a small, isolated learning demo.
 * A private Blob contains the PHP session and its SQLite snapshot. Every request
 * downloads a fresh snapshot; conditional ETag writes prevent silent lost updates.
 * MySQL hosting uses ordinary PHP sessions instead, without this adapter.
 */
final class BlobSessionHandler implements SessionHandlerInterface {
    private string $original = '';
    private string $etag = '';
    private string $sqlite = '';
    private string $pathname = '';

    public function open(string $path, string $name): bool { return true; }
    public function close(): bool {
        // Session close runs AFTER write, including exits from 403/405 responses.
        if ($this->sqlite !== '' && is_file($this->sqlite)) unlink($this->sqlite);
        return true;
    }
    public function gc(int $max_lifetime): int|false { return 0; } // Blob TTL handles expiry.
    public function destroy(string $id): bool { return true; } // No logout/reset route in this demo.

    private function request(string $method, string $url, array $headers = [], ?string $body = null): array {
        $curl = curl_init($url);
        $responseHeaders = [];
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . getenv('BLOB_READ_WRITE_TOKEN'), ...$headers],
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$responseHeaders): int {
                if (str_contains($line, ':')) { [$key,$value]=explode(':',$line,2); $responseHeaders[strtolower(trim($key))]=trim($value); }
                return strlen($line);
            },
        ]);
        if ($body !== null) curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        $result = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($result === false) throw new RuntimeException('Durable store connection failed.');
        return [$status, $result, $responseHeaders];
    }

    public function read(string $id): string|false {
        $this->pathname = 'admisia/sessions/' . hash('sha256', $id) . '.json';
        $this->sqlite = tempnam(sys_get_temp_dir(), 'admisia-');
        putenv('SQLITE_PATH=' . $this->sqlite);
        $store = getenv('BLOB_STORE_ID');
        if (!$store || !preg_match('/^[a-zA-Z0-9]+$/', $store)) throw new RuntimeException('Missing private store configuration.');
        [$status,$body,$headers] = $this->request('GET', 'https://' . strtolower($store) . '.private.blob.vercel-storage.com/' . $this->pathname . '?cache=0');
        if ($status === 404) return '';
        if ($status !== 200) throw new RuntimeException('Durable store read failed: ' . $status);
        $this->etag = $headers['etag'] ?? '';
        if ($this->etag === '') throw new RuntimeException('Durable store did not provide an ETag.');
        $this->original = $body;
        $record = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        $database = base64_decode($record['database'], true);
        $session = base64_decode($record['session'], true);
        if ($database === false || $session === false) throw new RuntimeException('Invalid durable snapshot.');
        file_put_contents($this->sqlite, $database);
        return $session;
    }

    public function write(string $id, string $data): bool {
        $body = json_encode(['session'=>base64_encode($data), 'database'=>base64_encode((string)file_get_contents($this->sqlite))], JSON_THROW_ON_ERROR);
        if ($body === $this->original) return true;
        $headers = [
            'x-api-version: 13', 'x-vercel-blob-access: private', 'x-add-random-suffix: 0',
            'x-content-type: application/json', 'Content-Type: application/json', 'x-cache-control-max-age: 0',
            'x-ttl-days: 7', 'x-allow-overwrite: ' . ($this->etag !== '' ? '1' : '0'),
        ];
        if ($this->etag !== '') $headers[] = 'x-if-match: ' . $this->etag;
        [$status,$result] = $this->request('PUT', 'https://blob.vercel-storage.com/?pathname=' . rawurlencode($this->pathname), $headers, $body);
        if (in_array($status,[409,412],true)) throw new RuntimeException('Concurrent session update; reload and retry.',409);
        if ($status < 200 || $status >= 300) throw new RuntimeException('Durable store write failed: ' . $status . ' ' . substr($result,0,250));
        $this->original = $body;
        return true;
    }
}
