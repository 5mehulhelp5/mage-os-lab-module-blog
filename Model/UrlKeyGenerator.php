<?php
declare(strict_types=1);

namespace MageOS\Blog\Model;

use MageOS\Blog\Api\UrlKeyGeneratorInterface;
use MageOS\Blog\Model\UrlKeyGenerator\CollisionChecker;

final class UrlKeyGenerator implements UrlKeyGeneratorInterface
{
    public function __construct(private readonly CollisionChecker $checker)
    {
    }

    public function generate(string $title, string $entityType, ?int $storeId = null): string
    {
        $base = $this->normalize($title);
        if ($base === '' || \in_array($base, self::RESERVED, true)) {
            throw new \InvalidArgumentException("Cannot generate a URL key from '{$title}'.");
        }

        $candidate = $base;
        $suffix = 1;
        while ($this->checker->isTaken($candidate, $entityType, $storeId)) {
            $suffix++;
            $candidate = "{$base}-{$suffix}";
        }

        return $candidate;
    }

    public function validate(string $urlKey, string $entityType, ?int $storeId, ?int $excludingEntityId = null): void
    {
        if (\in_array($urlKey, self::RESERVED, true)) {
            throw new \InvalidArgumentException("URL key '{$urlKey}' is reserved.");
        }
        if ($this->checker->isTaken($urlKey, $entityType, $storeId, $excludingEntityId)) {
            throw new \InvalidArgumentException("URL key '{$urlKey}' is already in use.");
        }
    }

    private function normalize(string $title): string
    {
        $ascii = \iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title) ?: $title;
        $lower = \strtolower($ascii);
        $slug = \preg_replace('/[^a-z0-9]+/', '-', $lower) ?? '';
        return \trim($slug, '-');
    }
}
