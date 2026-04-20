<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\UrlResolver;

use Magento\UrlRewriteGraphQl\Model\Resolver\UrlRewrite\CustomUrlLocatorInterface;

/**
 * Deferral stub: registers the blog_post entity type with UrlRewriteGraphQl's
 * CustomUrlLocator composite. Blog post URLs have url_rewrite rows (populated
 * by Plugin\Repository\Post\UrlRewritePlugin on save), so the default URL
 * finder resolves them; this locator returns null to defer.
 */
class BlogPost implements CustomUrlLocatorInterface
{
    public function locateUrl($urlKey): ?string
    {
        return null;
    }
}
