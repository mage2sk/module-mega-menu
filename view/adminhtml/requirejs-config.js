/**
 * RequireJS configuration for Panth MegaMenu Admin
 */
var config = {
    config: {
        'Panth_MegaMenu/js/menu-preview': {
            bustCache: true
        }
    },
    map: {
        '*': {
            menuPreview: 'Panth_MegaMenu/js/menu-preview'
        }
    }
};
