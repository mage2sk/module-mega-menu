/**
 * Form page actions handler
 * Handles Import button click to open modal
 */
(function() {
    'use strict';

    // Wait for RequireJS to be available
    function initImportButton() {
        if (typeof require === 'undefined') {
            setTimeout(initImportButton, 100);
            return;
        }

        require([
            'jquery',
            'uiRegistry',
            'domReady!'
        ], function ($, registry) {

            // Function to bind Import button
            function bindImportButton() {
                // Find all possible button selectors for the form import button
                var buttonSelectors = [
                    '#import-menu-button-form',
                    'button[title="Import"]',
                    'a.import-menu-button-form',
                    '.import-menu-button-form',
                    '.page-actions button:contains("Import")',
                    '.page-actions a:contains("Import")',
                    '[id="import-menu-button-form"]'
                ];

                var $buttons = $(buttonSelectors.join(', '));

                if ($buttons.length === 0) {
                    return;
                }

                $buttons.each(function() {
                    // Remove any onclick attribute that Magento added
                    $(this).removeAttr('onclick');

                    // Also remove the click property
                    this.onclick = null;
                });

                $buttons.off('click.importModal').on('click.importModal', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    // Get the modal component and open it
                    var modal = registry.get('panth_menu_form.panth_menu_form.import_menu_modal_form');

                    if (modal && typeof modal.openModal === 'function') {
                        modal.openModal();
                    } else if (modal && typeof modal.toggleModal === 'function') {
                        modal.toggleModal();
                    } else {
                        // Try again after a delay
                        setTimeout(function() {
                            var modal = registry.get('panth_menu_form.panth_menu_form.import_menu_modal_form');
                            if (modal) {
                                if (typeof modal.openModal === 'function') {
                                    modal.openModal();
                                } else if (typeof modal.toggleModal === 'function') {
                                    modal.toggleModal();
                                }
                            }
                        }, 500);
                    }

                    return false;
                });
            }

            // Bind on ready
            $(document).ready(function () {
                // Try binding immediately
                bindImportButton();

                // Also try after UI components are loaded
                setTimeout(bindImportButton, 1000);
                setTimeout(bindImportButton, 2000);
                setTimeout(bindImportButton, 3000);
            });
        });
    }

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initImportButton);
    } else {
        initImportButton();
    }
})();
