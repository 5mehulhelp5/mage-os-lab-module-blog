<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\ViewModel\Post;

use MageOS\Blog\Model\Config;
use MageOS\Blog\ViewModel\Post\SocialShare;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SocialShareTest extends TestCase
{
    #[Test]
    public function returns_empty_array_when_no_networks_configured(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getSocialNetworks')->willReturn([]);

        $links = (new SocialShare($config))->getLinks('Hello', 'https://shop.test/blog/hello');

        self::assertSame([], $links);
    }

    #[Test]
    public function unknown_network_keys_are_filtered_out(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getSocialNetworks')->willReturn(['facebook', 'bogus', 'x']);

        $links = (new SocialShare($config))->getLinks('Hello', 'https://shop.test/blog/hello');

        self::assertCount(2, $links);
        self::assertSame('facebook', $links[0]['key']);
        self::assertSame('x', $links[1]['key']);
    }

    #[Test]
    public function url_and_title_are_rawurlencoded_in_share_url(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getSocialNetworks')->willReturn(['x']);

        $links = (new SocialShare($config))->getLinks('Hello World & Co', 'https://shop.test/blog/a b');

        self::assertStringContainsString(
            'url=' . rawurlencode('https://shop.test/blog/a b'),
            $links[0]['url']
        );
        self::assertStringContainsString(
            'text=' . rawurlencode('Hello World & Co'),
            $links[0]['url']
        );
    }

    #[Test]
    public function links_preserve_config_order(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getSocialNetworks')->willReturn(['reddit', 'linkedin', 'facebook']);

        $links = (new SocialShare($config))->getLinks('T', 'https://shop.test/');

        self::assertSame(['reddit', 'linkedin', 'facebook'], array_column($links, 'key'));
    }
}
