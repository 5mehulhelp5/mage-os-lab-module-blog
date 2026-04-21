# Blog frontend redesign — design

_Scope: v1.0.2 patch release. Pure frontend: layouts + CSS + templates. No PHP / schema / admin changes._

## Problem

Live review on 2026-04-21 against `https://app.mage-os-typesense.test/blog/` and `/blog/hello-world` surfaced three compounding issues:

1. **Double page heading.** Both the Luma page-title-wrapper and the blog template emit `<h1>Blog</h1>` on the index; same on post detail (`<h1>Hello World | Mage-OS Blog</h1>` + `<h1>Hello World</h1>`).
2. **Catalog sidebar bleed.** Every blog page uses `layout="2columns-right"` (from `blog_default.xml`), which inherits Luma's `sidebar.additional` container. Wishlist, Compare Products, and Last Added Items render on the right of every blog page — the module never intended this.
3. **No styling.** Templates output bare HTML with `.mageos-blog-*` class names, but no stylesheet targets those classes. Result is default browser flow — fine for preview, unacceptable for a "proper good looking blog".

## Non-goals

- Hyvä templates (still v1.1 per `phase-5-handoff.md`).
- Server-side performance (listing queries already paginate).
- Dark-mode theming.
- JS interactivity (no lazy-load, no infinite scroll, no share-button SDKs — plain links).
- Redesigning widgets (`Block/Widget/*` stays untouched; widgets can still be used in CMS blocks).

## Architecture

All blog layouts move to `layout="1column"`. `blog_default.xml` no longer references `sidebar.additional`, which removes the Luma catalog sidebar bleed by construction. Templates stop emitting their own `<h1>` — the Luma page-title block (already present in `1column`) owns the page heading, which also fixes the double-title.

One new stylesheet `view/frontend/web/css/blog.css` is referenced from `blog_default.xml` via `<head><css src="MageOS_Blog::css/blog.css"/></head>`. Everything blog-specific is namespaced under `.mageos-blog` so the CSS can't collide with Luma or merchant styles.

No JS. No Hyvä twin. No preprocessor — vanilla CSS with custom properties.

## Listing pages (index, category, tag, author, search)

Five pages share one card pattern:

```
┌───────────────────────────────────────────┐
│                                           │
│              HERO IMAGE                   │   16:9 ratio, object-fit: cover
│           (featured_image)                │   entire card is a link
│                                           │
├───────────────────────────────────────────┤
│  Why Mage-OS v1 matters         [<h2>]    │
│  by Jane Doe · Apr 18 · 2 min read        │
│                                           │
│  Long-form thoughts on the v1 release.    │   excerpt = short_content,
│  Read more →                              │   3-line clamp
└───────────────────────────────────────────┘
```

- `<article class="mageos-blog-card">` stacked vertically. `2rem` gap between cards.
- Hero image: full-width inside the card, 16:9 aspect via CSS `aspect-ratio`, `object-fit: cover`, wrapped in `<a>` so the whole image is a link to the post.
- No featured image → hero slot is omitted entirely (no placeholder box). Text block becomes the whole card.
- Text block: `<h2>` title linked to the post, meta row (author — linked if present — · formatted date · reading-time), excerpt, "Read more →" CTA.
- Context header only on `/blog/category/*`, `/blog/tag/*`, `/blog/author/*`, `/blog/search?q=*`: entity title + optional description as a block above the card stack. (On author pages: avatar + name + bio.)
- Pagination footer already handled by the `Listing` ViewModel — keep the existing prev / numbered / next links. Styled as a horizontal row under the card stack.

Card title is `<h2>` at 1.5rem on desktop, 1.25rem on mobile. Meta row is `.875rem` in `--mageos-blog-meta` gray.

## Post detail

```
          ┌─────────────────────────┐
          │                         │
          │       HERO IMAGE        │   at article-width (760px), rounded
          │      (featured)         │
          │                         │
          └─────────────────────────┘

              Hello World
              ─────────────
              by Jane Doe · April 17, 2026 · 1 min read

              Welcome to the Mage-OS Blog. This is
              our first post. Pellentesque habitant
              morbi tristique senectus et netus…

              (rich content, 760px max width)

              [ Magento ] [ Release ]      ← tag chips
              ─────────────────────────
              Share: Facebook · X · LinkedIn · Email
              ─────────────────────────

              Related posts
              ─────────────
              · Why Mage-OS v1 matters
              · Hyvä tips from the field
```

