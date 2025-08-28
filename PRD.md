# Product Requirements Document (PRD)
## Project: News Listing Shortcode (WP 4.9.8+, PHP 7.2.1+)

### 1) Summary
- **Plugin name:** News Listing Shortcode
- **One‑liner:** A production‑ready shortcode that renders configurable news listings in **grid** or **carousel** layouts with tag badges and ACF‑powered category icons.
- **Primary persona:** Site Admin
- **Secondary personas:** Editors/Authors
- **Goal:** Let non‑technical editors drop performant, accessible news lists anywhere via `[news_listing]` with clear layout/filter controls.
- **Non‑goals (MVP):** Admin settings UI, Gutenberg block UI, server‑side caching, custom REST endpoints.

### 2) Background & Problem
Theming for post listings varies widely across sites; custom page builders often introduce performance or maintenance overhead. We need a stable, portable shortcode that produces consistent listings and plays nicely with themes on legacy WordPress (4.9.8) and PHP (7.2.1).

### 3) Objectives & KPIs
- **Setup:** Editor can paste shortcode and publish in < 2 minutes.
- **Compatibility:** Works with **WordPress 4.9.8+** and **PHP 7.2.1+**.
- **Performance:** Grid render adds < 40 ms median server time (no external HTTP requests).
- **Accessibility:** Keyboard and screen‑reader friendly; WCAG 2.2 AA for contrast on tag badges and focus states.

### 4) Scope (MVP)
A single shortcode: **`[news_listing]`**

#### 4.1 Attributes
| Attribute | Type | Default | Allowed | Description | Example |
|---|---|---|---|---|---|
| `layout` | string | `grid` | `grid`, `carousel` | Chooses the layout. | `layout="carousel"` |
| `category` | string (CSV) | *(all)* | category slugs | Filters posts by any of the listed slugs (OR). | `category="business,technology"` |
| `count` | int | `9` | `1+` | Max posts to display. | `count="12"` |
| `category_icon` | bool | `true` | `true`, `false` | Show category icons from ACF term field `category_icon` (URL). Graceful if ACF absent. | `category_icon="false"` |
| `tags_badges` | bool | `true` | `true`, `false` | Show up to 3 tags as badges on the thumbnail. | `tags_badges="true"` |

#### 4.2 Query behavior
- Uses `WP_Query`.
- When `category` is provided, posts match **any** provided slug (logical OR) via `category_name` CSV.
- Grid layout uses standard pagination (`paged` or `page` query var). Carousel does not paginate.

#### 4.3 Layouts
- **Grid:** Responsive CSS grid; thumbnail (3:2), title (linked), excerpt, category icons; pagination below list.
- **Carousel:** Horizontal, responsive carousel with previous/next arrow buttons and keyboard (Left/Right) support; same content as grid per item.

#### 4.4 Item structure & styling
- **Thumbnail:** 3:2 aspect ratio. Implement CSS `aspect-ratio` where available and **padding‑top 66.666%** fallback for legacy browsers. Use `srcset` where possible; include `loading="lazy"` and `decoding="async"` (harmless if unsupported).
- **Tag badges:** Up to 3 tags overlaid in the top area of the thumbnail; truncated with ellipsis; ensure 4.5:1 contrast.
- **Title:** Linked to the post; use `esc_url()` and `esc_html()`; preserve accessible focus styles.
- **Excerpt:** Truncated via `wp_trim_words()`; sanitized with `wp_kses_post()`.
- **Category icons:** For each category on the post, fetch ACF term field **`category_icon`** (image URL): `get_field('category_icon', 'category_' . $term_id)` if ACF is present; otherwise try `get_term_meta($term_id, 'category_icon', true)`. Sanitize URLs and set `alt` to the category name. Omit if none.

