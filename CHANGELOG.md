# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] — Initial release

### Added — menu builder
- Drag-and-drop tree editor in admin with unlimited depth
- Per-item properties: label, URL (manual / category / CMS page),
  icon (SVG or icon-font class), image, custom CSS class, target
  window
- Multi-column dropdown layouts (1 / 2 / 3 / 4 columns)
- CMS block injection — drop any CMS block into a dropdown column
- Conditional visibility by customer group, store view, date range
- Bulk JSON import / export of the menu tree

### Added — frontend
- **Hyva template** — Tailwind utilities + Alpine.js, no jQuery, no
  Knockout
- **Luma template** — vanilla JS, no RequireJS dependency
- **Mobile drawer** — slides in from the left, big touch targets,
  smooth open/close animation, sub-menu accordion
- Configurable hover-intent delay so dropdowns don't flicker on
  accidental mouse-overs
- Keyboard navigable — arrow keys, Enter, Escape, full ARIA roles
- Fixed-height menu container — no CLS

### Added — performance
- FPC integration with `cms_b` and `cat_c` cache tags so the menu
  is automatically invalidated when a referenced block or category
  changes
- Single DB query to load the entire menu tree
- Per-store-view cache scoping
- Lazy CMS block rendering (only when the column is actually open)

### Added — admin
- Store-view scoped Menu Builder with one-click copy from another
  store view
- Hardened ACL — separate admin resources for view, edit, delete
- In-admin documentation page

### Quality
- Constructor injection only — zero `ObjectManager::getInstance()`
  usage anywhere in the module
- All PHP files lint clean
- Composer validate passes

### Compatibility
- Magento Open Source / Commerce / Cloud 2.4.4 → 2.4.8
- PHP 8.1, 8.2, 8.3, 8.4

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422