- `<article class="mageos-blog-post">` centered at `max-width: 760px`.
- Hero image kept at article width (no full-bleed break-out — simpler CSS, looks fine). Rounded 0.375rem corners, aspect-ratio 16:9.
- `<h1>` owned by the article, NOT the page-title-wrapper (suppressed via layout XML on `blog_post_view` only). Page `<title>` in `<head>` still set for SEO.
- Meta row directly below h1: author name (linked to `/blog/author/<slug>` if set) · formatted publish date · reading-time (omitted if null).
- `.mageos-blog-post__content` serif typography wrapper around `$post->getContent()`: proper paragraph margins, styled h2/h3, blockquotes, inline code, lists, responsive images.
- After content: tag chips row — each tag as a small pill linking to `/blog/tag/<url-key>`.
- After tags: social share bar using the existing `ViewModel\Post\SocialShare` output. Styled as a horizontal row of plain-text links, no colored icons.
- After share: "Related posts" section if `RelatedPostsProvider` returns any. Up to 3 posts, thumbnail + title row each.

## CSS (`view/frontend/web/css/blog.css`)

Vanilla CSS, ~250 lines, no preprocessor. Namespace `.mageos-blog`.

Custom properties at the root of `.mageos-blog`:

```css
--mageos-blog-font: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
--mageos-blog-font-serif: Charter, "Iowan Old Style", Georgia, serif;
--mageos-blog-text: 1rem;
--mageos-blog-h1: clamp(2rem, 3.2vw + 1rem, 3rem);
--mageos-blog-h2: 1.5rem;
--mageos-blog-meta-size: 0.875rem;

--mageos-blog-ink: #1a1a1a;
--mageos-blog-meta: #666;
--mageos-blog-border: rgba(0, 0, 0, 0.08);
--mageos-blog-chip-bg: rgba(0, 0, 0, 0.04);
```

Layout primitives:

```css
.mageos-blog-container { max-width: 760px; margin-inline: auto; padding-inline: 1.5rem; }
.mageos-blog-card { margin-bottom: 2rem; }
.mageos-blog-card__hero { aspect-ratio: 16/9; width: 100%; object-fit: cover; border-radius: 0.375rem; }
.mageos-blog-post__content { font-family: var(--mageos-blog-font-serif); font-size: 1.0625rem; line-height: 1.75; }
.mageos-blog-post__content img { max-width: 100%; height: auto; border-radius: 0.375rem; margin: 2rem 0; }
.mageos-blog-chip { display: inline-block; padding: 0.25rem 0.75rem; background: var(--mageos-blog-chip-bg); border-radius: 1rem; font-size: 0.875rem; }
```

Breakpoint at 640px only for h1 / h2 type scale downshift.

## Delivery + testing

- `blog_default.xml` adds the `<head><css>` include. No JS. No other asset changes.
- Developer mode serves dynamically. Production still needs `setup:static-content:deploy`.
- Playwright visual smoke against all five surfaces (`/blog/`, `/blog/hello-world`, `/blog/category/news`, `/blog/tag/magento`, `/blog/author/jane-doe`, `/blog/search?q=hyva`). Eyeball each — no pixel-diff automation for v1.0.2.
- No new unit tests (templates + CSS).

## Commit plan

1. `refactor(frontend): switch blog to 1column + strip Luma sidebar bleed` — layout XMLs, remove `sidebar.additional`.
2. `feat(frontend): blog.css — typography, layout primitives, cards` — new stylesheet + head include.
3. `feat(frontend): new post listing, detail, category/tag/author, search templates` — template rewrites.
4. `feat(frontend): related-posts strip + tag chips + share row on post detail` — post-detail extras.

## Out of scope, noted for v1.1+

- Hyvä twin package (`mageos/module-blog-hyva`).
- Full-bleed hero on post detail (breaks out of article container).
- Table of contents auto-generated from `<h2>` in post content.
- Prev / next post navigation below article.
- Reading-progress bar.
- Author avatar circle next to byline (we already have the data; trivial to add but not in this pass).
