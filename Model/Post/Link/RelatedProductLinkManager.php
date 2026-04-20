<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Post\Link;

use MageOS\Blog\Model\Link\AbstractPivotLinkManager;

class RelatedProductLinkManager extends AbstractPivotLinkManager
{
    protected function pivotTable(): string
    {
        return 'mageos_blog_post_related_product';
    }

    protected function leftColumn(): string
    {
        return 'post_id';
    }

    protected function rightColumn(): string
    {
        return 'product_id';
    }
}
