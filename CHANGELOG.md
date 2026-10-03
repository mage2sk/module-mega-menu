# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.20] - 2026-10-03

### Fixed
- Item schedules: an end date that includes a time no longer hides the item permanently. Start and end dates are read in the store's configured timezone; a date with a time applies from or until that exact moment, and a date without a time covers the whole day. The storefront menu and the menu preview use the same rule.
- The Luma menu renderer scopes its stylesheet rule by rule instead of splitting it on semicolons: every selector in a comma separated list is prefixed, rules inside `@media` and `@supports` blocks are scoped, `@keyframes` and `@font-face` are kept as they are, and selectors for `html`, `body`, `#panthMenuContent` and `.megamenu-container` are not prefixed.
- `Block\Menu::getMenu()` no longer caches an inactive menu, so an inactive menu is never returned on a later lookup.
- Version History shows the real number of items in each version instead of an inflated count.
- Duplicating a menu creates an identifier made of lowercase letters, numbers and underscores (hyphens and other characters become underscores, `menu_copy` when nothing usable is left) and adds a numeric suffix when the identifier is taken, so the copy can be saved.
- `ViewModel\Menu::getItemDepth()` accepts a string level, `Block\StickyMenu::getStoreName()` returns an empty string when no store name is configured, and the navigation, breadcrumb and menu item blocks render menu trees made of plain arrays instead of failing with a type error.
