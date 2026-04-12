/**
 * Content Tab Component for Menu Item Edit Form
 */
define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return {
        /**
         * Render Content Tab
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
                    <div class="field-group-title">${$t('HTML Content')}</div>

                    <div class="admin__field admin__field-option">
                        <label class="admin__field-label" for="item-custom-content">
                            <span>${$t('Custom HTML Content')}</span>
                        </label>
                        <div class="admin__field-control">
                            <textarea id="item-custom-content"
                                      class="admin__control-textarea"
                                      rows="15"
                                      style="font-family: 'Courier New', Consolas, monospace; font-size: 13px; width: 100%;">${escape(item.custom_content || '')}</textarea>
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
                                   value="${escape(item.cms_block || '')}"
                                   placeholder="my_cms_block" />
                            <p class="note"><span>${$t('Identifier of CMS block to display in mega menu')}</span></p>
                        </div>
                    </div>
                </div>
            `;
        }
    };
});
