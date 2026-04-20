<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Post;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use MageOS\Blog\Model\Config;

class SocialShare implements ArgumentInterface
{
    /**
     * @var array<string, array{label: string, url: string}>
     */
    private const NETWORK_TEMPLATES = [
        'facebook' => [
            'label' => 'Facebook',
            'url' => 'https://www.facebook.com/sharer/sharer.php?u={url}',
        ],
        'x' => [
            'label' => 'X',
            'url' => 'https://twitter.com/intent/tweet?url={url}&text={title}',
        ],
        'linkedin' => [
            'label' => 'LinkedIn',
            'url' => 'https://www.linkedin.com/sharing/share-offsite/?url={url}',
        ],
        'reddit' => [
            'label' => 'Reddit',
            'url' => 'https://www.reddit.com/submit?url={url}&title={title}',
        ],
        'email' => [
            'label' => 'Email',
            'url' => 'mailto:?subject={title}&body={url}',
        ],
    ];

    public function __construct(private readonly Config $config)
    {
    }

    /**
     * Build share-link descriptors for every configured, known network.
     *
     * @return array<int, array{key: string, label: string, url: string}>
     */
    public function getLinks(string $title, string $url): array
    {
        $active = $this->config->getSocialNetworks();
        $links = [];
        foreach ($active as $network) {
            $key = strtolower(trim($network));
            if (!isset(self::NETWORK_TEMPLATES[$key])) {
                continue;
            }
            $template = self::NETWORK_TEMPLATES[$key];
            $links[] = [
                'key' => $key,
                'label' => $template['label'],
                'url' => strtr($template['url'], [
                    '{url}' => rawurlencode($url),
                    '{title}' => rawurlencode($title),
                ]),
            ];
        }

        return $links;
    }
}
