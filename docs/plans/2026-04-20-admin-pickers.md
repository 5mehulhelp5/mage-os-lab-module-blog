# Admin Pickers + Author Avatar Bug Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the post-form taxonomy/related text-inputs with real pickers, wire a hierarchical parent-category picker on the category form, and fix the author avatar upload crash.

**Architecture:** Re-use Magento's stock `Magento_Ui/js/form/element/ui-select` + hierarchical `optgroup` option sources for three of the post-form fields and the category-form `parent_id`. Wire `insertListing` against `product_listing` for related-products. No custom JS, no custom tree widget.

**Tech Stack:** Magento 2.4.x UI components, `ArgumentInterface`, `OptionSourceInterface`, `insertListing`, PostRepositoryInterface, CategoryRepositoryInterface, SearchCriteriaBuilder. PHPStan L8, PHPCS, PHPUnit.

**Design doc:** `docs/plans/2026-04-20-admin-pickers-design.md`.

---

## Task 1 — Fix author avatar upload `xsi:type="string"` bug

**Files:**
- Modify: `view/adminhtml/ui_component/mageos_blog_author_form.xml:106-110`

**Step 1: Load the broken region and confirm the exact text.**

Run: `grep -n 'uploaderConfig\|avatar\|xsi:type="string"' view/adminhtml/ui_component/mageos_blog_author_form.xml`

Expected: shows the nested `<param name="url" xsi:type="url" path="mageos_blog/author/uploadImage">` containing an inner `<param name="type" xsi:type="string">avatar</param>`. This is the exact structure the post-form fix already replaced.

**Step 2: Replace the nested param block.**

In `view/adminhtml/ui_component/mageos_blog_author_form.xml`, find:

```xml
<uploaderConfig>
    <param name="url" xsi:type="url" path="mageos_blog/author/uploadImage">
        <param name="type" xsi:type="string">avatar</param>
    </param>
</uploaderConfig>
```

Replace with:

```xml
<uploaderConfig>
    <param name="url" xsi:type="url" path="mageos_blog/author/uploadImage/type/avatar"/>
</uploaderConfig>
```

The controller reads `type` via `$this->getRequest()->getParam('type')`, which also picks up Magento's URL-path positional parameters — so the value is still delivered.

**Step 3: Validate the XML and sniff.**

Run: `cd /Users/david/Herd/mage-os-typesense && warden env exec -T php-fpm xmllint --noout /var/www/html/app/code/MageOS/Blog/view/adminhtml/ui_component/mageos_blog_author_form.xml && echo ok`

Expected: `ok`.

Run: `vendor/bin/phpcs --standard=phpcs.xml.dist --error-severity=1 --warning-severity=0 view/adminhtml/ui_component/mageos_blog_author_form.xml`

Expected: no error output.

**Step 4: Live smoke — author edit no longer throws.**

Flush caches first:

```bash
cd /Users/david/Herd/mage-os-typesense && warden env exec -T php-fpm bin/magento cache:flush && warden env exec -T varnish varnishadm "ban req.url ~ ."
```

Then in the browser (already authenticated), visit
`https://app.mage-os-typesense.test/backend/mageos_blog/author/edit/author_id/1/` and confirm the form renders without the `LocalizedException`.

**Step 5: Commit.**

```bash
git add view/adminhtml/ui_component/mageos_blog_author_form.xml
git commit -m "fix(admin): author avatar upload — drop nested xsi:type=\"string\" param"
```

---

## Task 2 — Hierarchical parent-category picker on category form

This task has logic (descendant filtering to prevent cycles) so we build it TDD.

### Task 2.1 — Write failing unit test for `ParentCategory\Options`

**Files:**
- Create: `Test/Unit/Ui/Component/Form/ParentCategory/OptionsTest.php`

**Step 1: Write the failing test.**

