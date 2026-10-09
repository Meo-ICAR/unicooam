<?php

namespace App\Services\SharePoint;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SharePointUploader
{
    /** Oltre questa soglia Graph richiede una upload session. */
    private const SIMPLE_UPLOAD_LIMIT = 4 * 1024 * 1024;

    /** Multiplo di 320 KiB, come richiesto da Graph per i blocchi. */
    private const CHUNK_SIZE = 10 * 327680;

    public function isConfigured(): bool
    {
        return filled(config('services.sharepoint.drive_id'))
            && filled(config('services.sharepoint.tenant_id'))
            && filled(config('services.sharepoint.client_id'))
            && filled(config('services.sharepoint.client_secret'));
    }

    /**
     * Carica un file locale nel drive. Le cartelle mancanti vengono create da Graph.
     *
     * @param  list<string>  $folders  Cartelle sotto la radice del drive
     * @return array{id: string, etag: ?string, web_url: ?string, drive_id: string}
     */
    public function upload(string $absolutePath, array $folders, string $fileName): array
    {
        if (! is_file($absolutePath)) {
            throw new RuntimeException("File locale non trovato: {$absolutePath}");
        }

        $remote = $this->remotePath($folders, $fileName);
        $item = filesize($absolutePath) <= self::SIMPLE_UPLOAD_LIMIT
            ? $this->simpleUpload($absolutePath, $remote)
            : $this->sessionUpload($absolutePath, $remote);

        return [
            'id' => (string) $item['id'],
            'etag' => $item['eTag'] ?? null,
            'web_url' => $item['webUrl'] ?? null,
            'drive_id' => $this->driveId(),
        ];
    }

    /** Rimuove i caratteri che SharePoint non accetta nei nomi di file e cartelle. */
    public function sanitize(string $name): string
    {
        $clean = trim((string) preg_replace('/[\\\\\/:*?"<>|#%]+/', '-', $name), " .\t\n\r");

        return $clean === '' ? '_' : mb_substr($clean, 0, 120);
    }

    /** @param list<string> $folders */
    private function remotePath(array $folders, string $fileName): string
    {
        $segments = array_map(fn (string $s) => rawurlencode($this->sanitize($s)), [...$folders, $fileName]);

        return implode('/', $segments);
    }

    /** @return array<string, mixed> */
    private function simpleUpload(string $path, string $remote): array
    {
        $response = Http::withToken($this->token())
            ->withBody((string) file_get_contents($path), 'application/octet-stream')
            ->timeout(120)
            ->put($this->itemUrl($remote).':/content?@microsoft.graph.conflictBehavior=rename');

        if ($response->failed()) {
            throw new RuntimeException("Upload SharePoint fallito (HTTP {$response->status()}): ".$response->body());
        }

        return $response->json();
    }

    /** @return array<string, mixed> */
    private function sessionUpload(string $path, string $remote): array
    {
        $session = Http::withToken($this->token())
            ->post($this->itemUrl($remote).':/createUploadSession', [
                'item' => ['@microsoft.graph.conflictBehavior' => 'rename'],
            ]);

        if ($session->failed()) {
            throw new RuntimeException("Upload session fallita (HTTP {$session->status()}): ".$session->body());
        }

        $uploadUrl = (string) $session->json('uploadUrl');
        $size = filesize($path);
        $handle = fopen($path, 'rb');
        $item = [];

        try {
            for ($offset = 0; $offset < $size; $offset += self::CHUNK_SIZE) {
                $chunk = (string) fread($handle, self::CHUNK_SIZE);
                $end = $offset + strlen($chunk) - 1;

                // L'uploadUrl è già autenticato: non va inviato il bearer token.
                $response = Http::withBody($chunk, 'application/octet-stream')
                    ->withHeaders(['Content-Range' => "bytes {$offset}-{$end}/{$size}"])
                    ->timeout(120)
                    ->put($uploadUrl);

                if ($response->failed()) {
                    throw new RuntimeException("Upload blocco fallito (HTTP {$response->status()}): ".$response->body());
                }

                $item = $response->json() ?? [];
            }
        } finally {
            fclose($handle);
        }

        if (empty($item['id'])) {
            throw new RuntimeException('Upload session terminata senza elemento creato.');
        }

        return $item;
    }

    private function itemUrl(string $remote): string
    {
        return "https://graph.microsoft.com/v1.0/drives/{$this->driveId()}/root:/{$remote}";
    }

    private function driveId(): string
    {
        return (string) config('services.sharepoint.drive_id');
    }

    private function token(): string
    {
        return Cache::remember('sharepoint.upload_token', 3000, function (): string {
            $tenantId = config('services.sharepoint.tenant_id');
            $response = Http::asForm()->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
                'client_id' => config('services.sharepoint.client_id'),
                'client_secret' => config('services.sharepoint.client_secret'),
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ]);

            if ($response->failed()) {
                throw new RuntimeException("Errore token SharePoint (HTTP {$response->status()})");
            }

            return (string) $response->json('access_token');
        });
    }
}
