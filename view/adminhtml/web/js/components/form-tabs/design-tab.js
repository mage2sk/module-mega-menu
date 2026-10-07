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
                        <label class="admin__field-label" for="item-image">
                            <span>${$t('Menu Item Image')}</span>
                        </label>
                        <div class="admin__field-control">
                            <div class="image-uploader-wrapper" style="margin-bottom: 10px;">
                                <!-- Image Preview -->
                                <div id="item-image-preview" style="margin-bottom: 10px; ${item.image ? '' : 'display: none;'}">
                                    <div style="position: relative; display: inline-block; border: 1px solid #ddd; padding: 5px; border-radius: 3px; background: #fafafa;">
                                        <img id="item-image-preview-img"
                                             src="${escape(item.image || '')}"
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
                                       value="${escape(item.image || '')}" />

                                <!-- Manual URL input (optional) -->
                                <div style="margin-top: 10px;">
                                    <label style="display: block; margin-bottom: 5px; font-size: 12px; color: #666;">
                                        ${$t('Or enter URL manually:')}
                                    </label>
                                    <input id="item-image-url"
                                           class="admin__control-text"
                                           type="text"
                                           value="${escape(item.image || '')}"
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
                        <label class="admin__field-label">
                            <span>${$t('Image Dimensions')}</span>
                        </label>
                        <div class="admin__field-control">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-size: 12px; color: #666;">
                                        ${$t('Width')}
                                    </label>
                                    <input id="item-image-width"
                                           class="admin__control-text"
                                           type="text"
                                           value="${escape(item.image_width || '')}"
                                           placeholder="200px or auto"
                                           style="width: 100%;" />
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-size: 12px; color: #666;">
                                        ${$t('Height')}
                                    </label>
                                    <input id="item-image-height"
                                           class="admin__control-text"
                                           type="text"
                                           value="${escape(item.image_height || '')}"
                                           placeholder="auto"
                                           style="width: 100%;" />
                                </div>
                            </div>
                            <p class="note">
                                <span>${$t('Enter dimensions with units (e.g., 200px, 100%, auto). Leave empty for default.')}</span>
                            </p>
                        </div>
                    </div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-display-type">
                            <span>${$t('Display Type')}</span>
                        </label>
                        <div class="admin__field-control">
                            <select id="item-display-type" class="admin__control-select">
                                <option value="text" ${item.display_type === 'text' ? 'selected' : ''}>${$t('Text Only')}</option>
                                <option value="icon-text" ${item.display_type === 'icon-text' ? 'selected' : ''}>${$t('Icon + Text')}</option>
                                <option value="image-text" ${item.display_type === 'image-text' ? 'selected' : ''}>${$t('Image + Text')}</option>
                                <option value="image-card" ${item.display_type === 'image-card' ? 'selected' : ''}>${$t('Image Card (with background)')}</option>
                                <option value="image-banner" ${item.display_type === 'image-banner' ? 'selected' : ''}>${$t('Image Banner (full width)')}</option>
                            </select>
                            <p class="note"><span>${$t('How the menu item should be displayed')}</span></p>
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