```php
<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Ui\Component\Form\ParentCategory;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\App\RequestInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Ui\Component\Form\ParentCategory\Options;
use PHPUnit\Framework\TestCase;

final class OptionsTest extends TestCase
{
    public function test_includes_root_option_first_then_all_categories_in_optgroup_shape(): void
    {
        $parent = $this->makeCategory(1, 'News', null);
        $child = $this->makeCategory(2, 'Tech', 1);
        $options = $this->build([$parent, $child], currentCategoryId: 0);

        $result = $options->toOptionArray();

        $this->assertSame('', $result[0]['value']);
        $this->assertSame('— None (Root) —', (string) $result[0]['label']);
        $this->assertSame(1, $result[1]['value']);
        $this->assertSame('News', $result[1]['label']);
        $this->assertSame(2, $result[1]['optgroup'][0]['value']);
        $this->assertSame('Tech', $result[1]['optgroup'][0]['label']);
    }

    public function test_excludes_current_category_and_its_descendants(): void
    {
        $parent = $this->makeCategory(1, 'News', null);
        $child = $this->makeCategory(2, 'Tech', 1);
        $grand = $this->makeCategory(3, 'Magento', 2);
        $sibling = $this->makeCategory(4, 'Guides', null);
        $options = $this->build([$parent, $child, $grand, $sibling], currentCategoryId: 1);

        $result = $options->toOptionArray();

        $values = $this->flattenValues($result);
        $this->assertNotContains(1, $values); // self
        $this->assertNotContains(2, $values); // descendant
        $this->assertNotContains(3, $values); // deep descendant
        $this->assertContains(4, $values);    // sibling kept
    }

    /**
     * @param CategoryInterface[] $items
     */
    private function build(array $items, int $currentCategoryId): Options
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->with('category_id')->willReturn($currentCategoryId);

        $searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->method('create')->willReturn($this->createMock(SearchCriteria::class));

        $results = $this->createMock(SearchResults::class);
        $results->method('getItems')->willReturn($items);

        $repo = $this->createMock(CategoryRepositoryInterface::class);
        $repo->method('getList')->willReturn($results);

        return new Options($repo, $searchCriteriaBuilder, $request);
    }

    private function makeCategory(int $id, string $title, ?int $parentId): CategoryInterface
    {
        $cat = $this->createMock(CategoryInterface::class);
        $cat->method('getCategoryId')->willReturn($id);
        $cat->method('getTitle')->willReturn($title);
        $cat->method('getParentId')->willReturn($parentId);
        return $cat;
    }

    /**
     * @param array<int, array<string, mixed>> $options
     * @return int[]
     */
    private function flattenValues(array $options): array
    {
        $ids = [];
        foreach ($options as $opt) {
            if (isset($opt['value']) && \is_int($opt['value'])) {
                $ids[] = $opt['value'];
            }
            if (isset($opt['optgroup']) && \is_array($opt['optgroup'])) {
                $ids = array_merge($ids, $this->flattenValues($opt['optgroup']));
            }
        }
        return $ids;
    }
}
```

**Step 2: Run the failing test.**

Run: `vendor/bin/phpunit --testsuite unit --filter OptionsTest Test/Unit/Ui/Component/Form/ParentCategory/OptionsTest.php`

Expected: FAIL with `Class "MageOS\Blog\Ui\Component\Form\ParentCategory\Options" not found`.

### Task 2.2 — Implement `ParentCategory\Options`

**Files:**
- Create: `Ui/Component/Form/ParentCategory/Options.php`

**Step 1: Write the class.**

