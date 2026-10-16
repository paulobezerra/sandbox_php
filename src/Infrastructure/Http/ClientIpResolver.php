<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use InvalidArgumentException;

final readonly class ClientIpResolver
{
    /** @var list<array{network: string, prefix: int}> */
    private array $trustedNetworks;

    /** @param list<string> $trustedProxies */
    public function __construct(array $trustedProxies)
    {
        $networks = [];
        foreach ($trustedProxies as $trustedProxy) {
            $network = self::parseNetwork($trustedProxy);
            if ($network === null) {
                throw new InvalidArgumentException('Proxy confiável contém um IP ou CIDR inválido.');
            }
            $networks[] = $network;
        }
        $this->trustedNetworks = $networks;
    }

    public static function isValidNetwork(string $network): bool
    {
        return self::parseNetwork($network) !== null;
    }

    public function resolve(string $remoteAddress, string $forwardedFor): string
    {
        $remotePacked = inet_pton($remoteAddress);
        if ($remotePacked === false) {
            return 'unknown';
        }

        if (!$this->isTrusted($remotePacked)) {
            return inet_ntop($remotePacked) ?: 'unknown';
        }

        $forwardedAddresses = trim($forwardedFor) === '' ? [] : explode(',', $forwardedFor);
        for ($index = count($forwardedAddresses) - 1; $index >= 0; $index--) {
            $address = trim($forwardedAddresses[$index]);
            $packed = inet_pton($address);
            if ($packed === false) {
                return inet_ntop($remotePacked) ?: 'unknown';
            }

            if (!$this->isTrusted($packed)) {
                return inet_ntop($packed) ?: 'unknown';
            }
        }

        return inet_ntop($remotePacked) ?: 'unknown';
    }

    /** @return array{network: string, prefix: int}|null */
    private static function parseNetwork(string $value): ?array
    {
        $parts = explode('/', trim($value));
        if (count($parts) > 2) {
            return null;
        }

        $network = inet_pton($parts[0]);
        if ($network === false) {
            return null;
        }

        $maxPrefix = strlen($network) * 8;
        $prefix = count($parts) === 1 ? $maxPrefix : filter_var($parts[1], FILTER_VALIDATE_INT);
        if ($prefix === false || $prefix < 0 || $prefix > $maxPrefix) {
            return null;
        }

        return ['network' => $network, 'prefix' => $prefix];
    }

    private function isTrusted(string $address): bool
    {
        foreach ($this->trustedNetworks as $trustedNetwork) {
            if (strlen($address) !== strlen($trustedNetwork['network'])) {
                continue;
            }

            $wholeBytes = intdiv($trustedNetwork['prefix'], 8);
            $remainingBits = $trustedNetwork['prefix'] % 8;
            if (substr($address, 0, $wholeBytes) !== substr($trustedNetwork['network'], 0, $wholeBytes)) {
                continue;
            }

            if ($remainingBits === 0) {
                return true;
            }

            $mask = (0xff << (8 - $remainingBits)) & 0xff;
            if ((ord($address[$wholeBytes]) & $mask) === (ord($trustedNetwork['network'][$wholeBytes]) & $mask)) {
                return true;
            }
        }

        return false;
    }
}
