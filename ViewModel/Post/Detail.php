<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Post;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Controller\Post\View as PostViewController;

class Detail implements ArgumentInterface
{
    public function __construct(
        private readonly Registry $registry,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    public function getPost(): ?PostInterface
    {
        $post = $this->registry->registry(PostViewController::REGISTRY_KEY);

        return $post instanceof PostInterface ? $post : null;
    }

    public function getTitle(): string
    {
        $post = $this->getPost();

        return $post !== null ? (string) $post->getTitle() : '';
    }

    public function getContent(): ?string
    {
        return $this->getPost()?->getContent();
    }

    public function getShortContent(): ?string
    {
        return $this->getPost()?->getShortContent();
    }

    public function getFeaturedImageUrl(): ?string
    {
        $post = $this->getPost();
        if ($post === null || $post->getFeaturedImage() === null || $post->getFeaturedImage() === '') {
            return null;
        }

        return $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA])
            . 'mageos_blog/' . $post->getFeaturedImage();
    }

    public function getFeaturedImageAlt(): string
    {
        $post = $this->getPost();

        return $post === null ? '' : (string) ($post->getFeaturedImageAlt() ?? $post->getTitle());
    }

    public function getFormattedPublishDate(): string
    {
        $post = $this->getPost();
        if ($post === null) {
            return '';
        }
        $date = $post->getPublishDate();
        if ($date === null || $date === '') {
            return '';
        }

        try {
            return (new \DateTimeImmutable($date))->format('F j, Y');
        } catch (\Throwable) {
            return '';
        }
    }

    public function getCanonicalUrl(): string
    {
        $post = $this->getPost();

        return $post === null
            ? ''
            : $this->urlBuilder->getUrl('blog/' . $post->getUrlKey());
    }
}
