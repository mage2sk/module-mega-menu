/**
 * General Tab Component for Menu Item Edit Form
 */
define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return {
        /**
         * Render General Tab Content
         */
        render: function(item) {
            // Escape function for HTML
            var escape = function(str) {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            };

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
                                   value="${escape(item.title)}"
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
                                       value="${escape(item.url || '')}"
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
                                   value="${escape(item.rel || '')}"
                                   placeholder="nofollow noopener" />
                            <p class="note"><span>${$t('e.g., nofollow, noopener, noreferrer')}</span></p>
                        </div>
                    </div>
                </div>
            `;
        }
    };
});
