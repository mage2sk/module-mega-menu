/**
 * Menu Builder Button Handler
 */
define([
    'jquery',
    'uiRegistry',
    'domReady!'
], function($, registry) {
    'use strict';

    return function() {
        // Bind button click handler
        $(document).on('click', '#open-advanced-builder-btn', function(e) {
            e.preventDefault();

            // Get the modal component and open it
            var modal = registry.get('panth_menu_form.panth_menu_form.menu_builder_modal');

            if (modal && typeof modal.openModal === 'function') {
                modal.openModal();
            } else if (modal && typeof modal.toggleModal === 'function') {
                modal.toggleModal();
            }
        });
    };
});
