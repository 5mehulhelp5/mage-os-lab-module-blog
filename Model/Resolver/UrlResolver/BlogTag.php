<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\UrlResolver;

use Magento\UrlRewriteGraphQl\Model\Resolver\UrlRewrite\CustomUrlLocatorInterface;

/**
 * Deferral stub: registers the blog_tag entity type with UrlRewriteGraphQl's
 * CustomUrlLocator composite. Blog tag URLs are url_rewrite-backed, so this
 * locator returns null to let the default URL finder resolve them.
 */
class BlogTag implements CustomUrlLocatorInterface
{
    public function locateUrl($urlKey): ?string
    {
        return null;
    }
}