```php
<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\Component\Form\ParentCategory;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Phrase;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterface;

/**
 * Options for the category-edit form's `parent_id` picker.
 *
 * Returns the full category list in Magento's hierarchical `optgroup` format,
 * prepended with a "root" sentinel, with the currently-edited category and
 * all of its descendants filtered out to prevent cycles.
 */
class Options implements OptionSourceInterface
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly RequestInterface $request,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toOptionArray(): array
    {
        $currentId = (int) $this->request->getParam('category_id');

        $results = $this->categoryRepository->getList($this->searchCriteriaBuilder->create());
        /** @var CategoryInterface[] $categories */
        $categories = $results->getItems();

        $excluded = $currentId > 0
            ? $this->collectDescendantIds($categories, $currentId)
            : [];
        $excluded[] = $currentId;

        /** @var array<int, CategoryInterface[]> $byParent */
        $byParent = [];
        foreach ($categories as $cat) {
            $id = (int) $cat->getCategoryId();
            if (\in_array($id, $excluded, true)) {
                continue;
            }
            $parentId = $cat->getParentId() === null ? 0 : (int) $cat->getParentId();
            $byParent[$parentId][] = $cat;
        }

        $tree = [];
        foreach ($byParent[0] ?? [] as $root) {
            $tree[] = $this->branch($root, $byParent);
        }

        return array_merge(
            [['value' => '', 'label' => new Phrase('— None (Root) —')]],
            $tree,
        );
    }

    /**
     * @param array<int, CategoryInterface[]> $byParent
     *
     * @return array<string, mixed>
     */
    private function branch(CategoryInterface $category, array $byParent): array
    {
        $id = (int) $category->getCategoryId();
        $node = [
            'value' => $id,
            'label' => (string) $category->getTitle(),
        ];
        if (!empty($byParent[$id])) {
            $node['optgroup'] = array_map(
                fn (CategoryInterface $child) => $this->branch($child, $byParent),
                $byParent[$id],
            );
        }
        return $node;
    }

    /**
     * @param CategoryInterface[] $categories
     *
     * @return int[]
     */
    private function collectDescendantIds(array $categories, int $rootId): array
    {
        $childrenByParent = [];
        foreach ($categories as $cat) {
            $parentId = $cat->getParentId() === null ? 0 : (int) $cat->getParentId();
            $childrenByParent[$parentId][] = (int) $cat->getCategoryId();
        }

        $descendants = [];
        $stack = [$rootId];
        while ($stack !== []) {
            $current = array_pop($stack);
            foreach ($childrenByParent[$current] ?? [] as $childId) {
                if (\in_array($childId, $descendants, true)) {
                    continue;
                }
                $descendants[] = $childId;
                $stack[] = $childId;
            }
        }

        return $descendants;
    }
}
```

**Step 2: Run the test — it passes now.**

Run: `vendor/bin/phpunit --testsuite unit --filter OptionsTest`

Expected: PASS, 2 tests.

**Step 3: PHPStan + PHPCS.**

Run: `vendor/bin/phpstan analyse --memory-limit=1G --no-progress Ui/Component/Form/ParentCategory Test/Unit/Ui/Component/Form/ParentCategory`

Expected: `[OK] No errors`.

Run: `vendor/bin/phpcs --standard=phpcs.xml.dist --error-severity=1 --warning-severity=0 Ui/Component/Form/ParentCategory Test/Unit/Ui/Component/Form/ParentCategory`

Expected: no error output.

### Task 2.3 — Wire `Options` into the category form

**Files:**
- Modify: `view/adminhtml/ui_component/mageos_blog_category_form.xml:78-85`

**Step 1: Replace the `parent_id` field.**

Replace

```xml
<field name="parent_id" formElement="input" sortOrder="40">
    <settings>
        <dataType>number</dataType>
        <label translate="true">Parent ID</label>
        <dataScope>parent_id</dataScope>
        <notice translate="true">Root category: leave blank. Tree chooser lands in a later task.</notice>
    </settings>
</field>
```

With

```xml
<field name="parent_id" formElement="select" sortOrder="40">
    <settings>
        <dataType>text</dataType>
        <label translate="true">Parent Category</label>
        <dataScope>parent_id</dataScope>
        <notice translate="true">Pick a parent category or leave as "None (Root)" to create a top-level category.</notice>
    </settings>
    <formElements>
        <select>
            <settings>
                <options class="MageOS\Blog\Ui\Component\Form\ParentCategory\Options"/>
            </settings>
        </select>
    </formElements>
</field>
```

