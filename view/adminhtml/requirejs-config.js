/**
 * RequireJS configuration for Panth MegaMenu Admin
 */
var config = {
    config: {
        'Panth_MegaMenu/js/menu-preview': {
            bustCache: true
        },
        mixins: {
            'Magento_Ui/js/lib/validation/validator': {
                'Panth_MegaMenu/js/identifier-rule-mixin': true
            }
        }
    },
    map: {
        '*': {
            menuPreview: 'Panth_MegaMenu/js/menu-preview'
        }
    }
};
