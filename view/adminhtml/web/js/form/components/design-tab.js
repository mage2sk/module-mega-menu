/**
 * Design Tab Component for Menu Item Edit Form
 */
define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return {
        /**
         * Render Design Tab
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
                                       value="${escape(item.icon || '')}"
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
                                    </optgroup>
                                    <optgroup label="Bootstrap Icons">
                                        <option value="bi bi-house">bi bi-house</option>
                                        <option value="bi bi-cart">bi bi-cart</option>
                                    </optgroup>
                                    <optgroup label="Material Icons">
                                        <option value="material-icons">home</option>
                                        <option value="material-icons">shopping_cart</option>
                                    </optgroup>
                                </select>
                            </div>
                            <p class="note">
                                <span>${$t('Supported: Font Awesome, Bootstrap Icons, Material Icons, Line Icons')}</span>
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
                                   value="${escape(item.badge_text || '')}"
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
                                <option value="25" ${item.column_width == '25' ? 'selected' : ''}>${$t('25% (1/4)')}</option>
                                <option value="33" ${item.column_width == '33' ? 'selected' : ''}>${$t('33% (1/3)')}</option>
                                <option value="50" ${item.column_width == '50' ? 'selected' : ''}>${$t('50% (1/2)')}</option>
                                <option value="100" ${item.column_width == '100' ? 'selected' : ''}>${$t('100% (Full Width)')}</option>
                            </select>
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
                                   value="${escape(item.css_class || '')}"
                                   placeholder="my-custom-class" />
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
                                       value="${escape(item.bg_color || '#ffffff')}"
                                       style="width: 60px; height: 38px; border: 1px solid #adadad; border-radius: 3px; cursor: pointer;"
                                       onchange="document.getElementById('item-bg-color').value = this.value" />
                                <input id="item-bg-color"
                                       class="admin__control-text"
                                       type="text"
                                       value="${escape(item.bg_color || '')}"
                                       placeholder="#ffffff"
                                       style="flex: 1;"
                                       oninput="document.getElementById('item-bg-color-picker').value = this.value" />
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    };
});
