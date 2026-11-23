<?php

declare(strict_types=1);

namespace App\Modules\Documents;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Encrypted storage for case documents (PROJECT1_PLAN.md §9.3, §8.7).
 *
 * Each file gets its own random data key (XChaCha20-Poly1305, libsodium). The data key is stored wrapped by the
 * document master key, which is separate from APP_KEY and kept with the backup key. The document id is bound as
 * associated data, so a file or key copied onto another document row fails to decrypt.
 */
final class DocumentVault
{
    private const VERSION = 'v1';

    /** @return array{storage_key: string, wrapped_key: string} */
    public function store(string $documentId, string $contents): array
    {
        $dataKey = sodium_crypto_aead_xchacha20poly1305_ietf_keygen();
        $storageKey = 'documents/'.substr($documentId, 0, 2).'/'.$documentId.'.bin';
        $this->disk()->put($storageKey, $this->seal($contents, $documentId, $dataKey));
        $wrapped = self::VERSION.':'.base64_encode($this->seal($dataKey, 'dk:'.$documentId, $this->masterKey()));
        sodium_memzero($dataKey);

        return ['storage_key' => $storageKey, 'wrapped_key' => $wrapped];
    }

    public function read(string $documentId, string $storageKey, string $wrappedKey): string
    {
        [$version, $payload] = explode(':', $wrappedKey, 2) + [null, null];
        if ($version !== self::VERSION || $payload === null) {
            throw new RuntimeException('unknown document key version');
        }
        $dataKey = $this->open((string) base64_decode($payload, true), 'dk:'.$documentId, $this->masterKey());
        $blob = $this->disk()->get($storageKey) ?? throw new RuntimeException('document file missing');
        $plain = $this->open($blob, $documentId, $dataKey);
        sodium_memzero($dataKey);

        return $plain;
    }

    public function delete(string $storageKey): void
    {
        $this->disk()->delete($storageKey);
    }

    private function seal(string $plain, string $aad, string $key): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);

        return $nonce.sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plain, $aad, $nonce, $key);
    }

    private function open(string $sealed, string $aad, string $key): string
    {
        $nonce = substr($sealed, 0, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $plain = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(substr($sealed, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES), $aad, $nonce, $key);
        if ($plain === false) {
            throw new RuntimeException('document decryption failed');
        }

        return $plain;
    }

    private function masterKey(): string
    {
        $key = base64_decode((string) config('dentlegal.documents.master_key'), true);
        if ($key === false || strlen($key) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) {
            throw new RuntimeException('DOCUMENT_MASTER_KEY must be 32 bytes, base64-encoded');
        }

        return $key;
    }

    private function disk(): Filesystem
    {
        return Storage::disk((string) config('dentlegal.documents.disk'));
    }
}
