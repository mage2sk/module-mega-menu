/**
 * Comprehensive Menu Item Edit Form Generator
 */
define([
    'jquery',
    'mage/translate',
    'Panth_MegaMenu/js/components/form-tabs/general-tab',
    'Panth_MegaMenu/js/components/form-tabs/content-tab',
    'Panth_MegaMenu/js/components/form-tabs/design-tab'
], function ($, $t, generalTab, contentTab, designTab) {
    'use strict';

    return {
        /**
         * Generate comprehensive edit form with tabs
         */
        generateForm: function(item) {
            return `
                <div class="menu-item-edit-wrapper">
                    <!-- Tabs Navigation -->
                    <div class="menu-item-tabs">
                        <ul class="tab-headers">
                            <li class="tab-header active" data-tab="tab-general">${$t('General')}</li>
                            <li class="tab-header" data-tab="tab-content">${$t('Content')}</li>
                            <li class="tab-header" data-tab="tab-design">${$t('Design & Layout')}</li>
                            <li class="tab-header" data-tab="tab-advanced">${$t('Advanced')}</li>
                            <li class="tab-header" data-tab="tab-visibility">${$t('Visibility')}</li>
                        </ul>

                        <!-- General Tab -->
                        <div id="tab-general" class="tab-content active">
                            ${generalTab.render(item)}
                        </div>

                        <!-- Content Tab -->
                        <div id="tab-content" class="tab-content">
                            ${contentTab.render(item)}
                        </div>

                        <!-- Design & Layout Tab -->
                        <div id="tab-design" class="tab-content">
                            ${designTab.render(item)}
                        </div>

                        <!-- Advanced Tab -->
                        <div id="tab-advanced" class="tab-content">
                            ${this.generateAdvancedTab(item)}
                        </div>

                        <!-- Visibility Tab -->
                        <div id="tab-visibility" class="tab-content">
                            ${this.generateVisibilityTab(item)}
                        </div>
                    </div>
                </div>

                <style>
                    .menu-item-edit-wrapper {
                        min-height: 500px;
                    }

                    .menu-item-tabs .tab-headers {
                        list-style: none;
                        padding: 0;
                        margin: 0 0 20px 0;
                        border-bottom: 2px solid #ddd;
                        display: flex;
                    }

                    .menu-item-tabs .tab-header {
                        padding: 12px 20px;
                        cursor: pointer;
                        background: #f5f5f5;
                        border: 1px solid #ddd;
                        border-bottom: none;
                        margin-right: 5px;
                        border-radius: 4px 4px 0 0;
                        transition: all 0.3s;
                    }

                    .menu-item-tabs .tab-header:hover {
                        background: #e9e9e9;
                    }

                    .menu-item-tabs .tab-header.active {
                        background: #fff;
                        font-weight: 600;
                        color: #1979c3;
                        border-bottom-color: #fff;
                        margin-bottom: -2px;
                    }

                    .menu-item-tabs .tab-content {
                        display: none;
                        padding: 20px;
                        background: #fff;
                        border: 1px solid #ddd;
                        border-radius: 0 4px 4px 4px;
                    }

                    .menu-item-tabs .tab-content.active {
                        display: block;
                    }

                    .field-group {
                        margin-bottom: 30px;
                        padding-bottom: 20px;
                        border-bottom: 1px solid #e3e3e3;
                    }

                    .field-group:last-child {
                        border-bottom: none;
                    }

                    .field-group-title {
                        font-size: 14px;
                        font-weight: 600;
                        color: #333;
                        margin-bottom: 15px;
                        padding-bottom: 8px;
                        border-bottom: 1px solid #e8e8e8;
                    }

                    /* Required field asterisk */
                    .admin__field._required > .admin__field-label > span::after,
                    .admin__field-option._required > .admin__field-label > span::after {
                        content: '*';
                        color: #e02b27;
                        font-size: 1.2rem;
                        margin: 0 0 0 5px;
                        font-weight: normal;
                    }

                    .admin__field-label {
                        position: relative;
                        display: block;
                        margin-bottom: 8px;
                    }

                    .admin__field-control {
                        position: relative;
                    }
                </style>
            `;
        },

        /**
         * Generate General Tab
         */
        generateGeneralTab: function(item) {
            return `
                <div class="field-group">
                    <div class="field-group-title">${$t('Basic Information')}</div>

                    <div class="admin__field admin__field-option _required">
                        <label class="admin__field-label" for="item-title">
                            <span>${$t('Menu Item Title')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-title"
                                   class="admin__control-text"
                                   type="text"
                                   value="${this.escape(item.title)}"
                                   required />
                            <p class="note"><span>${$t('This will be displayed in the menu')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option _required">
                        <label class="admin__field-label" for="item-type">
                            <span>${$t('Link Type')}</span>
                        </label>
                        <div class="admin__field-control">
                            <select id="item-type" class="admin__control-select">
                                <optgroup label="${$t('Basic')}">
                                    <option value="link" ${item.item_type === 'link' ? 'selected' : ''}>${$t('Custom URL')}</option>
                                    <option value="dropdown" ${item.item_type === 'dropdown' ? 'selected' : ''}>${$t('Dropdown Parent (No Link)')}</option>
                                    <option value="divider" ${item.item_type === 'divider' ? 'selected' : ''}>${$t('Separator / Divider')}</option>
                                </optgroup>

                                <optgroup label="${$t('Catalog')}">
                                    <option value="category" ${item.item_type === 'category' ? 'selected' : ''}>${$t('Link to Category')}</option>
                                    <option value="product" ${item.item_type === 'product' ? 'selected' : ''}>${$t('Link to Product')}</option>
                                </optgroup>

                                <optgroup label="${$t('Content Pages')}">
                                    <option value="cms_page" ${item.item_type === 'cms_page' ? 'selected' : ''}>${$t('CMS Page')}</option>
                                    <option value="cms_block" ${item.item_type === 'cms_block' ? 'selected' : ''}>${$t('Static Block (CMS)')}</option>
                                    <option value="custom_html" ${item.item_type === 'custom_html' ? 'selected' : ''}>${$t('Custom HTML')}</option>
                                    <option value="widget" ${item.item_type === 'widget' ? 'selected' : ''}>${$t('Widget Code')}</option>
                                </optgroup>

                                <optgroup label="${$t('Customer Account')}">
                                    <option value="account" ${item.item_type === 'account' ? 'selected' : ''}>${$t('My Account Dashboard')}</option>
                                    <option value="account_orders" ${item.item_type === 'account_orders' ? 'selected' : ''}>${$t('My Orders')}</option>
                                    <option value="account_wishlist" ${item.item_type === 'account_wishlist' ? 'selected' : ''}>${$t('My Wishlist')}</option>
                                    <option value="account_addresses" ${item.item_type === 'account_addresses' ? 'selected' : ''}>${$t('Address Book')}</option>
                                    <option value="account_edit" ${item.item_type === 'account_edit' ? 'selected' : ''}>${$t('Account Information')}</option>
                                </optgroup>

                                <optgroup label="${$t('Store Pages')}">
                                    <option value="contact" ${item.item_type === 'contact' ? 'selected' : ''}>${$t('Contact Us')}</option>
                                    <option value="about" ${item.item_type === 'about' ? 'selected' : ''}>${$t('About Us')}</option>
                                    <option value="faq" ${item.item_type === 'faq' ? 'selected' : ''}>${$t('FAQ')}</option>
                                    <option value="blog" ${item.item_type === 'blog' ? 'selected' : ''}>${$t('Blog')}</option>
                                </optgroup>
                            </select>
                            <p class="note"><span>${$t('Select the type of content or link this menu item should point to')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option" id="url-field-container">
                        <label class="admin__field-label" for="item-url">
                            <span>${$t('URL / Link')}</span>
                        </label>
                        <div class="admin__field-control">
                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <input id="item-url"
                                       class="admin__control-text"
                                       type="text"
                                       value="${this.escape(item.url || '')}"
                                       placeholder="Enter URL or click Browse to select content"
                                       style="flex: 1;" />
                                <button type="button"
                                        id="browse-content-btn"
                                        class="action-default"
                                        title="${$t('Browse Categories, Products, Pages & Blocks')}">
                                    <span>${$t('Browse...')}</span>
                                </button>
                            </div>

                            <p class="note" id="url-field-note">
                                <span>${$t('Enter URL manually or click Browse to search categories, products, CMS pages/blocks')}</span>
                            </p>
                        </div>
                    </div>

                </div>

                <div class="field-group">
                    <div class="field-group-title">${$t('Link Behavior')}</div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-target">
                            <span>${$t('Open Link In')}</span>
                        </label>
                        <div class="admin__field-control">
                            <select id="item-target" class="admin__control-select">
                                <option value="_self" ${item.target === '_self' ? 'selected' : ''}>${$t('Same Window')}</option>
                                <option value="_blank" ${item.target === '_blank' ? 'selected' : ''}>${$t('New Window/Tab')}</option>
                                <option value="_parent" ${item.target === '_parent' ? 'selected' : ''}>${$t('Parent Frame')}</option>
                            </select>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-rel">
                            <span>${$t('Rel Attribute')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-rel"
                                   class="admin__control-text"
                                   type="text"
                                   value="${this.escape(item.rel || '')}"
                                   placeholder="nofollow noopener" />
                            <p class="note"><span>${$t('e.g., nofollow, noopener, noreferrer')}</span></p>
                        </div>
                    </div>
                </div>
            `;
        },

        /**
         * Generate Content Tab
         */
        generateContentTab: function(item) {
            return `
                <div class="field-group">
                    <div class="field-group-title">${$t('HTML Content')}</div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-custom-content">
                            <span>${$t('Custom HTML Content')}</span>
                        </label>
                        <div class="admin__field-control">
                            <textarea id="item-custom-content"
                                      class="admin__control-textarea"
                                      rows="15"
                                      style="font-family: 'Courier New', Consolas, monospace; font-size: 13px; width: 100%;">${this.escape(item.custom_content || '')}</textarea>
                            <p class="note"><span>${$t('Enter HTML content for mega menu blocks. Use Bootstrap classes for styling.')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-cms-block">
                            <span>${$t('CMS Block Identifier')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-cms-block"
                                   class="admin__control-text"
                                   type="text"
                                   value="${this.escape(item.cms_block || '')}"
                                   placeholder="my_cms_block" />
                            <p class="note"><span>${$t('Identifier of CMS block to display in mega menu')}</span></p>
                        </div>
                    </div>
                </div>

            `;
        },

        /**
         * Generate Design Tab
         */
        generateDesignTab: function(item) {
            return `
                <div class="field-group">
                    <div class="field-group-title">${$t('Icons & Images')}</div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-icon">
                            <span>${$t('Icon Class')}</span>
                        </label>
                        <div class="admin__field-control">
                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <input id="item-icon"
                                       class="admin__control-text"
                                       type="text"
                                       value="${this.escape(item.icon || '')}"
                                       placeholder="fa fa-home"
                                       style="flex: 1;" />
                                <select class="admin__control-select" onchange="this.previousElementSibling.value = this.value" style="width: 200px;">
                                    <option value="">${$t('Select Icon Library')}</option>
                                    <optgroup label="Font Awesome">
                                        <option value="fa fa-home">fa fa-home</option>
                                        <option value="fa fa-shopping-cart">fa fa-shopping-cart</option>
                                        <option value="fa fa-user">fa fa-user</option>
                                        <option value="fa fa-search">fa fa-search</option>
                                        <option value="fa fa-heart">fa fa-heart</option>
                                        <option value="fa fa-star">fa fa-star</option>
                                        <option value="fa fa-envelope">fa fa-envelope</option>
                                        <option value="fa fa-phone">fa fa-phone</option>
                                    </optgroup>
                                    <optgroup label="Bootstrap Icons">
                                        <option value="bi bi-house">bi bi-house</option>
                                        <option value="bi bi-cart">bi bi-cart</option>
                                        <option value="bi bi-person">bi bi-person</option>
                                        <option value="bi bi-search">bi bi-search</option>
                                        <option value="bi bi-heart">bi bi-heart</option>
                                        <option value="bi bi-star">bi bi-star</option>
                                        <option value="bi bi-envelope">bi bi-envelope</option>
                                        <option value="bi bi-telephone">bi bi-telephone</option>
                                        <option value="bi bi-box">bi bi-box</option>
                                        <option value="bi bi-gift">bi bi-gift</option>
                                        <option value="bi bi-tag">bi bi-tag</option>
                                        <option value="bi bi-grid">bi bi-grid</option>
                                    </optgroup>
                                    <optgroup label="Material Icons">
                                        <option value="material-icons">home</option>
                                        <option value="material-icons">shopping_cart</option>
                                        <option value="material-icons">person</option>
                                        <option value="material-icons">search</option>
                                        <option value="material-icons">favorite</option>
                                    </optgroup>
                                    <optgroup label="Line Icons">
                                        <option value="lni lni-home">lni lni-home</option>
                                        <option value="lni lni-cart">lni lni-cart</option>
                                        <option value="lni lni-user">lni lni-user</option>
                                    </optgroup>
                                </select>
                            </div>
                            <p class="note">
                                <span>${$t('Supported: Font Awesome (fa fa-*), Bootstrap Icons (bi bi-*), Material Icons, Line Icons (lni lni-*)')}</span><br/>
                                <span>${$t('Use the dropdown for quick selection or type custom icon class')}</span>
                            </p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-image">
                            <span>${$t('Menu Item Image')}</span>
                        </label>
                        <div class="admin__field-control">
                            <div class="image-uploader-wrapper" style="margin-bottom: 10px;">
                                <!-- Image Preview -->
                                <div id="item-image-preview" style="margin-bottom: 10px; ${item.image ? '' : 'display: none;'}">
                                    <div style="position: relative; display: inline-block; border: 1px solid #ddd; padding: 5px; border-radius: 3px; background: #fafafa;">
                                        <img id="item-image-preview-img"
                                             src="${this.escape(item.image || '')}"
                                             alt="Menu Item Image"
                                             style="max-width: 200px; max-height: 200px; display: block;" />
                                        <button type="button"
                                                class="action-secondary action-remove"
                                                id="item-image-remove"
                                                style="position: absolute; top: 0; right: 0; margin: 5px; padding: 2px 8px; background: #e02b27; color: white; border: none; cursor: pointer; border-radius: 3px;"
                                                title="${$t('Remove Image')}">
                                            <span>×</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Upload Controls -->
                                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                                    <input type="file"
                                           id="item-image-file"
                                           accept="image/*"
                                           style="display: none;" />
                                    <button type="button"
                                            class="action-secondary"
                                            id="item-image-upload-btn"
                                            style="white-space: nowrap;">
                                        <span>${$t('Choose Image')}</span>
                                    </button>
                                    <span id="item-image-filename" style="color: #666; font-size: 12px;"></span>
                                    <div id="item-image-progress" style="display: none; flex: 1;">
                                        <div style="background: #e5e5e5; height: 4px; border-radius: 2px; overflow: hidden;">
                                            <div id="item-image-progress-bar" style="background: #1979c3; height: 100%; width: 0%; transition: width 0.3s;"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Hidden field to store image path -->
                                <input id="item-image"
                                       type="hidden"
                                       value="${this.escape(item.image || '')}" />

                                <!-- Manual URL input (optional) -->
                                <div style="margin-top: 10px;">
                                    <label style="display: block; margin-bottom: 5px; font-size: 12px; color: #666;">
                                        ${$t('Or enter URL manually:')}
                                    </label>
                                    <input id="item-image-url"
                                           class="admin__control-text"
                                           type="text"
                                           value="${this.escape(item.image || '')}"
                                           placeholder="https://example.com/image.jpg or /pub/media/image.jpg"
                                           style="font-size: 12px;" />
                                </div>
                            </div>
                            <p class="note">
                                <span>${$t('Upload an image for mega menu columns or menu item icon. Supported formats: JPG, PNG, GIF, SVG, WebP')}</span>
                            </p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-badge">
                            <span>${$t('Badge / Label')}</span>
                        </label>
                        <div class="admin__field-control">
                            <select id="item-badge" class="admin__control-select">
                                <option value="" ${!item.badge ? 'selected' : ''}>${$t('None')}</option>
                                <option value="new" ${item.badge === 'new' ? 'selected' : ''}>${$t('New')}</option>
                                <option value="hot" ${item.badge === 'hot' ? 'selected' : ''}>${$t('Hot')}</option>
                                <option value="sale" ${item.badge === 'sale' ? 'selected' : ''}>${$t('Sale')}</option>
                                <option value="featured" ${item.badge === 'featured' ? 'selected' : ''}>${$t('Featured')}</option>
                                <option value="custom" ${item.badge === 'custom' ? 'selected' : ''}>${$t('Custom')}</option>
                            </select>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-badge-text">
                            <span>${$t('Custom Badge Text')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-badge-text"
                                   class="admin__control-text"
                                   type="text"
                                   value="${this.escape(item.badge_text || '')}"
                                   placeholder="Custom Label" />
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <div class="field-group-title">${$t('Layout & Styling')}</div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-column-width">
                            <span>${$t('Column Width (Mega Menu)')}</span>
                        </label>
                        <div class="admin__field-control">
                            <select id="item-column-width" class="admin__control-select">
                                <option value="" ${!item.column_width ? 'selected' : ''}>${$t('Auto / Default')}</option>
                                <option value="20" ${item.column_width == '20' ? 'selected' : ''}>${$t('20% (1/5)')}</option>
                                <option value="25" ${item.column_width == '25' ? 'selected' : ''}>${$t('25% (1/4)')}</option>
                                <option value="33" ${item.column_width == '33' ? 'selected' : ''}>${$t('33% (1/3)')}</option>
                                <option value="50" ${item.column_width == '50' ? 'selected' : ''}>${$t('50% (1/2)')}</option>
                                <option value="66" ${item.column_width == '66' ? 'selected' : ''}>${$t('66% (2/3)')}</option>
                                <option value="75" ${item.column_width == '75' ? 'selected' : ''}>${$t('75% (3/4)')}</option>
                                <option value="100" ${item.column_width == '100' ? 'selected' : ''}>${$t('100% (Full Width)')}</option>
                            </select>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-submenu-display">
                            <span>${$t('Submenu Display Type')}</span>
                        </label>
                        <div class="admin__field-control">
                            <select id="item-submenu-display" class="admin__control-select">
                                <option value="dropdown" ${(!item.submenu_display || item.submenu_display === 'dropdown') ? 'selected' : ''}>${$t('Dropdown (Hover - Below)')}</option>
                                <option value="flyout-right" ${item.submenu_display === 'flyout-right' ? 'selected' : ''}>${$t('Flyout (Hover - Right Side)')}</option>
                                <option value="flyout-left" ${item.submenu_display === 'flyout-left' ? 'selected' : ''}>${$t('Flyout (Hover - Left Side)')}</option>
                                <option value="accordion" ${item.submenu_display === 'accordion' ? 'selected' : ''}>${$t('Accordion (Click to Expand)')}</option>
                            </select>
                            <p class="note"><span>${$t('How child items appear in the menu (applies to level 2+)')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-css-class">
                            <span>${$t('CSS Classes')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-css-class"
                                   class="admin__control-text"
                                   type="text"
                                   value="${this.escape(item.css_class || '')}"
                                   placeholder="my-custom-class another-class" />
                            <p class="note"><span>${$t('Space-separated CSS classes')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-bg-color">
                            <span>${$t('Background Color')}</span>
                        </label>
                        <div class="admin__field-control">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input id="item-bg-color-picker"
                                       type="color"
                                       value="${this.escape(item.bg_color || '#ffffff')}"
                                       style="width: 60px; height: 38px; border: 1px solid #adadad; border-radius: 3px; cursor: pointer;"
                                       onchange="document.getElementById('item-bg-color').value = this.value" />
                                <input id="item-bg-color"
                                       class="admin__control-text"
                                       type="text"
                                       value="${this.escape(item.bg_color || '')}"
                                       placeholder="#ffffff"
                                       style="flex: 1;"
                                       oninput="document.getElementById('item-bg-color-picker').value = this.value" />
                                <button type="button"
                                        class="action-secondary"
                                        onclick="document.getElementById('item-bg-color').value = ''; document.getElementById('item-bg-color-picker').value = '#ffffff';"
                                        title="Clear">
                                    <span>Clear</span>
                                </button>
                            </div>
                            <p class="note"><span>${$t('Pick a color or enter hex code (e.g., #f5f5f5)')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-text-color">
                            <span>${$t('Text Color')}</span>
                        </label>
                        <div class="admin__field-control">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input id="item-text-color-picker"
                                       type="color"
                                       value="${this.escape(item.text_color || '#333333')}"
                                       style="width: 60px; height: 38px; border: 1px solid #adadad; border-radius: 3px; cursor: pointer;"
                                       onchange="document.getElementById('item-text-color').value = this.value" />
                                <input id="item-text-color"
                                       class="admin__control-text"
                                       type="text"
                                       value="${this.escape(item.text_color || '')}"
                                       placeholder="#333333"
                                       style="flex: 1;"
                                       oninput="document.getElementById('item-text-color-picker').value = this.value" />
                                <button type="button"
                                        class="action-secondary"
                                        onclick="document.getElementById('item-text-color').value = ''; document.getElementById('item-text-color-picker').value = '#333333';"
                                        title="Clear">
                                    <span>Clear</span>
                                </button>
                            </div>
                            <p class="note"><span>${$t('Pick a color or enter hex code (e.g., #333333)')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label">
                            <span>${$t('Spacing & Dimensions')}</span>
                        </label>
                        <div class="admin__field-control">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 12px;">${$t('Padding')}</label>
                                    <input id="item-padding"
                                           class="admin__control-text"
                                           type="text"
                                           value="${this.escape(item.padding || '')}"
                                           placeholder="10px 15px" />
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 12px;">${$t('Margin')}</label>
                                    <input id="item-margin"
                                           class="admin__control-text"
                                           type="text"
                                           value="${this.escape(item.margin || '')}"
                                           placeholder="0 10px" />
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 12px;">${$t('Width')}</label>
                                    <input id="item-width"
                                           class="admin__control-text"
                                           type="text"
                                           value="${this.escape(item.width || '')}"
                                           placeholder="auto or 200px" />
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 12px;">${$t('Height')}</label>
                                    <input id="item-height"
                                           class="admin__control-text"
                                           type="text"
                                           value="${this.escape(item.height || '')}"
                                           placeholder="auto or 50px" />
                                </div>
                            </div>
                            <p class="note"><span>${$t('CSS values: padding, margin, width, height (e.g., 10px, 1em, 50%)')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-border">
                            <span>${$t('Border Style')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-border"
                                   class="admin__control-text"
                                   type="text"
                                   value="${this.escape(item.border || '')}"
                                   placeholder="1px solid #ddd" />
                            <p class="note"><span>${$t('CSS border value (e.g., 1px solid #ddd, 2px dashed red)')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-border-radius">
                            <span>${$t('Border Radius')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-border-radius"
                                   class="admin__control-text"
                                   type="text"
                                   value="${this.escape(item.border_radius || '')}"
                                   placeholder="4px or 50%" />
                            <p class="note"><span>${$t('Rounded corners (e.g., 4px, 10px, 50% for circle)')}</span></p>
                        </div>
                    </div>
                </div>
            `;
        },

        /**
         * Generate Advanced Tab
         */
        generateAdvancedTab: function(item) {
            return `
                <div class="field-group">
                    <div class="field-group-title">${$t('Animation & Effects')}</div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-animation">
                            <span>${$t('Animation Effect')}</span>
                        </label>
                        <div class="admin__field-control">
                            <select id="item-animation" class="admin__control-select">
                                <option value="" ${!item.animation ? 'selected' : ''}>${$t('None')}</option>
                                <option value="fadeIn" ${item.animation === 'fadeIn' ? 'selected' : ''}>${$t('Fade In')}</option>
                                <option value="fadeInUp" ${item.animation === 'fadeInUp' ? 'selected' : ''}>${$t('Fade In Up')}</option>
                                <option value="fadeInDown" ${item.animation === 'fadeInDown' ? 'selected' : ''}>${$t('Fade In Down')}</option>
                                <option value="slideInLeft" ${item.animation === 'slideInLeft' ? 'selected' : ''}>${$t('Slide In Left')}</option>
                                <option value="slideInRight" ${item.animation === 'slideInRight' ? 'selected' : ''}>${$t('Slide In Right')}</option>
                                <option value="zoomIn" ${item.animation === 'zoomIn' ? 'selected' : ''}>${$t('Zoom In')}</option>
                                <option value="bounceIn" ${item.animation === 'bounceIn' ? 'selected' : ''}>${$t('Bounce In')}</option>
                            </select>
                            <p class="note"><span>${$t('Animation when menu item appears (requires animation library like Animate.css)')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-hover-effect">
                            <span>${$t('Hover Effect')}</span>
                        </label>
                        <div class="admin__field-control">
                            <select id="item-hover-effect" class="admin__control-select">
                                <option value="" ${!item.hover_effect ? 'selected' : ''}>${$t('Default')}</option>
                                <option value="underline" ${item.hover_effect === 'underline' ? 'selected' : ''}>${$t('Underline')}</option>
                                <option value="background-change" ${item.hover_effect === 'background-change' ? 'selected' : ''}>${$t('Background Change')}</option>
                                <option value="scale" ${item.hover_effect === 'scale' ? 'selected' : ''}>${$t('Scale / Grow')}</option>
                                <option value="shadow" ${item.hover_effect === 'shadow' ? 'selected' : ''}>${$t('Shadow')}</option>
                                <option value="glow" ${item.hover_effect === 'glow' ? 'selected' : ''}>${$t('Glow')}</option>
                            </select>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-hover-bg-color">
                            <span>${$t('Hover Background Color')}</span>
                        </label>
                        <div class="admin__field-control">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="color"
                                       value="${this.escape(item.hover_bg_color || '#f0f0f0')}"
                                       style="width: 60px; height: 38px; border: 1px solid #adadad; border-radius: 3px; cursor: pointer;"
                                       onchange="document.getElementById('item-hover-bg-color').value = this.value" />
                                <input id="item-hover-bg-color"
                                       class="admin__control-text"
                                       type="text"
                                       value="${this.escape(item.hover_bg_color || '')}"
                                       placeholder="#f0f0f0"
                                       style="flex: 1;" />
                            </div>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-hover-text-color">
                            <span>${$t('Hover Text Color')}</span>
                        </label>
                        <div class="admin__field-control">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="color"
                                       value="${this.escape(item.hover_text_color || '#000000')}"
                                       style="width: 60px; height: 38px; border: 1px solid #adadad; border-radius: 3px; cursor: pointer;"
                                       onchange="document.getElementById('item-hover-text-color').value = this.value" />
                                <input id="item-hover-text-color"
                                       class="admin__control-text"
                                       type="text"
                                       value="${this.escape(item.hover_text_color || '')}"
                                       placeholder="#000000"
                                       style="flex: 1;" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <div class="field-group-title">${$t('Typography')}</div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-font-family">
                            <span>${$t('Font Family')}</span>
                        </label>
                        <div class="admin__field-control">
                            <select id="item-font-family" class="admin__control-select">
                                <option value="" ${!item.font_family ? 'selected' : ''}>${$t('Default / Inherit')}</option>
                                <option value="Arial, sans-serif" ${item.font_family === 'Arial, sans-serif' ? 'selected' : ''}>Arial</option>
                                <option value="Helvetica, sans-serif" ${item.font_family === 'Helvetica, sans-serif' ? 'selected' : ''}>Helvetica</option>
                                <option value="'Times New Roman', serif" ${item.font_family === "'Times New Roman', serif" ? 'selected' : ''}>Times New Roman</option>
                                <option value="Georgia, serif" ${item.font_family === 'Georgia, serif' ? 'selected' : ''}>Georgia</option>
                                <option value="'Courier New', monospace" ${item.font_family === "'Courier New', monospace" ? 'selected' : ''}>Courier New</option>
                                <option value="'Roboto', sans-serif" ${item.font_family === "'Roboto', sans-serif" ? 'selected' : ''}>Roboto</option>
                                <option value="'Open Sans', sans-serif" ${item.font_family === "'Open Sans', sans-serif" ? 'selected' : ''}>Open Sans</option>
                                <option value="'Lato', sans-serif" ${item.font_family === "'Lato', sans-serif" ? 'selected' : ''}>Lato</option>
                            </select>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-font-size">
                            <span>${$t('Font Size')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-font-size"
                                   class="admin__control-text"
                                   type="text"
                                   value="${this.escape(item.font_size || '')}"
                                   placeholder="14px or 1rem" />
                            <p class="note"><span>${$t('e.g., 14px, 1rem, 1.2em')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-font-weight">
                            <span>${$t('Font Weight')}</span>
                        </label>
                        <div class="admin__field-control">
                            <select id="item-font-weight" class="admin__control-select">
                                <option value="" ${!item.font_weight ? 'selected' : ''}>${$t('Default')}</option>
                                <option value="300" ${item.font_weight === '300' ? 'selected' : ''}>300 - Light</option>
                                <option value="400" ${item.font_weight === '400' ? 'selected' : ''}>400 - Normal</option>
                                <option value="500" ${item.font_weight === '500' ? 'selected' : ''}>500 - Medium</option>
                                <option value="600" ${item.font_weight === '600' ? 'selected' : ''}>600 - Semi Bold</option>
                                <option value="700" ${item.font_weight === '700' ? 'selected' : ''}>700 - Bold</option>
                                <option value="800" ${item.font_weight === '800' ? 'selected' : ''}>800 - Extra Bold</option>
                            </select>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-text-transform">
                            <span>${$t('Text Transform')}</span>
                        </label>
                        <div class="admin__field-control">
                            <select id="item-text-transform" class="admin__control-select">
                                <option value="" ${!item.text_transform ? 'selected' : ''}>${$t('None')}</option>
                                <option value="uppercase" ${item.text_transform === 'uppercase' ? 'selected' : ''}>${$t('UPPERCASE')}</option>
                                <option value="lowercase" ${item.text_transform === 'lowercase' ? 'selected' : ''}>${$t('lowercase')}</option>
                                <option value="capitalize" ${item.text_transform === 'capitalize' ? 'selected' : ''}>${$t('Capitalize')}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <div class="field-group-title">${$t('Spacing & Layout')}</div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-padding">
                            <span>${$t('Padding')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-padding"
                                   class="admin__control-text"
                                   type="text"
                                   value="${this.escape(item.padding || '')}"
                                   placeholder="10px 20px or 15px" />
                            <p class="note"><span>${$t('CSS padding value (e.g., 10px 20px, 15px, 10px 20px 10px 20px)')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-margin">
                            <span>${$t('Margin')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-margin"
                                   class="admin__control-text"
                                   type="text"
                                   value="${this.escape(item.margin || '')}"
                                   placeholder="0 10px or 5px" />
                            <p class="note"><span>${$t('CSS margin value (e.g., 0 10px, 5px, 0 5px 0 10px)')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-gap">
                            <span>${$t('Gap Between Child Items')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-gap"
                                   class="admin__control-text"
                                   type="text"
                                   value="${this.escape(item.gap || '')}"
                                   placeholder="10px or 1rem" />
                            <p class="note"><span>${$t('Gap between submenu items (e.g., 10px, 1rem, 0.5em)')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-border-radius">
                            <span>${$t('Border Radius')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-border-radius"
                                   class="admin__control-text"
                                   type="text"
                                   value="${this.escape(item.border_radius || '')}"
                                   placeholder="4px or 50%" />
                            <p class="note"><span>${$t('CSS border-radius value (e.g., 4px, 50%, 10px 20px)')}</span></p>
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <div class="field-group-title">${$t('Shadow & Effects')}</div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-box-shadow">
                            <span>${$t('Box Shadow')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-box-shadow"
                                   class="admin__control-text"
                                   type="text"
                                   value="${this.escape(item.box_shadow || '')}"
                                   placeholder="0 2px 4px rgba(0,0,0,0.1)" />
                            <p class="note"><span>${$t('CSS box-shadow value (e.g., 0 2px 4px rgba(0,0,0,0.1))')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-text-shadow">
                            <span>${$t('Text Shadow')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-text-shadow"
                                   class="admin__control-text"
                                   type="text"
                                   value="${this.escape(item.text_shadow || '')}"
                                   placeholder="1px 1px 2px rgba(0,0,0,0.5)" />
                            <p class="note"><span>${$t('CSS text-shadow value')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-opacity">
                            <span>${$t('Opacity')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-opacity"
                                   class="admin__control-text"
                                   type="number"
                                   min="0"
                                   max="1"
                                   step="0.1"
                                   value="${this.escape(item.opacity || '1')}"
                                   placeholder="1" />
                            <p class="note"><span>${$t('0 = invisible, 1 = fully visible')}</span></p>
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <div class="field-group-title">${$t('Behavior & Interaction')}</div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-tooltip">
                            <span>${$t('Tooltip Text')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-tooltip"
                                   class="admin__control-text"
                                   type="text"
                                   value="${this.escape(item.tooltip || '')}" />
                            <p class="note"><span>${$t('Text shown on hover')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-click-action">
                            <span>${$t('Custom Click Action (JavaScript)')}</span>
                        </label>
                        <div class="admin__field-control">
                            <textarea id="item-click-action"
                                      class="admin__control-textarea"
                                      rows="3"
                                      style="font-family: monospace;">${this.escape(item.click_action || '')}</textarea>
                            <p class="note"><span>${$t('JavaScript code to execute on click (advanced)')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-data-attributes">
                            <span>${$t('Custom Data Attributes')}</span>
                        </label>
                        <div class="admin__field-control">
                            <textarea id="item-data-attributes"
                                      class="admin__control-textarea"
                                      rows="4"
                                      placeholder='{"data-category": "electronics", "data-tracking": "menu-click"}'>${this.escape(item.data_attributes || '')}</textarea>
                            <p class="note"><span>${$t('JSON object of data attributes to add to menu item')}</span></p>
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <div class="field-group-title">${$t('Accessibility')}</div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-aria-label">
                            <span>${$t('ARIA Label')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-aria-label"
                                   class="admin__control-text"
                                   type="text"
                                   value="${this.escape(item.aria_label || '')}" />
                            <p class="note"><span>${$t('Accessible label for screen readers')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-role">
                            <span>${$t('ARIA Role')}</span>
                        </label>
                        <div class="admin__field-control">
                            <select id="item-role" class="admin__control-select">
                                <option value="" ${!item.role ? 'selected' : ''}>${$t('Default')}</option>
                                <option value="menuitem" ${item.role === 'menuitem' ? 'selected' : ''}>menuitem</option>
                                <option value="button" ${item.role === 'button' ? 'selected' : ''}>button</option>
                                <option value="link" ${item.role === 'link' ? 'selected' : ''}>link</option>
                                <option value="navigation" ${item.role === 'navigation' ? 'selected' : ''}>navigation</option>
                            </select>
                        </div>
                    </div>
                </div>
            `;
        },

        /**
         * Generate Visibility Tab
         */
        generateVisibilityTab: function(item) {
            return `
                <div class="field-group">
                    <div class="field-group-title">${$t('Display Status')}</div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-active">
                            <span>${$t('Enable Menu Item')}</span>
                        </label>
                        <div class="admin__field-control">
                            <select id="item-active" class="admin__control-select">
                                <option value="1" ${item.is_active == 1 || item.is_active === true ? 'selected' : ''}>${$t('Yes')}</option>
                                <option value="0" ${item.is_active == 0 || item.is_active === false ? 'selected' : ''}>${$t('No')}</option>
                            </select>
                            <p class="note"><span>${$t('Set to "No" to hide this menu item without deleting it')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-sort-order">
                            <span>${$t('Sort Order')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-sort-order"
                                   class="admin__control-text"
                                   type="number"
                                   value="${this.escape(item.sort_order || item.position || '0')}"
                                   min="0"
                                   step="1" />
                            <p class="note"><span>${$t('Lower numbers appear first. Use 0 for default order.')}</span></p>
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <div class="field-group-title">${$t('Device Visibility')}</div>
                    <div class="admin__field-note" style="margin-bottom: 15px;">
                        <span>${$t('Control which devices can see this menu item')}</span>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-show-desktop">
                            <span>${$t('Show on Desktop')} (> 1024px)</span>
                        </label>
                        <div class="admin__field-control">
                            <select id="item-show-desktop" class="admin__control-select">
                                <option value="1" ${item.show_on_desktop == 1 || item.show_on_desktop === true || item.show_on_desktop === undefined ? 'selected' : ''}>${$t('Yes')}</option>
                                <option value="0" ${item.show_on_desktop == 0 || item.show_on_desktop === false ? 'selected' : ''}>${$t('No')}</option>
                            </select>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-show-tablet">
                            <span>${$t('Show on Tablet')} (768px - 1024px)</span>
                        </label>
                        <div class="admin__field-control">
                            <select id="item-show-tablet" class="admin__control-select">
                                <option value="1" ${item.show_on_tablet == 1 || item.show_on_tablet === true || item.show_on_tablet === undefined ? 'selected' : ''}>${$t('Yes')}</option>
                                <option value="0" ${item.show_on_tablet == 0 || item.show_on_tablet === false ? 'selected' : ''}>${$t('No')}</option>
                            </select>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-show-mobile">
                            <span>${$t('Show on Mobile')} (< 768px)</span>
                        </label>
                        <div class="admin__field-control">
                            <select id="item-show-mobile" class="admin__control-select">
                                <option value="1" ${item.show_on_mobile == 1 || item.show_on_mobile === true || item.show_on_mobile === undefined ? 'selected' : ''}>${$t('Yes')}</option>
                                <option value="0" ${item.show_on_mobile == 0 || item.show_on_mobile === false ? 'selected' : ''}>${$t('No')}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <div class="field-group-title">${$t('Store View Visibility')}</div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-store-views">
                            <span>${$t('Visible on Store Views')}</span>
                        </label>
                        <div class="admin__field-control">
                            <select id="item-store-views" class="admin__control-multiselect" multiple style="min-height: 100px;">
                                <option value="0" ${this.isSelected(item.store_views, '0')}>${$t('All Store Views')}</option>
                                <option value="1" ${this.isSelected(item.store_views, '1')}>${$t('Default Store View')}</option>
                            </select>
                            <p class="note"><span>${$t('Select which store views should display this item. Leave empty or select "All" for all store views.')}</span></p>
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <div class="field-group-title">${$t('Schedule Visibility')}</div>
                    <div class="admin__field-note" style="margin-bottom: 15px;">
                        <span>${$t('Set a date range for when this menu item should be visible')}</span>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-start-date">
                            <span>${$t('Start Date')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-start-date"
                                   class="admin__control-text"
                                   type="datetime-local"
                                   value="${this.escape(item.start_date || '')}" />
                            <p class="note"><span>${$t('Item will become visible from this date/time. Leave empty to show immediately.')}</span></p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-end-date">
                            <span>${$t('End Date')}</span>
                        </label>
                        <div class="admin__field-control">
                            <input id="item-end-date"
                                   class="admin__control-text"
                                   type="datetime-local"
                                   value="${this.escape(item.end_date || '')}" />
                            <p class="note"><span>${$t('Item will be hidden after this date/time. Leave empty to show permanently.')}</span></p>
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <div class="field-group-title">${$t('Customer Group Restrictions')}</div>
                    <div class="admin__field-note" style="margin-bottom: 15px;">
                        <span>${$t('Control which customer groups can see this menu item')}</span>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-customer-groups">
                            <span>${$t('Visible to Customer Groups')}</span>
                        </label>
                        <div class="admin__field-control">
                            <select id="item-customer-groups" class="admin__control-multiselect" multiple style="min-height: 120px;">
                                <option value="all" ${this.isSelected(item.customer_groups, 'all')}>${$t('All Customer Groups')}</option>
                                <option value="0" ${this.isSelected(item.customer_groups, '0')}>${$t('NOT LOGGED IN')}</option>
                                <option value="1" ${this.isSelected(item.customer_groups, '1')}>${$t('General')}</option>
                                <option value="2" ${this.isSelected(item.customer_groups, '2')}>${$t('Wholesale')}</option>
                                <option value="3" ${this.isSelected(item.customer_groups, '3')}>${$t('Retailer')}</option>
                            </select>
                            <p class="note"><span>${$t('Hold Ctrl/Cmd to select multiple groups. Leave empty or select "All" to show to all customers.')}</span></p>
                        </div>
                    </div>
                </div>
            `;
        },

        /**
         * Escape HTML
         */
        escape: function(text) {
            if (!text) return '';
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        /**
         * Check if value is selected in array
         */
        isSelected: function(arr, value) {
            if (!arr) return '';
            if (typeof arr === 'string') arr = arr.split(',');
            return arr.indexOf(value) !== -1 ? 'selected' : '';
        }
    };
});
