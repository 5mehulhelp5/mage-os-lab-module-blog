<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\RelatedPostsProvider;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\PostRepositoryInterface;

class ManualRelationLoader
{
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly PostRepositoryInterface $repository
    ) {
    }

    /**
     * @return PostInterface[]
     */
    public function load(PostInterface $post, int $limit): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName('mageos_blog_post_related_post'), ['related_post_id'])
            ->where('post_id = ?', (int) $post->getPostId())
            ->order('position ASC')
            ->limit($limit);

        $ids = array_map('intval', $connection->fetchCol($select));
        $items = [];
        foreach ($ids as $id) {
            try {
                $items[] = $this->repository->getById($id);
            } catch (NoSuchEntityException) {
                // skip deleted
            }
        }
        return $items;
    }
}
