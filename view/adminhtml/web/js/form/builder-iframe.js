/**
 * Menu Builder Iframe Component
 */
define([
    'Magento_Ui/js/form/components/html',
    'uiRegistry',
    'mage/url'
], function (Html, registry, urlBuilder) {
    'use strict';

    return Html.extend({
        defaults: {
            template: 'Panth_MegaMenu/form/builder-iframe'
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            return this;
        },

        /**
         * Get iframe URL
         */
        getIframeUrl: function () {
            var menuIdField = registry.get('panth_menu_form.panth_menu_form.general.menu_id');
            var menuId = menuIdField && menuIdField.value() ? menuIdField.value() : '';

            var params = {};
            if (menuId) {
                params.menu_id = menuId;
            }

            return urlBuilder.build('panth_menu/menu/customEdit', params);
        }
    });
});
