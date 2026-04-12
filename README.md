# Panth Mega Menu for Magento 2

[![Magento 2.4.4 - 2.4.8](https://img.shields.io/badge/Magento-2.4.4%20--%202.4.8-orange)]()
[![PHP 8.1 - 8.4](https://img.shields.io/badge/PHP-8.1%20--%208.4-blue)]()
[![Hyva Compatible](https://img.shields.io/badge/Hyva-Compatible-green)]()
[![Luma Compatible](https://img.shields.io/badge/Luma-Compatible-green)]()

A **modern, responsive mega menu** for Magento 2 that works on **both
Hyva and Luma** out of the box. Drag-and-drop tree builder, multi-
column dropdowns, image + icon support per item, custom CMS block
injection per column, smooth mobile drawer, role-based access.

---

## ✨ Why this extension

| | Other mega menu extensions | **Panth Mega Menu** |
|---|---|---|
| Theme support | Usually one (Luma OR Hyva) | **Both** — same module, two purpose-built templates |
| Mobile experience | Desktop dropdown shrunk | True mobile drawer with smooth slide animations and big touch targets |
| CMS block injection per column | Often missing | **Yes** — drop a CMS block into any dropdown column |
| Image + icon support | Sometimes | Per-item, including SVG icons |
| Drag-and-drop tree builder | Rare | Yes — visual tree editor in admin |
| Store-view scoped menus | Sometimes | Yes — different menu per store view |
| FPC-aware caching | Rare | Yes — full FPC integration with proper cache tags |
| Uses ObjectManager? | Often | **Never** — full constructor injection |

---

## 🎯 Features

### Menu builder
- **Drag-and-drop tree editor** in the admin — visual hierarchy with
  unlimited depth
- **Per-item config**: label, URL (manual / category / CMS page), icon
  (SVG or icon-font class), image, custom CSS class, target window
- **Multi-column dropdowns** — choose 1, 2, 3, or 4 columns per
  parent item
- **CMS block injection** — drop any CMS block into a dropdown column
  for promo banners, featured products, custom HTML
- **Conditional visibility** — show menu items only to specific
  customer groups, store views, or based on a date range

### Frontend
- **Hyva template** — Tailwind utilities + Alpine.js, no jQuery, no
  Knockout
- **Luma template** — vanilla JS, no RequireJS dependency
- **Mobile drawer** — slides in from the left, big touch targets,
  smooth open/close animation, sub-menu accordion
- **Hover delay tuning** — configurable hover-intent delay so the
  dropdowns don't flicker on accidental mouse-overs
- **Keyboard navigable** — arrow keys, Enter, Escape, full ARIA
- **No CLS** — fixed-height menu container so the dropdown never
  pushes layout

### Performance
- **FPC integration** — menu HTML is rendered into the FPC and
  invalidated by the standard `cms_b` / `cat_c` cache tags whenever
  a referenced block or category changes
- **Single DB query** to load the entire tree (with EAV joins
  resolved in one shot)
- **Per-store-view cache** — menu rows are scoped per store view
- **Lazy CMS block render** — blocks inside dropdown columns are
  rendered only when the column is actually open

### Admin
- **Store-view scoped** — different menu per store view, copy from
  another store with one click
- **Bulk import / export** — export the menu tree as JSON, edit
  externally, re-import
- **Role-based access** — separate admin resources for view, edit,
  delete
- **In-admin documentation** page

---

## 📦 Installation

### Via Composer (recommended)

```bash
composer require mage2kishan/module-mega-menu
bin/magento module:enable Panth_Core Panth_MegaMenu
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Via uploaded zip

1. Download the extension zip from the Marketplace
2. Extract to `app/code/Panth/MegaMenu`
3. Make sure `app/code/Panth/Core` is also installed
4. Run the same commands above starting from `module:enable`

### Verify

```bash
bin/magento module:status Panth_MegaMenu
# Module is enabled
```

---

## 🛠 Requirements

| | Required |
|---|---|
| Magento | 2.4.4 — 2.4.8 (Open Source / Commerce / Cloud) |
| PHP | 8.1 / 8.2 / 8.3 / 8.4 |
| `mage2kishan/module-core` | ^1.0 (installed automatically as a composer dependency) |

---

## 🔧 Configuration

Open **Stores → Configuration → Panth Extensions → Mega Menu**.

| Setting | Default | What it does |
|---|---|---|
| **Enable Module** | Yes | Master kill switch |
| **Replace Native Menu** | Yes | Hides Magento's default top-menu and renders the Panth menu in its place |
| **Mobile Breakpoint (px)** | 1024 | Below this width the desktop dropdown is replaced by the mobile drawer |
| **Hover Delay (ms)** | 150 | Time the user must hover before a dropdown opens |
| **Animation Duration (ms)** | 200 | CSS transition timing for open / close |
| **Cache Menu** | Yes | Cache the rendered menu HTML in the dedicated menu cache type |

### Menu builder

Open **Stores → Panth Infotech → Mega Menu → Menu Builder**. The
drag-and-drop tree editor lives here. Build your menu, choose how
many columns each dropdown should have, drop CMS blocks into columns,
add icons / images, then click Save.

---

## 📚 Documentation

Full administrator documentation is built into the admin panel:

**Stores → Panth Infotech → Mega Menu → Documentation**

It covers the menu builder, item types, column layouts, CMS block
injection, mobile drawer behaviour, troubleshooting, and the CLI
reference.

---

## 🆘 Support

| Channel | Contact |
|---|---|
| Email | kishansavaliyakb@gmail.com |
| Website | https://kishansavaliya.com |
| WhatsApp | +91 84012 70422 |

Response time: 1-2 business days for paid licenses.

---

## 📄 License

Commercial — see `LICENSE.txt`. One license per Magento production
installation. Includes 12 months of free updates and email support.

---

## 🏢 About the developer

Built and maintained by **Kishan Savaliya** — https://kishansavaliya.com.
Builds high-quality, security-focused Magento 2 extensions and themes
for both Hyva and Luma storefronts.
