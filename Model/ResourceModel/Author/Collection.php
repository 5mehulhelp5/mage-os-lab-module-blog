<?php
declare(strict_types=1);

namespace MageOS\Blog\Model\ResourceModel\Author;

use MageOS\Blog\Model\Author;
use MageOS\Blog\Model\ResourceModel\Author as AuthorResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'author_id';

    protected function _construct(): void
    {
        $this->_init(Author::class, AuthorResource::class);
    }
}
