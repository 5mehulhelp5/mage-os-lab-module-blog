<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\RelatedPostsProviderInterface;
use MageOS\Blog\Model\RelatedPostsProvider\AlgorithmicLoader;
use MageOS\Blog\Model\RelatedPostsProvider\ManualRelationLoader;

class RelatedPostsProvider implements RelatedPostsProviderInterface
{
    public function __construct(
        private readonly ManualRelationLoader $manualLoader,
        private readonly AlgorithmicLoader $algorithmicLoader
    ) {
    }

    /**
     * @return PostInterface[]
     */
    public function forPost(PostInterface $post, int $limit = 5): array
    {
        if ($limit <= 0) {
            return [];
        }

        $manual = $this->manualLoader->load($post, $limit);
        if (\count($manual) >= $limit) {
            return \array_slice($manual, 0, $limit);
        }

        $excluded = array_merge(
            [(int) $post->getPostId()],
            array_map(static fn (PostInterface $p): int => (int) $p->getPostId(), $manual)
        );

        $needed = $limit - \count($manual);
        $algorithmic = $this->algorithmicLoader->load($post, $needed, $excluded);

        return \array_slice(array_merge($manual, $algorithmic), 0, $limit);
    }
}