#### 4.5 Asset loading
- Namespaced classes `nlp-*` to avoid conflicts.
- Enqueue styles/scripts **only when the shortcode renders** on a page. No global admin CSS injections.
- No jQuery dependency; carousel uses vanilla JS + CSS scroll‑snap.

### 5) Out of Scope (MVP)
- Admin settings UI
- Gutenberg block version
- Server‑side caching
- Tag filters for queries (taxonomy query is not part of MVP)
- Custom placeholder media management

### 6) User Stories & Acceptance Criteria
1. **Default grid render**
   - *As an Editor, I can add `[news_listing]` and see a responsive grid of up to 9 posts with pagination.*
   - **Acceptance:** No console errors; grid uses 1–2 columns on mobile, ≥3 on desktop; pagination works on static pages and archives.

2. **Category filter**
   - *As an Editor, I can set `category="business,technology"` to include posts from either category.*
   - **Acceptance:** Query contains only posts from the listed categories; unrelated categories are excluded.

3. **Carousel layout**
   - *As a Visitor, I can browse items horizontally using left/right buttons and keyboard arrows.*
   - **Acceptance:** Buttons have `aria-label`s; arrow keys scroll by one card width; swipe works on touch; no layout shift.

4. **Category icons from ACF (soft dep)**
   - *As a Visitor, I see category icons if provided; if ACF or icons are absent, layout remains intact.*
   - **Acceptance:** No fatals if ACF is disabled; image URLs sanitized; `alt` text equals category name.

5. **Tag badges overlay**
   - *As a Visitor, I see up to 3 tag badges on the thumbnail with readable contrast.*
   - **Acceptance:** Badges truncate long labels; contrast ≥ 4.5:1; hidden from screen readers if redundant with text elsewhere.

### 7) UX Notes
- Grid columns: `repeat(auto-fit, minmax(280px, 1fr))`; 16px gaps.
- Pagination: `paginate_links()` with prev/next arrows and screen‑reader labels.
- Focus states: visible outlines for controls; minimum hit area 44×44px.
- Theme override guidance: allow overrides via CSS (namespaced classes); document typical hooks.

### 8) Technical Requirements
- **WordPress:** 4.9.8+
- **PHP:** 7.2.1+
- **Security:** Sanitize all shortcode attributes; escape output; no inline event handlers; read‑only operations (no nonce needed).
- **Performance:** Avoid global queries; batch term lookups; zero external HTTP requests; enqueue assets only when in use.
- **i18n:** Text domain `news-listing`; ship `.pot`.
- **Uninstall:** No persistent options in MVP.
- **Coding standards:** WPCS + PSR‑12 via PHPCS.

### 9) Telemetry & Privacy
- No telemetry in MVP. No PII collected or transmitted.

### 10) Risks & Mitigations
- **Multiple shortcodes on one page:** Pagination may conflict (shared `paged`). *Mitigation:* document limitation; consider per‑instance pagination param in v1.1.
- **ACF not installed:** Icons won’t show. *Mitigation:* soft dependency with fallback to `get_term_meta`; omit gracefully.
- **Theme CSS interference:** Use namespaced selectors; avoid global admin CSS; provide example overrides in README.

### 11) Milestones
- **M0:** Scaffolding (repo + skeleton + tooling)
- **M1:** Shortcode engine + query
- **M2:** Grid layout + pagination
- **M3:** Carousel + JS
- **M4:** Icons & tag badges + styling
- **M5:** A11y, i18n, docs, tests, hardening

### 12) Dependencies
- Optional: Advanced Custom Fields (term field `category_icon` returning an **image URL**) — *soft dependency*.

### 13) Release Plan
- Version **1.0.0** (zip artifact)
- README includes parameter table, examples, and theme override notes.
- Changelog and semantic versioning from the start.

### 14) Open Questions
- Do we need per‑instance pagination (e.g., `paged_id`) to support multiple grids on one page in v1.1?
- Should we expose a filter for excerpt length (e.g., `nls_excerpt_length`)?
