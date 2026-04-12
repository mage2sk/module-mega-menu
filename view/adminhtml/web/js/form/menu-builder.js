define([
    'Magento_Ui/js/form/components/html',
    'uiRegistry',
    'mage/url'
], function (Html, registry, urlBuilder) {
    'use strict';

    return Html.extend({
        defaults: {
            template: 'Panth_MegaMenu/form/menu-builder'
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            return this;
        },

        /**
         * Get items JSON textarea element
         */
        getItemsField: function () {
            return registry.get(this.ns + '.' + this.ns + '.menu_items.items_json');
        },

        /**
         * Open custom builder
         */
        openCustomBuilder: function () {
            var menuId = registry.get(this.ns + '.' + this.ns + '.general.menu_id');
            var id = menuId && menuId.value() ? menuId.value() : '';

            // Use Magento's URL builder
            var params = {};
            if (id) {
                params.menu_id = id;
            }

            var url = urlBuilder.build('panth_menu/menu/customEdit', params);
            window.location.href = url;
        }
    });
});