**Step 2: XML lint.**

Run: `cd /Users/david/Herd/mage-os-typesense && warden env exec -T php-fpm xmllint --noout /var/www/html/app/code/MageOS/Blog/view/adminhtml/ui_component/mageos_blog_category_form.xml && echo ok`

Expected: `ok`.

**Step 3: Live smoke.**

```bash
cd /Users/david/Herd/mage-os-typesense && warden env exec -T php-fpm bin/magento cache:flush && warden env exec -T varnish varnishadm "ban req.url ~ ."
```

Visit `https://app.mage-os-typesense.test/backend/mageos_blog/category/edit/category_id/1/`. The `Parent Category` dropdown should show `— None (Root) —`, `Guides`, and (indented under Guides if any exist) any sub-categories. Selecting a different parent and saving should round-trip.

**Step 4: Commit.**

```bash
git add Ui/Component/Form/ParentCategory/Options.php Test/Unit/Ui/Component/Form/ParentCategory/OptionsTest.php view/adminhtml/ui_component/mageos_blog_category_form.xml
git commit -m "feat(admin): hierarchical parent-category picker on category form"
```

---

## Task 3 — Category / Tag / Related-post pickers on post form

Three parallel sources, all thin. Less TDD value per class (they're direct repository-to-array mappings with no branching logic); one integration-level cover via the live smoke is enough.

### Task 3.1 — `Categories\Options` source

**Files:**
- Create: `Ui/Component/Form/Categories/Options.php`

**Step 1: Write the class.**

```php
<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\Component\Form\Categories;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterface;

/**
 * Options for the post-edit form's `category_ids` picker. Hierarchical optgroup
 * format — ui-select renders this with visible nesting.
 */
class Options implements OptionSourceInterface
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toOptionArray(): array
    {
        /** @var CategoryInterface[] $categories */
        $categories = $this->categoryRepository
            ->getList($this->searchCriteriaBuilder->create())
            ->getItems();

        /** @var array<int, CategoryInterface[]> $byParent */
        $byParent = [];
        foreach ($categories as $cat) {
            $parentId = $cat->getParentId() === null ? 0 : (int) $cat->getParentId();
            $byParent[$parentId][] = $cat;
        }

        $tree = [];
        foreach ($byParent[0] ?? [] as $root) {
            $tree[] = $this->branch($root, $byParent);
        }
        return $tree;
    }

    /**
     * @param array<int, CategoryInterface[]> $byParent
     *
     * @return array<string, mixed>
     */
    private function branch(CategoryInterface $category, array $byParent): array
    {
        $id = (int) $category->getCategoryId();
        $node = [
            'value' => $id,
            'label' => (string) $category->getTitle(),
        ];
        if (!empty($byParent[$id])) {
            $node['optgroup'] = array_map(
                fn (CategoryInterface $child) => $this->branch($child, $byParent),
                $byParent[$id],
            );
        }
        return $node;
    }
}
```

**Step 2: Verify.**

Run: `vendor/bin/phpstan analyse --memory-limit=1G --no-progress Ui/Component/Form/Categories`

Expected: `[OK] No errors`.

### Task 3.2 — `Tags\Options` source

**Files:**
- Create: `Ui/Component/Form/Tags/Options.php`

**Step 1: Write the class.**

```php
<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\Component\Form\Tags;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Api\TagRepositoryInterface;

class Options implements OptionSourceInterface
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toOptionArray(): array
    {
        $tags = $this->tagRepository
            ->getList($this->searchCriteriaBuilder->create())
            ->getItems();

        $options = [];
        /** @var TagInterface $tag */
        foreach ($tags as $tag) {
            $options[] = [
                'value' => (int) $tag->getTagId(),
                'label' => (string) $tag->getTitle(),
            ];
        }
        return $options;
    }
}
```

**Step 2: Verify.**

Run: `vendor/bin/phpstan analyse --memory-limit=1G --no-progress Ui/Component/Form/Tags`

Expected: `[OK] No errors`.

### Task 3.3 — `RelatedPosts\Options` source

**Files:**
- Create: `Ui/Component/Form/RelatedPosts/Options.php`

**Step 1: Write the class.**

```php
<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\Component\Form\RelatedPosts;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;

/**
 * Options for the post-edit form's `related_post_ids` picker. Excludes the
 * currently-edited post so a post can't relate to itself. Caps at 500 — paginated
 * search is v1.1 work.
 */
class Options implements OptionSourceInterface
{
    private const HARD_LIMIT = 500;

    public function __construct(
        private readonly PostRepositoryInterface $postRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly RequestInterface $request,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toOptionArray(): array
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter(PostInterface::STATUS, BlogPostStatus::Published->value)
            ->setPageSize(self::HARD_LIMIT)
            ->create();

        $posts = $this->postRepository->getList($criteria)->getItems();

        $currentId = (int) $this->request->getParam('post_id');
        $options = [];
        /** @var PostInterface $post */
        foreach ($posts as $post) {
            $id = (int) $post->getPostId();
            if ($id === $currentId) {
                continue;
            }
            $options[] = [
                'value' => $id,
                'label' => (string) $post->getTitle(),
            ];
        }
        return $options;
    }
}
```

**Step 2: Verify.**

Run: `vendor/bin/phpstan analyse --memory-limit=1G --no-progress Ui/Component/Form/RelatedPosts`

Expected: `[OK] No errors`.

### Task 3.4 — Rewire the three fields on `mageos_blog_post_form.xml`

**Files:**
- Modify: `view/adminhtml/ui_component/mageos_blog_post_form.xml:261-276` (taxonomy) and `283-290` (related posts)

**Step 1: Replace the `category_ids` field.**

Replace the `<field name="category_ids" formElement="input" ...>` block in the `taxonomy` fieldset with:

```xml
<field name="category_ids" formElement="select" sortOrder="10">
    <argument name="data" xsi:type="array">
        <item name="config" xsi:type="array">
            <item name="filterOptions" xsi:type="boolean">true</item>
            <item name="chipsEnabled" xsi:type="boolean">true</item>
            <item name="disableLabel" xsi:type="boolean">true</item>
            <item name="levelsVisibility" xsi:type="string">1</item>
            <item name="elementTmpl" xsi:type="string">ui/grid/filters/elements/ui-select</item>
        </item>
    </argument>
    <settings>
        <dataType>text</dataType>
        <label translate="true">Categories</label>
        <dataScope>category_ids</dataScope>
    </settings>
    <formElements>
        <select>
            <settings>
                <options class="MageOS\Blog\Ui\Component\Form\Categories\Options"/>
                <multiple>true</multiple>
            </settings>
        </select>
    </formElements>
</field>
```

**Step 2: Replace the `tag_ids` field.**

Replace with:

```xml
<field name="tag_ids" formElement="select" sortOrder="20">
    <argument name="data" xsi:type="array">
        <item name="config" xsi:type="array">
            <item name="filterOptions" xsi:type="boolean">true</item>
            <item name="chipsEnabled" xsi:type="boolean">true</item>
            <item name="disableLabel" xsi:type="boolean">true</item>
            <item name="elementTmpl" xsi:type="string">ui/grid/filters/elements/ui-select</item>
        </item>
    </argument>
    <settings>
        <dataType>text</dataType>
        <label translate="true">Tags</label>
        <dataScope>tag_ids</dataScope>
    </settings>
    <formElements>
        <select>
            <settings>
                <options class="MageOS\Blog\Ui\Component\Form\Tags\Options"/>
                <multiple>true</multiple>
            </settings>
        </select>
    </formElements>
</field>
```

**Step 3: Replace the `related_post_ids` field.**

Replace with:

```xml
<field name="related_post_ids" formElement="select" sortOrder="10">
    <argument name="data" xsi:type="array">
        <item name="config" xsi:type="array">
            <item name="filterOptions" xsi:type="boolean">true</item>
            <item name="chipsEnabled" xsi:type="boolean">true</item>
            <item name="disableLabel" xsi:type="boolean">true</item>
            <item name="elementTmpl" xsi:type="string">ui/grid/filters/elements/ui-select</item>
        </item>
    </argument>
    <settings>
        <dataType>text</dataType>
        <label translate="true">Related Posts</label>
        <dataScope>related_post_ids</dataScope>
    </settings>
    <formElements>
        <select>
            <settings>
                <options class="MageOS\Blog\Ui\Component\Form\RelatedPosts\Options"/>
                <multiple>true</multiple>
            </settings>
        </select>
    </formElements>
</field>
```

**Step 4: XML lint + phpcs.**

Run: `cd /Users/david/Herd/mage-os-typesense && warden env exec -T php-fpm xmllint --noout /var/www/html/app/code/MageOS/Blog/view/adminhtml/ui_component/mageos_blog_post_form.xml && echo ok`

Expected: `ok`.

Run: `vendor/bin/phpcs --standard=phpcs.xml.dist --error-severity=1 --warning-severity=0 view/adminhtml/ui_component/mageos_blog_post_form.xml`

Expected: no error output.

### Task 3.5 — Handle CSV legacy + native array on save

The post-form previously sent comma-separated strings. The new `ui-select` sends arrays. Magento's data binding wraps multiple values in `null`-indexed arrays, so the repository's existing `setCategoryIds(int[])` signature must accept either. Locate the Save controller and normalize.

**Files:**
- Modify: `Controller/Adminhtml/Post/Save.php` (the field-normalization block)

**Step 1: Read the current normalization and patch.**

Run: `grep -n 'category_ids\|tag_ids\|related_post_ids' Controller/Adminhtml/Post/Save.php`

In the existing `normalizeIds` (or equivalent) routine, replace the CSV explode with the following:

```php
/**
 * @param mixed $raw
 * @return int[]
 */
private function normalizeIds(mixed $raw): array
{
    if (\is_array($raw)) {
        $values = $raw;
    } elseif (\is_string($raw) && $raw !== '') {
        $values = explode(',', $raw);
    } else {
        return [];
    }
    $ids = [];
    foreach ($values as $value) {
        $int = (int) $value;
        if ($int > 0) {
            $ids[] = $int;
        }
    }
    return array_values(array_unique($ids));
}
```

If the current code already handles both formats, no change is needed; just confirm.

**Step 2: Smoke-verify via Playwright.**

Re-open `https://app.mage-os-typesense.test/backend/mageos_blog/post/edit/post_id/4/`. Confirm:

- `Categories` ui-select renders hierarchical options; the two currently-assigned (News) is pre-selected.
- `Tags` ui-select renders all three tags; Magento + Release are pre-selected.
- `Related Posts` ui-select is empty (we didn't seed any).
- Pick one new category, remove one tag, pick two related posts, save.
- Reload the page; confirm the new picks stuck.

**Step 3: Commit.**

```bash
git add Ui/Component/Form/Categories Ui/Component/Form/Tags Ui/Component/Form/RelatedPosts view/adminhtml/ui_component/mageos_blog_post_form.xml Controller/Adminhtml/Post/Save.php
git commit -m "feat(admin): category + tag + related-post pickers on post form"
```

---

## Task 4 — Related-products insertListing picker

Magento's `product_listing` grid already exists and supports re-use as an insertListing target. We just embed it in the post form.

**Files:**
- Modify: `view/adminhtml/ui_component/mageos_blog_post_form.xml` — replace the `related_product_ids` field with the insertListing pattern. Remove the existing text-input field.

**Step 1: Replace the field.**

Find

```xml
<field name="related_product_ids" formElement="input" sortOrder="20">
    <settings>
        <dataType>text</dataType>
        <label translate="true">Related Product IDs (comma-separated)</label>
        <dataScope>related_product_ids</dataScope>
    </settings>
</field>
```

Replace with

```xml
<container name="related_product_ids_container">
    <htmlContent name="related_product_ids_content">
        <block class="Magento\Backend\Block\Template"
               name="related_product_ids.panel"
               template="Magento_Ui::form/components/complex.phtml"/>
    </htmlContent>
    <insertListing name="related_product_ids_listing" component="Magento_Ui/js/form/components/insert-listing">
        <argument name="data" xsi:type="array">
            <item name="config" xsi:type="array">
                <item name="autoRender" xsi:type="boolean">true</item>
                <item name="dataScope" xsi:type="string">related_product_ids</item>
                <item name="externalProvider" xsi:type="string">product_listing.product_listing_data_source</item>
                <item name="selectionsProvider" xsi:type="string">product_listing.product_listing.product_columns.ids</item>
                <item name="ns" xsi:type="string">product_listing</item>
                <item name="render_url" xsi:type="url" path="mui/index/render"/>
                <item name="realTimeLink" xsi:type="boolean">true</item>
                <item name="behaviourType" xsi:type="string">simple</item>
                <item name="externalFilterMode" xsi:type="boolean">true</item>
                <item name="imports" xsi:type="array">
                    <item name="productIds" xsi:type="string">${ $.provider }:${ $.dataProvider }</item>
                </item>
                <item name="exports" xsi:type="array">
                    <item name="productIds" xsi:type="string">${ $.provider }:${ $.dataProvider }</item>
                </item>
            </item>
        </argument>
        <settings>
            <dataLinks>
                <exports>true</exports>
                <imports>true</imports>
            </dataLinks>
            <listens>
                <link name="${ $.externalProvider }:params.filters">evaluateExternalFilters</link>
            </listens>
        </settings>
    </insertListing>
</container>
```

**Step 2: XML + phpcs.**

Run the same lint commands as Task 3. Fix any warnings.

**Step 3: Live smoke.**

Reload the post edit page. The **Related** fieldset now shows an embedded grid titled "Products" with search / filter / pagination. Select a couple of products, save, reload, confirm the `related_product_ids` value round-tripped through the pivot.

**Step 4: Commit.**

```bash
git add view/adminhtml/ui_component/mageos_blog_post_form.xml
git commit -m "feat(admin): related-products insertListing picker on post form"
```

---

## Task 5 — Final verification & push

**Step 1: Run every static gate.**

```bash
vendor/bin/phpstan analyse --memory-limit=1G --no-progress
vendor/bin/phpunit --testsuite unit
vendor/bin/phpcs --standard=phpcs.xml.dist --error-severity=1 --warning-severity=0
vendor/bin/php-cs-fixer fix --dry-run --diff --allow-risky=yes
```

All must pass. Unit count should be 59 (57 existing + 2 new in OptionsTest).

**Step 2: Final admin smoke via Playwright.**

- `/backend/mageos_blog/author/edit/author_id/1/` — renders without exception.
- `/backend/mageos_blog/category/edit/category_id/1/` — parent dropdown shows hierarchy + "None (Root)".
- `/backend/mageos_blog/post/edit/post_id/4/` — all three pickers work; related-products grid embeds.

**Step 3: Push.**

```bash
git push origin main
```

**Step 4: Update memory.**

Append to `/Users/david/.claude/projects/-Users-david-Herd-module-blog/memory/phase-5-handoff.md`:

- Two open v1.0.1 bugs are closed (admin form spinner + category/tag/author listings already closed in `6ecb54e`; new batch — author avatar, all four pickers).

Done.
