<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Infrastructure\Http\ClientIpResolver;
use PHPUnit\Framework\TestCase;

final class ClientIpResolverTest extends TestCase
{
    public function testIgnoresForwardedAddressFromUntrustedPeer(): void
    {
        $resolver = new ClientIpResolver(['10.0.0.0/8']);

        self::assertSame('198.51.100.10', $resolver->resolve('198.51.100.10', '203.0.113.9'));
    }

    public function testUsesFirstUntrustedAddressFromRightToLeftTrustedProxyChain(): void
    {
        $resolver = new ClientIpResolver(['10.0.0.0/8']);

        self::assertSame(
            '198.51.100.20',
            $resolver->resolve('10.0.0.3', '203.0.113.99, 198.51.100.20, 10.0.0.2')
        );
    }

    public function testFallsBackToProxyAddressWhenForwardedChainIsMalformed(): void
    {
        $resolver = new ClientIpResolver(['10.0.0.0/8']);

        self::assertSame('10.0.0.3', $resolver->resolve('10.0.0.3', '198.51.100.20, invalid'));
    }

    public function testMatchesIpv6TrustedProxyNetworks(): void
    {
        $resolver = new ClientIpResolver(['2001:db8:abcd::/48']);

        self::assertSame(
            '2001:4860:4860::8888',
            $resolver->resolve('2001:db8:abcd::1', '2001:4860:4860::8888')
        );
    }
}
