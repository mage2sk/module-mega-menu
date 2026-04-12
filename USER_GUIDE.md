# Panth Mega Menu — User Guide

This guide walks a Magento store administrator through every screen
and setting of the Panth Mega Menu extension. No coding required.

---

## Table of contents

1. [Installation](#1-installation)
2. [Verifying the extension is active](#2-verifying-the-extension-is-active)
3. [Configuration screens](#3-configuration-screens)
4. [The Menu Builder](#4-the-menu-builder)
5. [Item types](#5-item-types)
6. [Column layouts](#6-column-layouts)
7. [CMS block injection](#7-cms-block-injection)
8. [Mobile drawer behaviour](#8-mobile-drawer-behaviour)
9. [Per-store menus](#9-per-store-menus)
10. [Troubleshooting](#10-troubleshooting)
11. [CLI reference](#11-cli-reference)

---

## 1. Installation

### Composer (recommended)

```bash
composer require mage2kishan/module-mega-menu
bin/magento module:enable Panth_Core Panth_MegaMenu
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Manual zip

1. Download the extension package zip
2. Extract to `app/code/Panth/MegaMenu`
3. Make sure `app/code/Panth/Core` is also present
4. Run the same `module:enable … cache:flush` commands above

### Confirm

```bash
bin/magento module:status Panth_MegaMenu
# Module is enabled
```

---

## 2. Verifying the extension is active

After installation, three things should be true:

1. **Configuration page exists** — Stores → Configuration → Panth Extensions → Mega Menu is reachable
2. **Menu Builder exists** — Stores → Panth Infotech → Mega Menu → Menu Builder is reachable
3. **The storefront menu is replaced** — visit your store homepage and you should see the Panth menu instead of (or alongside) the native Magento top-menu

---

## 3. Configuration screens

Navigate to **Stores → Configuration → Panth Extensions → Mega Menu**.

### General

| Setting | Default | What it does |
|---|---|---|
| **Enable Module** | Yes | Master kill switch |
| **Replace Native Menu** | Yes | Hides Magento's default `top.menu` block and renders the Panth menu in its place. Set to No if you want both menus visible. |
| **Mobile Breakpoint (px)** | 1024 | Below this viewport width the desktop dropdown is replaced by the mobile drawer |
| **Hover Delay (ms)** | 150 | How long the user must hover before a dropdown opens. Lower = snappier, higher = fewer accidental opens. |
| **Animation Duration (ms)** | 200 | CSS transition timing for open / close |
| **Cache Menu** | Yes | Caches the rendered menu HTML for performance |

---

## 4. The Menu Builder

Navigate to **Stores → Panth Infotech → Mega Menu → Menu Builder**.

You will see a drag-and-drop tree editor on the left and an item
properties panel on the right.

- **Add a top-level item**: click "Add Item" at the top of the tree
- **Add a child item**: hover an existing item, click the "+" icon
- **Reorder items**: drag and drop
- **Edit an item**: click it, the right-hand panel shows its properties
- **Delete an item**: hover it, click the trash icon
- **Save the entire tree**: click "Save Menu" at the top right

---

## 5. Item types

Each menu item can link to one of:

| Type | What it is |
|---|---|
| **Manual URL** | Any URL (relative or absolute) |
| **Category** | Pick a Magento category from a dropdown — URL is generated automatically and updates when the category URL changes |
| **CMS Page** | Pick a CMS page from a dropdown — URL is generated automatically |
| **External Link** | Opens in a new tab |
| **Group Header** | A non-clickable label, used for organizing items in dropdown columns |

---

## 6. Column layouts

Each parent item with children can be displayed as a multi-column
dropdown. Open the parent item's properties and choose:

| Layout | Use case |
|---|---|
| **1 Column** | Simple single-column list (good for short menus) |
| **2 Columns** | Two columns of items, balanced automatically |
| **3 Columns** | Three columns, good for medium-sized categories |
| **4 Columns** | Four columns + optional CMS block on the right |

---

## 7. CMS block injection

Inside any column you can drop a CMS block — perfect for promo
banners, featured products, custom HTML. To do it:

1. Open the parent item's properties in the Menu Builder
2. Select the column you want
3. Click "Insert CMS Block"
4. Pick a block from the dropdown
5. Save

The block is rendered inline inside the dropdown column at runtime.
It is **lazily rendered** — the block's HTML is only generated when
the column is actually open in the user's browser, so unused blocks
never cost you any backend time.

---

## 8. Mobile drawer behaviour

Below the configured **Mobile Breakpoint** width, the desktop dropdown
is replaced by a slide-in drawer:

- Tap the hamburger icon → drawer slides in from the left
- Each parent item with children shows an accordion arrow
- Tap the arrow → that submenu expands inline (no nested drawers)
- Tap a leaf item → navigates to the URL
- Tap outside the drawer or the X → drawer slides out

The drawer uses big touch targets (44 px minimum), CSS transitions,
and respects `prefers-reduced-motion`.

---

## 9. Per-store menus

The Menu Builder is **store-view scoped**. To create a different menu
per store view:

1. Open the Menu Builder
2. At the top of the page, change the "Scope" dropdown to the target
   store view
3. Build / edit / save that store view's menu independently

To copy a menu from one store view to another, use the "Copy from
another store view" button.

---

## 10. Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| Native menu still visible | "Replace Native Menu" is set to No | Configuration → Mega Menu → Replace Native Menu = Yes |
| Menu doesn't appear at all | Module disabled / cache not flushed | `bin/magento module:status Panth_MegaMenu` then `cache:flush` |
| Edits don't show on the storefront | FPC + menu cache not invalidated | `cache:flush` or save any menu item to bump the cache tag |
| Mobile drawer doesn't open | JS error from another extension | Open browser console; the Panth drawer requires no jQuery / RequireJS so it works on Hyva and Luma — but a third-party JS error elsewhere can break Alpine init |
| Menu builder shows blank tree | Database empty for this store view | Click "Add Item" and start building, or "Copy from another store view" |

---

## 11. CLI reference

```bash
# Verify module status
bin/magento module:status Panth_MegaMenu

# Flush the menu cache
bin/magento cache:clean panth_megamenu  # if a dedicated cache type is enabled
bin/magento cache:flush                  # nuclear option

# Reindex (no specific indexer; menu reads from its own table)
bin/magento setup:upgrade
```

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422

Response time: 1-2 business days for paid licenses.
