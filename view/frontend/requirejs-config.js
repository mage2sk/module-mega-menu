/**
 * Panth MegaMenu - RequireJS Configuration
 *
 * Registers desktop and mobile AMD widgets for Luma theme.
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */

var config = {
    map: {
        '*': {
            // New clean widgets
            'pmenuDesktop': 'Panth_MegaMenu/js/pmenu-desktop',
            'pmenuMobile':  'Panth_MegaMenu/js/pmenu-mobile',

            // Legacy aliases (keep backward compat)
            'panthMegaMenu':   'Panth_MegaMenu/js/pmenu-desktop',
            'megaMenu':        'Panth_MegaMenu/js/pmenu-desktop',
            'megaMenuWidget':  'Panth_MegaMenu/js/pmenu-desktop'
        }
    }
};
