# Magento 2 Mega Menu

Panth Mega Menu replaces the default Magento top navigation with menus that are built in the admin and rendered by the module's own templates. A menu is a tree of items (category links, product links, CMS pages, custom URLs, custom HTML, CMS blocks, widget code and account links) with per-item icons, images, badges, colours, column layout, visibility rules and scheduling. The storefront renders the menu whose identifier is set in configuration (default "mainmenu") and removes Magento's `catalog.topnav` block once that identifier resolves to an active menu with visible items for the store view.

It is aimed at merchants who want to manage navigation without editing theme files, and at developers who need repositories, view models and templates to build on. The storefront menu uses one shared renderer, stylesheet and plain JavaScript file on Hyva and Luma; older Hyva and Luma template sets are still shipped for themes that override them.

Product page: [Magento 2 Mega Menu](https://kishansavaliya.com/magento-2-mega-menu.html)

## Features

- Menus are managed in an admin grid ("Menu Items") with create, edit, duplicate, enable/disable, delete, export and import actions.
- Drag-and-drop item builder (SortableJS) inside the menu form; the item tree is stored as JSON on the menu record.
- Item types in the builder: "Custom URL", "Dropdown Parent (No Link)", "Separator / Divider", "Link to Category", "Link to Product", "CMS Page", "Custom HTML", "Widget Code", "My Account Dashboard", "My Orders" and "My Wishlist". Sub-menu items can additionally be CMS blocks; the builder disables the CMS block and widget types for top-level items.
- Per-item options: icon (Font Awesome, LineIcons, Material Icons stylesheets are shipped, or an emoji), uploaded image with width and height, badge text, tooltip, ARIA label and role, link target and rel, CSS class, background, text and hover colours, font family, size and weight, padding, margin, border, column width, visibility per device (desktop, tablet, mobile), store views, customer groups, start date and end date, click action, custom content and data attributes.
- Category import builds items from the top-level categories down to a chosen depth (0 imports all levels).
- Version history: every save through the menu repository records a version (number, admin user, optional comment). Versions can be viewed, exported, restored and deleted from the "Version History" tab.
- JSON export of a menu and JSON import with "Create New Menu" or "Replace Existing Menu".
- Frontend preview of a menu, including unsaved changes, on a page that is sent with no-cache headers and `X-Robots-Tag: noindex, nofollow`.
- Demo CMS blocks (`panth_menu_demo_featured`, `panth_menu_demo_promo`, `panth_menu_demo_newsletter`) can be created from the admin for testing block injection.
- One storefront markup and design for Hyva, Luma and the admin preview: `pmm/render.phtml` renders the menu, `view/frontend/web/css/pmm.css` styles it and `view/frontend/web/js/pmm.js` (plain JavaScript, no Alpine.js or jQuery) drives hover intent, click and tap, keyboard navigation, the overflow arrows and the mobile drawer. See "Storefront menu" below.
- Theme detection (`Helper\Theme`) treats a store as Hyva when the `Hyva_Theme` module is enabled or the theme path contains "hyva", otherwise as Luma.
- Block output caching keyed by store, menu identifier, theme and customer, with a configurable lifetime and the cache tags `panth_megamenu` and `panth_megamenu_<menu_id>`. A "Flush Cache" admin action cleans the `full_page` and `block_html` cache types.
- Custom CSS and custom JavaScript fields in configuration, plus per-menu custom CSS and container styling.
- ACL resources for menu management, save, delete, the builder, item save and delete, version view and restore, and configuration.

## Compatibility

| Requirement | Supported |
|---|---|
| Magento Open Source | 2.4.4 to 2.4.8 (as published on the product page) |
| Adobe Commerce | 2.4.4 to 2.4.8 (as published on the product page) |
| PHP | 8.1, 8.2, 8.3, 8.4 (`~8.1.0||~8.2.0||~8.3.0||~8.4.0` in composer.json) |
| Themes | Hyva and Luma (one shared storefront renderer, `pmm/render.phtml`) |

Composer constraints on Magento packages: `magento/framework ^103.0`, `magento/module-backend ^102.0`, `magento/module-store ^101.1`, `magento/module-cms ^104.0`, `magento/module-catalog ^104.0`, `magento/module-config ^101.2`, `magento/module-ui ^101.2`.

## Requirements

- Magento Open Source or Adobe Commerce 2.4.4 to 2.4.8
- PHP 8.1 to 8.4
- `mage2kishan/module-core` ^1.0.17 (installed by Composer with this package). This module hangs its admin menu under `Panth_Core::panth_extensions`, registers itself with `Panth\Core\ViewModel\ThemeConfig` and uses `Panth\Core\Security\UploadExtensionPolicy` for image uploads.
- Suggested: `hyva-themes/magento2-default-theme` when the Hyva templates are wanted

## Installation

```bash
composer require mage2kishan/module-mega-menu
bin/magento module:enable Panth_Core Panth_MegaMenu
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

`setup:di:compile` is only needed in production mode. The module ships JavaScript, CSS, fonts and images under `view/adminhtml/web`, `view/base/web` and `view/frontend/web`, so static content has to be deployed.

Check the result with:

```bash
bin/magento module:status Panth_MegaMenu
```

`setup:upgrade` creates the module tables and runs the data patch `Setup\Patch\Data\AddDefaultStoreRelations`, which assigns store ID 0 (all store views) to any existing menu that has no store relation.

## Configuration

Admin path: Stores > Configuration > "Panth Extensions" > "MegaMenu". In `etc/adminhtml/system.xml` the tab label is prefixed with a rocket emoji. All fields can be set at default, website and store view scope. Config paths follow the pattern `panth_megamenu/<group>/<field>`; the field id is shown in brackets in the tables below.

### General Settings (`panth_megamenu/general`)

| Setting | Default | What it does |
|---|---|---|
| Enable MegaMenu (`enabled`) | Yes | Master switch. When set to No the module renders nothing and the default Magento navigation stays in place. This includes menus placed in CMS content with a `{{block class="Panth\MegaMenu\Block\Menu" ...}}` directive. |
| Menu Identifier (`menu_identifier`) | mainmenu | Identifier of the menu record to render. Shown when Enable MegaMenu is Yes. |
| Mobile Breakpoint (px) (`mobile_breakpoint`) | 1024 | Screen width at which the mobile menu activates. Shown when Enable MegaMenu is Yes. |
| Enable Sticky Menu (`sticky_menu`) | No | Keeps the menu fixed at the top of the viewport when scrolling; details are in Sticky Menu Settings. Shown when Enable MegaMenu is Yes. |

### Styling Settings (`panth_megamenu/styling`)

| Setting | Default | What it does |
|---|---|---|
| Hover Effect (`hover_effect`) | Underline | Effect applied to menu items on hover. Options: None, Underline, Fade, Highlight, Slide, Glow, Grow. |
| Image Size (`image_size`) | thumbnail | Text field naming the image size preset for category images in dropdowns. |
| Custom CSS (`custom_css`) | empty | CSS rules for the menu, without `<style>` tags. |

The group comment states that menu colours are taken from the Hyva theme's Tailwind `theme-config.json`. The module ships its default `pmenu-*` design tokens in `etc/theme-config.json`.

### Display Settings (`panth_megamenu/display`)

| Setting | Default | What it does |
|---|---|---|
| Animation Type (`animation_type`) | Fade | Animation used when a dropdown opens or closes. Options: None, Fade, Slide, Grow. |
| Animation Duration (ms) (`animation_duration`) | 200 | Duration of the dropdown animation. |
| Hover Intent Delay (ms) (`hover_intent_delay`) | 150 | Delay before a dropdown opens on hover. |
| Show Icons (`show_icons`) | Yes | Display item icons when set. |
| Show Images (`show_images`) | Yes | Display item images in dropdown panels. |
| Maximum Menu Depth (`max_depth`) | 5 | Number of menu levels rendered. Top-level items are the first level, so 3 shows top-level items and two levels below them. Every storefront template applies the same limit. |
| Columns (`columns`) | 4 | Default number of columns in a mega dropdown. |
| Show Category Product Count (`show_category_count`) | No | Show the product count next to category names. |
| Enable Custom Blocks (`enable_custom_blocks`) | Yes | Allow CMS blocks inside dropdowns. |

### Mobile Settings (`panth_megamenu/mobile`)

| Setting | Default | What it does |
|---|---|---|
| Enable Mobile Menu (`mobile_enabled`) | Yes | Enables the off-canvas mobile menu. |
| Mobile Menu Position (`position`) | Left | Slide-in side of the mobile panel: Left or Right. |
| Show Overlay (`overlay_enabled`) | Yes | Semi-transparent overlay behind the open mobile menu. |
| Accordion Style (`accordion_enabled`) | Yes | Expand and collapse sub-menus in place on mobile. |
| Enable Swipe Gestures (`swipe_enabled`) | No | Allow swipe gestures to open and close the mobile menu. |
| Animation Speed (ms) (`animation_speed`) | 300 | Open and close animation speed of the mobile menu. |
| Show Category Icons (`show_category_icons`) | Yes | Display item icons in the mobile menu. |

All fields except Enable Mobile Menu are shown only when Enable Mobile Menu is Yes. These settings are read by the Luma templates and by `js-init.phtml`; the Hyva templates switch between desktop and mobile with Tailwind's `lg` breakpoint.

### Sticky Menu Settings (`panth_megamenu/sticky`)

Shown only when Enable Sticky Menu in General Settings is Yes.

| Setting | Default | What it does |
|---|---|---|
| Sticky Offset (px) (`offset`) | 100 | Scroll distance before the sticky state activates. |
| Hide on Scroll Down (`hide_on_scroll_down`) | No | Hide the sticky menu while scrolling down. |
| Show on Scroll Up (`show_on_scroll_up`) | Yes | Reveal the sticky menu while scrolling up. |
| Show Shadow (`show_shadow`) | Yes | Drop shadow under the sticky menu. |
| Compact Mode (`compact_mode`) | No | Reduced-height layout while sticky. |
| Animation Speed (ms) (`animation_speed`) | 300 | Show and hide animation speed of the sticky menu. |

### Performance Settings (`panth_megamenu/performance`)

| Setting | Default | What it does |
|---|---|---|
| Enable Menu Cache (`cache_enabled`) | Yes | Cache the rendered menu block. When Yes, the block cache lifetime is taken from Cache Lifetime. |
| Cache Lifetime (seconds) (`cache_lifetime`) | 3600 | Lifetime of the cached menu block. Shown when Enable Menu Cache is Yes. |
| Lazy Load Dropdowns (`lazy_load`) | No | Load dropdown content on demand. |

### Advanced Settings (`panth_megamenu/advanced`)

| Setting | Default | What it does |
|---|---|---|
| Enable Debug Mode (`enable_debug`) | No | Debug logging for troubleshooting. |
| Custom JavaScript (`custom_js`) | empty | JavaScript added for the menu, without `<script>` tags. |

Default behaviour after installation: the module is enabled and looks for an active menu with the identifier `mainmenu`. Until such a menu exists for the store view, the layout plugin keeps the default navigation elements (`catalog.topnav`, `topmenu_desktop`, `topmenu_mobile`), so Hyva and Luma show their native category menu. When the menu resolves, the plugin removes them and adds the body class `panth-megamenu-active`, which the Luma styles use to hide the native nav toggle.

Admin menu entries: "Panth Extensions" > "Mega Menu" > "Menu Items" (route `panth_menu/menu/index`) and "Panth Extensions" > "Mega Menu" > "Configuration" (opens the MegaMenu configuration section).

The module does not add category attributes to the category edit form.

## Usage

### Creating a menu

1. Open "Panth Extensions" > "Mega Menu" > "Menu Items" and click "Create Menu".
2. In "General Information" set "Menu Title", "Identifier", "Enable Menu", "CSS Class", "Sort Order", "Description" and "Custom CSS". The identifier must match the Menu Identifier configuration value for the menu to appear on the storefront. Identifiers are unique.
3. "Menu Container Styling" holds "Container Background Color", "Container Padding", "Container Margin", "Gap Between Menu Items", "Container Max Width", "Container Border", "Container Border Radius", "Container Box Shadow" and "Menu Items Alignment" (Left, Center, Right, Space Between, Space Around).
4. Build the tree in "Menu Items". The same tab has a "Menu Items JSON (Advanced)" field with the raw JSON.
5. Save. The "Version History" tab lists the versions recorded for the menu.

Saving, importing, toggling, restoring or deleting a menu cleans the cache entries tagged `panth_megamenu` (menu block cache and full page cache entries of pages that show a menu). The "Flush Cache" action in the menu grid is still available.

Menus saved from the builder are assigned to store ID 0 (all store views); the builder has no store selector.

### Item types and URLs

- "Link to Category", "CMS Page" and "Link to Product" resolve the URL from the selected entity at render time; a URL entered on the item takes precedence.
- "Custom URL" uses the entered URL. Only relative URLs and the `http`, `https`, `mailto` and `tel` schemes are rendered; any other scheme (for example `javascript:` or `data:`) is replaced with `#`. For "My Account Dashboard", "My Orders" and "My Wishlist" the builder fills the URL field with `/customer/account`, `/sales/order/history` or `/wishlist`.
- "Dropdown Parent (No Link)", "Custom HTML", CMS block and "Widget Code" items are rendered without a link target (`#`).
- CMS block and "Widget Code" items are only available for sub-menu items and render the block or widget inside the dropdown panel. CMS block rendering can be switched off with Enable Custom Blocks.
- "Separator / Divider" can be selected in the builder; the frontend templates contain no dedicated rendering for this type.

### Item visibility

An item is rendered only when all of the following hold: it is active; its level is below Maximum Menu Depth; the current time is within its start and end date (both are read in the store timezone; a date with a time applies from or until that moment, a date without a time covers the whole day); its store view list is empty or contains the current store (or store 0); its customer group list is empty or contains the current customer group; and its device flag for the current device is set.

### Layout and columns

Parent items with children open a dropdown panel. The number of columns comes from the item's column settings, with the Columns configuration value as the store default. CMS blocks and custom HTML placed on sub-items fill a column of the panel. Custom HTML is passed through the CMS page filter, so CMS directives such as `{{store url=""}}` and `{{widget ...}}` are processed; the HTML itself is not stripped, so only trusted admin users should be allowed to edit menus.

### Images and icons

Images are uploaded from the builder to `pub/media/panth/megamenu/`; allowed extensions are jpg, jpeg, gif, png and webp, the file MIME type must be one of the matching image types, and the name is checked against `Panth\Core\Security\UploadExtensionPolicy`. SVG files cannot be uploaded. Icons are entered as a class from the shipped Font Awesome, LineIcons or Material Icons stylesheets, or as an emoji. Inline SVG icons that contain scripts, event handler attributes, `javascript:` URLs, `foreignObject`, embedded frames or character references are not rendered.

### Storefront menu

`luma/menu.phtml` (Luma, also used by `luma/desktop/menu.phtml`), the Panth Infotech theme template `Panth_MegaMenu::menu.phtml` (Hyva, with the category tree as fallback when no menu resolves) and `preview.phtml` collect the item tree and the configuration and render it through `pmm/render.phtml`. The stylesheet `css/pmm.css` is added to every storefront page by `default.xml`; the script `js/pmm.js` is loaded once by the first menu on the page. Every menu instance gets its own ids, so several menus (header plus CMS directives) work on one page.

Desktop (Hyva from 1024px, Luma above Mobile Breakpoint):

- A top-level item whose children have children opens a mega panel; otherwise it opens a simple dropdown. Both share one look: white panel, 1px `#E5E7EB` border, 8px radius, one shadow, 14px text, `#0F766E` hover text and `#F0FDFA` hover tint.
- A mega panel with one to three link columns and no promo tile, image or CMS content is sized to its columns (200-240px per column), opens under its top-level item and moves left when it would cross the container or viewport edge. A mega panel with four or more columns, a promo tile, an image or CMS content spans the nearest container with a max width (the theme container or the page column) and lays the columns out in a grid that shares the width evenly. Each panel shows each second-level item as a column heading with its links below, and shows third and deeper levels inline under their parent, indented with a guide line. A second-level item without children but with custom HTML, a CMS block or an image becomes a 240-300px promo tile at the end of the row. Column Width accepts a number of columns (1-6) or a CSS length. The top item's CMS block and image are shown in a footer row.
- Simple dropdowns are 220-320px wide, stay inside the viewport (they align to the right edge of their trigger near the right side of the screen) and wrap CMS block content. Panels get a max height with internal scroll on short screens and use z-index 60.
- The open item shows a 2px primary-colour bar and the hover tint; `aria-current="page"` items show the bar too. Hover Effect maps to: underline and slide (bar on hover), highlight (tint on hover), fade (80% opacity on hover); glow and grow fall back to the colour change.
- Hover opens after Hover Intent Delay (at most 400ms); while a panel is open, moving to another item switches after 120ms and moving into the open panel cancels the switch, so a diagonal pointer path does not close it. The panel closes 280ms after the pointer leaves the menu, on Escape and on a click outside.
- Touch: the first tap on an item with a dropdown opens it, the second tap follows the link. Items whose URL is `#` or empty only toggle their dropdown.
- Keyboard: Tab moves between top-level items, Enter follows a link, Enter on a `#` item, Space and Arrow Down open the dropdown and focus its first link, Arrow Up opens it at the last link, Arrow Left and Right move between top-level items, Home and End jump to the first and last item, arrows move inside the panel and Escape closes it and returns focus to the item. Triggers carry `aria-haspopup`, `aria-expanded` and `aria-controls`.
- When the items do not fit, the bar scrolls horizontally (mouse wheel or the arrow buttons, which only show while there is more to scroll); labels longer than 22em are truncated with an ellipsis.

Mobile (below the same breakpoints): the menu button opens an off-canvas drawer (left or right, following Mobile Menu Position) with a backdrop, both at z-index 70. The drawer is moved to the end of `<body>`, focus goes to the close button and returns to the menu button on close, Tab stays inside the drawer, Escape and the backdrop close it, and the page does not scroll while it is open. While the drawer is open the `<html>` element has the class `panth-overlay-open`, which floating buttons use to hide. Sub-menus open as an accordion with 48px rows, 16px text and a chevron button per item; custom HTML and CMS blocks are shown as a tinted tile.

Per-item text, hover text, hover background, background, font family, size, weight and text transform values are applied when they are plain CSS values; values containing `;`, braces, quotes, `url(` or `expression(` are ignored.

### Sticky menu

On Luma the desktop widget receives the sticky flag and offset and inserts a placeholder while the menu is fixed. On Hyva the template `hyva/sticky/header.phtml` renders through `Block\StickyMenu` when a theme layout adds that block.

### Preview

The "Preview" button opens an admin popup with Desktop (1280px, scaled to fit), Tablet (768px) and Mobile (375px) widths and a Refresh button. The popup posts the current, unsaved menu to the frontend route `panth_menu/preview/index` inside an isolated frame; links in the frame open in a new tab. The route only renders for requests that carry a valid `preview_token`: a signed token (HMAC-SHA256 over an expiry time with the deployment `crypt/key`) that the admin menu edit pages embed when they are loaded and that is valid for one hour (`Model\PreviewAccess::TOKEN_LIFETIME`). Reload the edit page to get a new token. A one-time token from `panth_menu/menu/previewToken` together with `menu_id` is also accepted. Requests without a valid token get the 404 page. POST requests (unsaved `items_json`) fail CSRF validation unless they carry a valid token, and unsaved items are only read from the POST body. The preview page uses the `empty` page layout, removes the regular menu blocks, renders the requested menu (saved items for `menu_id`, or the unsaved items from the POST body) with the same storefront markup, stylesheet and script inside `preview-container.phtml`, so Desktop shows the dropdowns and Tablet and Mobile show the drawer exactly as the storefront does. The page uses its own root template (`preview/root.phtml`) that outputs the storefront head assets and only the preview container, the menu custom CSS block and the Hyva Alpine.js script, so the store header, footer and other page blocks are not shown. It works on Hyva and Luma and is sent with no-cache headers.

The menu builder page loads a Tailwind stylesheet and the Inter font shipped with the module (`view/adminhtml/web/css/tailwind.min.css`, `view/adminhtml/web/fonts/inter.woff2`) instead of the Tailwind CDN and Google Fonts. Item titles, URLs, CSS classes, colours, icons and picker entries are HTML-escaped before the builder inserts them into the page. The Delete, Toggle, Duplicate, Flush Cache and Create Demo Blocks admin actions accept POST requests only; Toggle, Duplicate and Create Demo Blocks require the admin form key.

### Import, export and demo data

- "Export" downloads `menu_<identifier>_<timestamp>.json` with the menu data and items.
- "Import Menu" accepts a JSON file and either creates a new menu or replaces a selected one. The JSON may be at most 5 MB and 64 levels deep, and `menu` and `items` must both be objects or arrays.
- "Import Categories" (`panth_menu/menu/importCategories`) creates items from the category tree down to a maximum level.
- "Create Demo Blocks" creates three CMS blocks with the identifiers listed under Features.

### Layout and templates

- `view/frontend/layout/default.xml` adds `css/pmm.css` to the page head and `megamenu.main` (`Block\Menu`, template `Panth_MegaMenu::luma/menu.phtml`) to `header-wrapper` after the logo, guarded by `ifconfig="panth_megamenu/general/enabled"`.
- `view/frontend/layout/default_luma.xml` declares `megamenu.main` in `header.container` with `Panth_MegaMenu::luma/desktop/menu.phtml`.
- `view/frontend/layout/default_hyva.xml` removes `megamenu.main`; on Hyva the theme layout is expected to add `Block\Menu` with the Hyva templates (see the comment in that file).
- `view/frontend/layout/panth_menu_preview_index.xml` builds the preview page.
- Templates that a theme can override: `pmm/render.phtml` (shared storefront markup), `hyva/menu.phtml`, `hyva/desktop/menu.phtml`, `hyva/desktop/item.phtml`, `hyva/desktop/item_recursive.phtml`, `hyva/mobile/menu.phtml`, `hyva/mobile/item.phtml`, `hyva/mobile/drawer.phtml`, `hyva/sticky/header.phtml`, `hyva/css/styles.phtml`, `hyva/js/alpine-components.phtml`, `hyva/menu_mobile_premium.phtml`, `luma/menu.phtml`, `luma/desktop/menu.phtml`, `luma/desktop/item.phtml`, `luma/item.phtml`, `luma/mobile/menu.phtml`, `luma/mobile/menu_simple.phtml`, `luma/mobile/item.phtml`, `luma/sticky/header.phtml`, `luma/css/styles.phtml`, `luma/js/knockout-components.phtml`, `pmenu/menu.phtml`, `pmenu/item.phtml`, `preview.phtml`, `preview-container.phtml`, `js-init.phtml` and `theme-config.phtml`.
- Frontend RequireJS aliases: `pmenuDesktop`, `pmenuMobile` and the legacy aliases `panthMegaMenu`, `megaMenu`, `megaMenuWidget`, all pointing at `Panth_MegaMenu/js/pmenu-desktop` or `pmenu-mobile`. Admin alias: `menuPreview`.

### Caching

`Block\Menu::getCacheKeyInfo()` keys the block cache by store, menu identifier, theme and customer ID, and `getIdentities()` always returns `panth_megamenu` and, once a menu is loaded, `panth_megamenu_<menu_id>`. `Model\Menu` uses the cache tags `panth_megamenu_menu` and `panth_megamenu` and returns them (plus the ID variants) from `getIdentities()`, so saving or deleting a menu cleans the block cache and the full page cache entries of pages that show a menu. `Helper\Data::cleanMenuCache()` cleans entries by those tags and `Helper\Data::flushMenuCache()` cleans the `full_page` and `block_html` cache types.

## Developer Notes

- Module name: `Panth_MegaMenu`; Composer package: `mage2kishan/module-mega-menu` (version 1.0.12); PHP namespace: `Panth\MegaMenu`.
- Service contracts (preferences in `etc/di.xml`): `Api\MenuRepositoryInterface` (`save`, `getById`, `getByIdentifier`, `getList`, `delete`, `deleteById`), `Api\ItemRepositoryInterface` (adds `getMenuTree`, `moveItem`), `Api\MenuVersionRepositoryInterface` (adds `getByMenuId`), and the data interfaces `Api\Data\MenuInterface`, `Api\Data\ItemInterface`, `Api\Data\MenuVersionInterface` with their search results interfaces.
- `Model\MenuRepository::save()` creates a `panth_megamenu_menu_version` row on every save.
- Blocks: `Block\Menu` (`getCurrentMenu`, `getCurrentMenuTree`, `getMenuConfig`, `getMenuConfigJson`, `getCacheKeyInfo`, `getIdentities`), `Block\MenuItem`, `Block\Navigation`, `Block\MobileMenu`, `Block\StickyMenu`, `Block\Preview`.
- View models: `ViewModel\Menu` (`getMenuItems`, `getItemUrl`, `isItemVisible`, `getChildren`, `renderItemContent`, `getBadgeHtml`, `getBreadcrumbTrail`), `ViewModel\Config`, `ViewModel\MobileConfig`, `ViewModel\StickyConfig`, `ViewModel\ItemRenderer`.
- Helpers: `Helper\Data` (configuration getters, `cleanMenuCache`, `flushMenuCache`, `getMenuCacheTags`), `Helper\Theme` (`isHyva`, `isLuma`, `getCurrentTheme`, `getTemplateForTheme`, `getThemeConfig`), `Helper\MenuRenderer` (`renderDesktopMenu`, `renderDesktopMenuLuma`, `renderCmsBlock`, `renderIcon`, `renderImage`, `sanitizeUrl`, `filterContent`, `sanitizeTree`), `Helper\Config` (reads `panth_megamenu/mobile/mobile_enabled` for `isMobileEnabled()`).
- `Model\PreviewAccess` creates and validates the signed preview tokens. `Block\Menu` and `Block\Preview` pass the item tree through `Helper\MenuRenderer::sanitizeTree()` (URL scheme check and CMS filter for `custom_content`) before templates render it.
- Plugins (`etc/di.xml`): `Plugin\RemoveDefaultNavigation` (after `Magento\Framework\View\Layout::generateElements`, removes `catalog.topnav`, `topmenu_desktop`, `topmenu_mobile` when the module is enabled and an identifier is set); `Plugin\ThemeResolver` on `Magento\Framework\View\DesignInterface`; `Plugin\DisableBackendValidation` on `Magento\Backend\App\Request\BackendValidator` (skips validation for the read-only `GetCategories`, `ImportCategories` and `GetIcons` admin actions; for `CustomSave` it copies `form_key` from the JSON body into the request so the normal form key check applies).
- `etc/frontend/di.xml` registers the module with `Panth\Core\ViewModel\ThemeConfig`; `etc/theme-config.json` holds the default `pmenu-*` tokens.
- `etc/adminhtml/di.xml` declares the virtual type `Panth\MegaMenu\ItemImageUploader` (`Magento\Catalog\Model\ImageUploader`, base path `panth/megamenu/item`, jpg, jpeg, gif, png and webp). No controller currently uses it.
- Grid data sources: `panth_menu_listing_data_source` (`Model\ResourceModel\Menu\Grid\Collection`) and `panth_megamenu_item_listing_data_source` (virtual type over `panth_megamenu_item`).
- Routes: admin `panth_menu` (`etc/adminhtml/routes.xml`) and frontend `panth_menu` (`etc/frontend/routes.xml`).
- Admin controllers under `Controller\Adminhtml\Menu`: `Index`, `Edit`, `CustomEdit`, `Save`, `CustomSave`, `Delete`, `Duplicate`, `Toggle`, `Export`, `Import`, `ImportForm`, `ImportCategories`, `GetCategories`, `GetIcons`, `Upload`, `Validate`, `PreviewToken`, `Restore`, `VersionView`, `FlushCache`, `CreateDemoBlocks`; under `Controller\Adminhtml\Version`: `Restore`, `Export`, `Delete`. Frontend: `Controller\Preview\Index`.
- ACL resources: `Panth_MegaMenu::panth_extensions`, `Panth_MegaMenu::menu`, `Panth_MegaMenu::menu_items`, `Panth_MegaMenu::menu_save`, `Panth_MegaMenu::menu_delete`, `Panth_MegaMenu::menu_builder`, `Panth_MegaMenu::item_save`, `Panth_MegaMenu::item_delete`, `Panth_MegaMenu::menu_version_restore`, `Panth_MegaMenu::menu_version_view`, `Panth_MegaMenu::config`, `Panth_MegaMenu::design_config`.
- Database tables (`etc/db_schema.xml`): `panth_megamenu_menu` (menu records including `items_json` and container styling), `panth_megamenu_item` (normalised item rows with foreign keys to categories, CMS pages and products), `panth_megamenu_store` (menu to store view relation), `panth_megamenu_customer_group` (menu to customer group relation), `panth_megamenu_cache` (render cache rows keyed by menu, store and customer group), `panth_megamenu_menu_version` (version history). The storefront reads items from `items_json`.
- Cache tags: `panth_megamenu`, `panth_megamenu_<menu_id>`; model tags `panth_megamenu_menu`, `panth_megamenu_item`, `panth_megamenu_menu_version`.
- No EAV attributes, no `events.xml` and no cron jobs are declared. `Observer\ModuleInitialization`, `Model\InitFlag`, `Helper\Initialization` and `Helper\Webhook` ship with the module but are not referenced by any configuration file.

## Uninstallation

```bash
bin/magento module:disable Panth_MegaMenu
composer remove mage2kishan/module-mega-menu
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

After removal the six `panth_megamenu_*` tables, the configuration rows under `panth_megamenu/` in `core_config_data`, uploaded images in `pub/media/panth/megamenu/` and any demo CMS blocks remain and have to be dropped or deleted manually. No product or category attributes were added. `Panth_Core` stays installed if other packages depend on it.

## Support

- Product page: [Magento 2 Mega Menu](https://kishansavaliya.com/magento-2-mega-menu.html)
- Contact form: [kishansavaliya.com/contact](https://kishansavaliya.com/contact)
- Email: kishansavaliyakb@gmail.com
- Issues: [GitHub issues](https://github.com/mage2sk/module-mega-menu/issues)

## Documentation

[USER_GUIDE.md](USER_GUIDE.md) covers installation, checking that the module is active, the configuration screens, the menu builder, item types, column layouts, CMS block injection, mobile drawer behaviour, per-store menus, troubleshooting and a short CLI reference.

## License

Commercial software license. See [LICENSE.txt](LICENSE.txt) in this repository.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Links

- Website: [kishansavaliya.com](https://kishansavaliya.com)
- All extensions: [Magento extensions catalogue](https://kishansavaliya.com/magento-extensions.html)
- GitHub: [mage2sk/module-mega-menu](https://github.com/mage2sk/module-mega-menu)
- Packagist: [mage2kishan/module-mega-menu](https://packagist.org/packages/mage2kishan/module-mega-menu)
