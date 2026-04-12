/**
 * Magento Menu Item Builder Component
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry',
    'jquery',
    'knockout',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'Panth_MegaMenu/js/form/menu-item-form',
    'jquery/ui'
], function (Abstract, registry, $, ko, $t, modal, alert, confirm, itemForm) {
    'use strict';

    return Abstract.extend({
        defaults: {
            template: 'Panth_MegaMenu/form/menu-item-builder',
            // DO NOT initialize items here - must be done in initObservable
            modalElement: null,
            editingItemId: null,
            nextItemId: 1,
            expandedItems: {},
            sortableInstances: [],
            undoStack: [],
            redoStack: [],
            getCategoriesUrl: '', // Will be set from UI component XML configuration
            uploadUrl: '', // Will be set from UI component XML configuration
            createDemoBlocksUrl: '', // Will be set from UI component XML configuration
            editTabs: [
                { id: 'general', label: 'General' },
                { id: 'content', label: 'Content' },
                { id: 'design', label: 'Design & Layout' },
                { id: 'advanced', label: 'Advanced' },
                { id: 'visibility', label: 'Visibility' }
            ],
            listens: {
                'items': 'onItemsChange'
            }
        },

        /**
         * Initialize component
         */
        initialize: function () {

            try {
                // Call parent initialize FIRST
                this._super();

                // Register global preview function
                var self = this;
                window.previewMegaMenu = function() {
                    self.previewMenu();
                };

                // Initialize keyboard shortcuts
                this.initializeKeyboardShortcuts();

                // Create computed observables for better reactivity
                this.rootItems = ko.pureComputed(function() {
                    return this.getRootItems();
                }, this);

                this.hasItems = ko.pureComputed(function() {
                    return this.items().length > 0;
                }, this);

            } catch (e) {
            }

            return this;
        },

        /**
         * Initialize observable properties
         * CRITICAL: This MUST be called before template binding
         */
        initObservable: function () {

            // Initialize observables FIRST - items MUST be observable array before template binds
            this._super().observe({
                'items': [],         // Empty observable array - template will bind to this
                'isLoading': true,   // Loading state - show spinner while loading
                'isLoadingFromField': false,   // Flag to prevent infinite loop when loading from field
                'editingItem': null, // Currently editing item (Knockout observable)
                'activeTab': 'general', // Active tab in edit modal
                'selectedFileName': '', // Filename of selected image
                'imagePreviewSrc': '', // Preview image source
                'showImagePreview': false // Show/hide image preview
            });


            // Initialize expandedItems as a plain object (not observable)
            // Default: all items collapsed
            if (!this.expandedItems) {
                this.expandedItems = {};
            }

            // CRITICAL: Load items AFTER observables are initialized
            // This ensures the template is bound to the observable before we populate it
            var self = this;
            setTimeout(function() {
                self.loadItems();
            }, 150);

            return this;
        },

        /**
         * Handle items change
         */
        onItemsChange: function () {
            var self = this;

            // Only auto-save if we're not currently loading from the field
            // This prevents infinite loop: load -> items change -> save -> load -> ...
            if (!this.isLoadingFromField()) {
                this.saveItems();
            }

            // Reinitialize sortable when items change
            setTimeout(function() {
                self.initializeSortable();
            }, 100);
        },

        /**
         * Load items from JSON field
         * CRITICAL: This properly populates the observable array
         */
        loadItems: function () {
            var self = this;

            // Try multiple possible paths for the JSON field
            var possiblePaths = [
                this.ns + '.' + this.ns + '.menu_items.items_json',
                this.ns + '.menu_items.items_json',
                'panth_menu_form.panth_menu_form.menu_items.items_json',
                'panth_menu_form.menu_items.items_json'
            ];

            var jsonField = null;
            var jsonFieldPath = null;

            for (var i = 0; i < possiblePaths.length; i++) {
                jsonField = registry.get(possiblePaths[i]);
                if (jsonField) {
                    jsonFieldPath = possiblePaths[i];
                    break;
                }
            }

            if (!jsonField) {
                // Show items area anyway with empty state
                this.items([]);
                this.isLoading(false);
                return;
            }

            // Process the JSON value and populate observable
            var processValue = function() {
                var jsonValue = jsonField.value();

                if (!jsonValue || jsonValue.trim() === '') {
                    self.isLoading(false);
                    return;
                }

                // Set flag to prevent auto-save during loading
                self.isLoadingFromField(true);

                try {
                    var parsedItems = JSON.parse(jsonValue);

                    if (Array.isArray(parsedItems)) {

                        if (parsedItems.length > 0) {

                            // Find max item_id to set nextItemId
                            var maxId = 0;
                            parsedItems.forEach(function(item) {
                                var itemIdStr = String(item.item_id || '');
                                var itemIdNum = parseInt(itemIdStr.replace(/[^0-9]/g, ''));
                                if (!isNaN(itemIdNum) && itemIdNum > maxId) {
                                    maxId = itemIdNum;
                                }
                            });
                            self.nextItemId = maxId + 1;

                            // CRITICAL: Clear observable array and repopulate
                            self.items.removeAll();

                            // Add items one by one to trigger Knockout updates
                            parsedItems.forEach(function(item, index) {
                                self.items.push(item);
                            });

                            // Trigger sortable initialization after DOM updates
                            setTimeout(function() {
                                self.initializeSortable();
                                self.isLoading(false);
                                self.isLoadingFromField(false);
                            }, 400);

                        } else {
                            self.isLoading(false);
                            self.isLoadingFromField(false);
                        }
                    } else {
                        self.isLoading(false);
                        self.isLoadingFromField(false);
                    }
                } catch (e) {
                    self.isLoading(false);
                    self.isLoadingFromField(false);
                }
            };

            // Subscribe to jsonField value changes for automatic reload
            jsonField.on('value', function() {
                processValue();
            });

            // Try immediate load first
            if (jsonField.value()) {
                processValue();
            }
        },

        /**
         * Save items to JSON field
         */
        saveItems: function () {
            // Try multiple possible paths for the JSON field
            var possiblePaths = [
                this.ns + '.' + this.ns + '.menu_items.items_json',
                this.ns + '.menu_items.items_json',
                'panth_menu_form.panth_menu_form.menu_items.items_json',
                'panth_menu_form.menu_items.items_json'
            ];

            var jsonField = null;
            for (var i = 0; i < possiblePaths.length; i++) {
                jsonField = registry.get(possiblePaths[i]);
                if (jsonField) {
                    break;
                }
            }

            if (jsonField) {
                var itemsJson = JSON.stringify(this.items());
                jsonField.value(itemsJson);
            }
        },

        /**
         * Get root level items
         * CRITICAL: Must access items() to trigger Knockout reactivity
         */
        getRootItems: function () {
            // Access the observable array - this triggers Knockout dependency tracking
            var allItems = this.items();

            if (!Array.isArray(allItems)) {
                return [];
            }

            // Filter for root items (no parent or parent is 0)
            var rootItems = ko.utils.arrayFilter(allItems, function(item) {
                return item && (!item.parent_id || item.parent_id === 0 || item.parent_id === '0');
            });

            return rootItems || [];
        },

        /**
         * Get child items for a parent
         * CRITICAL: Must access items() to trigger Knockout reactivity
         */
        getChildItems: function (parentId) {
            // Access the observable array - this triggers Knockout dependency tracking
            var allItems = this.items();

            if (!Array.isArray(allItems)) {
                return [];
            }

            // Filter for children of the specified parent
            var childItems = ko.utils.arrayFilter(allItems, function(item) {
                return item && item.parent_id == parentId;
            });

            // Ensure we always return an array, never undefined
            return childItems || [];
        },

        /**
         * Add root item (wrapper for addItem with parentId = 0)
         */
        addRootItem: function () {
            this.addItem(0);
        },

        /**
         * Add new item
         */
        addItem: function (parentId) {
            var self = this;
            parentId = parentId || 0;

            var tempItemId = 'temp_' + Date.now();

            var newItem = {
                item_id: tempItemId,
                title: 'New Item',
                url: '#',
                item_type: 'link',
                parent_id: parentId,
                position: this.items().length,
                level: this.calculateLevel(parentId),
                is_active: 1,
                show_on_desktop: 1,
                show_on_tablet: 1,
                show_on_mobile: 1,
                target: '_self',
                css_class: '',
                icon: ''
            };

            // Add temp item to display in tree immediately
            var currentItems = this.items();
            currentItems.push(newItem);
            this.items(currentItems);

            // Open edit modal with save/cancel handlers
            this.editItemWithCallback(tempItemId, function(saved) {
                if (saved) {
                    // User saved - assign permanent ID
                    var item = self.findItemById(tempItemId);
                    if (item) {
                        item.item_id = 'new_' + self.nextItemId++;
                        self.items(self.items());
                        self.saveItems();
                    }
                } else {
                    // User cancelled - remove temp item
                    var items = self.items().filter(function(item) {
                        return item.item_id !== tempItemId;
                    });
                    self.items(items);
                }

                // Reinitialize sortable
                setTimeout(function () {
                    self.refreshSortable();
                }, 300);
            });
        },

        /**
         * Calculate level based on parent
         */
        calculateLevel: function (parentId) {
            if (!parentId || parentId === 0 || parentId === '0') {
                return 0;
            }
            var parent = this.findItemById(parentId);
            return parent ? (parent.level + 1) : 0;
        },

        /**
         * Find item by ID
         */
        findItemById: function (itemId) {
            return this.items().find(function (item) {
                return item.item_id == itemId;
            });
        },

        /**
         * Edit item
         */
        editItem: function (itemId) {
            this.editItemWithCallback(itemId, null);
        },

        /**
         * Edit item with callback for save/cancel
         */
        editItemWithCallback: function (itemId, callback) {
            var item = this.findItemById(itemId);
            if (!item) {
                if (callback) callback(false);
                return;
            }

            this.editingItemId = itemId;
            this.showEditModal(item, callback);
        },

        /**
         * Create observable version of item for editing
         */
        createEditableItem: function(item) {
            var observableItem = {};

            // Create observables for all fields
            var fields = [
                'item_id', 'title', 'url', 'item_type', 'parent_id', 'position', 'level',
                'target', 'rel', 'custom_content', 'cms_block',
                'icon', 'image', 'image_width', 'image_height', 'display_type', 'badge', 'badge_text',
                'column_width', 'css_class', 'bg_color', 'text_color',
                'padding', 'margin', 'width', 'height', 'border', 'border_radius',
                'animation', 'hover_effect', 'hover_bg_color', 'hover_text_color',
                'font_family', 'font_size', 'font_weight', 'text_transform',
                'box_shadow', 'text_shadow', 'opacity',
                'tooltip', 'click_action', 'data_attributes',
                'aria_label', 'role',
                'is_active', 'sort_order',
                'show_on_desktop', 'show_on_tablet', 'show_on_mobile',
                'store_views', 'start_date', 'end_date', 'customer_groups',
                'category_id'
            ];

            fields.forEach(function(field) {
                var value = item[field] !== undefined ? item[field] : '';
                // Convert numbers to strings for form inputs
                if (field === 'is_active' || field === 'show_on_desktop' || field === 'show_on_tablet' || field === 'show_on_mobile') {
                    value = String(value || '1');
                }
                observableItem[field] = ko.observable(value);
            });

            return observableItem;
        },

        /**
         * Show edit modal with Knockout bindings (Pure Knockout - No Hybrid)
         */
        showEditModal: function (item, callback) {
            var self = this;
            var userSaved = false;

            // Create observable version of item
            var editableItem = this.createEditableItem(item);
            this.editingItem(editableItem);
            this.activeTab('general');

            // Create modal container with inline template
            var modalElement = $('<div class="menu-edit-modal-container" data-bind="with: $data"><div class="menu-item-edit-modal"></div></div>');

            // Get the inner container
            var innerContainer = modalElement.find('.menu-item-edit-modal');

            // Build the comprehensive template HTML inline
            innerContainer.html(self.getEditModalTemplate());

            // Apply Knockout bindings to the modal element
            ko.applyBindings(self, modalElement[0]);

            var options = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: $t('Edit Menu Item') + ': ' + item.title,
                modalClass: 'menu-item-edit-modal-wrapper',
                buttons: [{
                    text: $t('Cancel'),
                    class: 'action-secondary action-dismiss',
                    click: function () {
                        userSaved = false;
                        this.closeModal();
                    }
                }, {
                    text: $t('Save'),
                    class: 'action-primary action-accept',
                    click: function () {
                        userSaved = true;
                        self.saveEditedItemKo();
                        this.closeModal();
                    }
                }],
                closed: function() {
                    // Clean up Knockout bindings
                    ko.cleanNode(modalElement[0]);
                    self.editingItem(null);

                    // Call callback when modal is closed
                    if (callback) {
                        callback(userSaved);
                    }
                    self.modalElement = null;
                }
            };

            this.modalElement = modal(options, modalElement);
            modalElement.modal('openModal');

            // All interactive features now use pure Knockout bindings - no jQuery initialization needed
        },

        /**
         * Get edit modal template HTML
         */
        /**
         * Get comprehensive edit modal template HTML with ALL fields (Pure Knockout)
         */
        getEditModalTemplate: function() {
            return `
                <!-- Tabs Navigation -->
                <ul class="edit-tabs-nav">
                    <!-- ko foreach: editTabs -->
                    <li data-bind="css: { active: $parent.activeTab() === id }, click: function() { $parent.activeTab(id); }">
                        <span data-bind="text: label"></span>
                    </li>
                    <!-- /ko -->
                </ul>

                <!-- General Tab -->
                <div class="edit-tab-content" data-bind="visible: activeTab() === 'general', with: editingItem">
                    <div class="field-group">
                        <div class="field-group-title">${$t('Basic Information')}</div>

                        <div class="admin__field _required">
                            <label class="admin__field-label"><span>${$t('Menu Item Title')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: title" required />
                                <p class="note"><span>${$t('This will be displayed in the menu')}</span></p>
                            </div>
                        </div>

                        <div class="admin__field _required">
                            <label class="admin__field-label"><span>${$t('Link Type')}</span></label>
                            <div class="admin__field-control">
                                <select id="item-type" class="admin__control-select" data-bind="value: item_type">
                                    <optgroup label="${$t('Basic')}">
                                        <option value="link">${$t('Custom URL')}</option>
                                        <option value="dropdown">${$t('Dropdown Parent (No Link)')}</option>
                                        <option value="divider">${$t('Separator / Divider')}</option>
                                    </optgroup>
                                    <optgroup label="${$t('Catalog')}">
                                        <option value="category">${$t('Link to Category')}</option>
                                        <option value="product">${$t('Link to Product')}</option>
                                    </optgroup>
                                    <optgroup label="${$t('Content Pages')}">
                                        <option value="cms_page">${$t('CMS Page')}</option>
                                        <option value="custom_html">${$t('Custom HTML')}</option>
                                        <option value="widget">${$t('Widget Code')}</option>
                                    </optgroup>
                                    <optgroup label="${$t('Customer Account')}">
                                        <option value="account">${$t('My Account Dashboard')}</option>
                                        <option value="account_orders">${$t('My Orders')}</option>
                                        <option value="account_wishlist">${$t('My Wishlist')}</option>
                                        <option value="account_addresses">${$t('Address Book')}</option>
                                        <option value="account_edit">${$t('Account Information')}</option>
                                    </optgroup>
                                    <optgroup label="${$t('Store Pages')}">
                                        <option value="contact">${$t('Contact Us')}</option>
                                        <option value="about">${$t('About Us')}</option>
                                        <option value="faq">${$t('FAQ')}</option>
                                        <option value="blog">${$t('Blog')}</option>
                                    </optgroup>
                                </select>
                                <p class="note"><span>${$t('Select the type of content or link this menu item should point to')}</span></p>
                            </div>
                        </div>

                        <div class="admin__field _required">
                            <label class="admin__field-label"><span>${$t('URL / Link')}</span></label>
                            <div class="admin__field-control">
                                <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                    <input class="admin__control-text" type="text" data-bind="textInput: url"
                                           required placeholder="Enter URL or click Browse to select content" style="flex: 1;" />
                                    <button type="button" class="action-default"
                                            data-bind="click: $parents[1].browseContent.bind($parents[1])"
                                            title="${$t('Browse Categories, Products, Pages & Blocks')}">
                                        <span>${$t('Browse...')}</span>
                                    </button>
                                </div>
                                <p class="note">
                                    <span>${$t('Enter URL manually or click Browse to search categories, products, CMS pages/blocks')}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="field-group">
                        <div class="field-group-title">${$t('Link Behavior')}</div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Open Link In')}</span></label>
                            <div class="admin__field-control">
                                <select class="admin__control-select" data-bind="value: target">
                                    <option value="_self">${$t('Same Window')}</option>
                                    <option value="_blank">${$t('New Window/Tab')}</option>
                                    <option value="_parent">${$t('Parent Frame')}</option>
                                </select>
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Rel Attribute')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: rel" placeholder="nofollow noopener" />
                                <p class="note"><span>${$t('e.g., nofollow, noopener, noreferrer')}</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Tab -->
                <div class="edit-tab-content" data-bind="visible: activeTab() === 'content', with: editingItem">
                    <div class="field-group">
                        <div class="field-group-title">${$t('HTML Content')}</div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Custom HTML Content')}</span></label>
                            <div class="admin__field-control">
                                <textarea class="admin__control-textarea" rows="15" data-bind="textInput: custom_content" style="font-family: 'Courier New', monospace; font-size: 13px;"></textarea>
                                <p class="note"><span>${$t('Enter HTML content for mega menu blocks. Use Bootstrap classes for styling.')}</span></p>
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('CMS Block Identifier')}</span></label>
                            <div class="admin__field-control">
                                <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                    <input class="admin__control-text" type="text" data-bind="textInput: cms_block"
                                           placeholder="my_cms_block" style="flex: 1;" />
                                    <button type="button" class="action-default"
                                            data-bind="click: $parents[1].browseCmsBlock.bind($parents[1])"
                                            title="${$t('Browse CMS Blocks')}">
                                        <span>${$t('Browse...')}</span>
                                    </button>
                                </div>
                                <p class="note"><span>${$t('Identifier of CMS block to display in mega menu. Click Browse to select from available blocks.')}</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Design Tab -->
                <div class="edit-tab-content" data-bind="visible: activeTab() === 'design', with: editingItem">
                    <div class="field-group">
                        <div class="field-group-title">${$t('Icons & Images')}</div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Icon Class')}</span></label>
                            <div class="admin__field-control">
                                <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                    <input class="admin__control-text" type="text" data-bind="textInput: icon"
                                           placeholder="fa fa-home" style="flex: 1;" />
                                    <select class="admin__control-select" data-bind="value: icon" style="width: 200px;">
                                        <option value="">${$t('Select Icon Library')}</option>
                                        <optgroup label="Font Awesome">
                                            <option value="fa fa-home">fa fa-home</option>
                                            <option value="fa fa-shopping-cart">fa fa-shopping-cart</option>
                                            <option value="fa fa-user">fa fa-user</option>
                                            <option value="fa fa-search">fa fa-search</option>
                                            <option value="fa fa-heart">fa fa-heart</option>
                                            <option value="fa fa-star">fa fa-star</option>
                                            <option value="fa fa-envelope">fa fa-envelope</option>
                                            <option value="fa fa-phone">fa fa-phone</option>
                                        </optgroup>
                                        <optgroup label="Bootstrap Icons">
                                            <option value="bi bi-house">bi bi-house</option>
                                            <option value="bi bi-cart">bi bi-cart</option>
                                            <option value="bi bi-person">bi bi-person</option>
                                            <option value="bi bi-search">bi bi-search</option>
                                            <option value="bi bi-heart">bi bi-heart</option>
                                            <option value="bi bi-star">bi bi-star</option>
                                        </optgroup>
                                    </select>
                                </div>
                                <p class="note">
                                    <span>${$t('Supported: Font Awesome (fa fa-*), Bootstrap Icons (bi bi-*), Material Icons, Line Icons')}</span>
                                </p>
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Menu Item Image')}</span></label>
                            <div class="admin__field-control">
                                <div class="image-uploader-wrapper" style="margin-bottom: 10px;">
                                    <!-- Image Preview -->
                                    <!-- ko if: image() -->
                                    <div style="margin-bottom: 10px;">
                                        <div style="position: relative; display: inline-block; border: 1px solid #ddd; padding: 5px; border-radius: 3px; background: #fafafa;">
                                            <img data-bind="attr: { src: image }" alt="Menu Item Image"
                                                 style="max-width: 200px; max-height: 200px; display: block;" />
                                            <button type="button" class="action-secondary action-remove"
                                                    data-bind="click: $parents[1].removeImage.bind($parents[1])"
                                                    style="position: absolute; top: 0; right: 0; margin: 5px; padding: 2px 8px;
                                                           background: #e02b27; color: white; border: none; cursor: pointer; border-radius: 3px;"
                                                    title="${$t('Remove Image')}">
                                                <span>×</span>
                                            </button>
                                        </div>
                                    </div>
                                    <!-- /ko -->

                                    <!-- Upload Controls -->
                                    <div style="margin-bottom: 10px;">
                                        <input type="file" id="item-image-file" accept="image/*" style="display: none;"
                                               data-bind="event: { change: $parents[1].handleImageFileSelect.bind($parents[1]) }" />
                                        <button type="button" class="action-default" style="margin-right: 10px;"
                                                data-bind="click: $parents[1].chooseImage.bind($parents[1])">
                                            <span>${$t('Choose Image')}</span>
                                        </button>
                                        <span data-bind="text: $parents[1].selectedFileName()" style="color: #666; font-size: 12px;"></span>
                                    </div>

                                    <!-- Manual URL Input -->
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-size: 12px; color: #666;">
                                            ${$t('Or enter image URL manually:')}
                                        </label>
                                        <input class="admin__control-text" type="text"
                                               data-bind="textInput: image" placeholder="https://example.com/image.jpg" />
                                    </div>
                                </div>
                                <p class="note"><span>${$t('Upload an image or enter URL manually. Supported: JPG, PNG, GIF, SVG')}</span></p>
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Image Dimensions')}</span></label>
                            <div class="admin__field-control">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 12px;">${$t('Width')}</label>
                                        <input class="admin__control-text" type="text" data-bind="textInput: image_width" placeholder="200px or auto" />
                                    </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 12px;">${$t('Height')}</label>
                                        <input class="admin__control-text" type="text" data-bind="textInput: image_height" placeholder="150px or auto" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Badge')}</span></label>
                            <div class="admin__field-control">
                                <select class="admin__control-select" data-bind="value: badge, event: { change: $parents[1].onBadgeChange.bind($parents[1]) }">
                                    <option value="">${$t('None')}</option>
                                    <option value="new">${$t('New')}</option>
                                    <option value="hot">${$t('Hot')}</option>
                                    <option value="sale">${$t('Sale')}</option>
                                    <option value="custom">${$t('Custom')}</option>
                                </select>
                                <p class="note"><span>${$t('Select a preset badge or "Custom" to enter your own text')}</span></p>
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Badge Text')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: badge_text, enable: badge() === 'custom' || badge() === ''" placeholder="Enter custom badge text" />
                                <p class="note"><span>${$t('Auto-filled for preset badges. Edit when "Custom" is selected.')}</span></p>
                            </div>
                        </div>
                    </div>

                    <div class="field-group">
                        <div class="field-group-title">${$t('Layout & Styling')}</div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Column Width')}</span></label>
                            <div class="admin__field-control">
                                <select class="admin__control-select" data-bind="value: column_width">
                                    <option value="">${$t('Auto')}</option>
                                    <option value="25">25%</option>
                                    <option value="33">33% (1/3)</option>
                                    <option value="50">50% (1/2)</option>
                                    <option value="66">66% (2/3)</option>
                                    <option value="75">75%</option>
                                    <option value="100">100%</option>
                                </select>
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('CSS Class')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: css_class" placeholder="custom-class another-class" />
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Background Color')}</span></label>
                            <div class="admin__field-control">
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="color" data-bind="value: bg_color, event: { change: $parents[1].syncColorToText.bind($parents[1], 'bg_color') }"
                                           style="width: 60px; height: 38px; border: 1px solid #adadad; border-radius: 3px; cursor: pointer;" />
                                    <input class="admin__control-text" type="text" data-bind="textInput: bg_color"
                                           placeholder="#ffffff" style="flex: 1;" />
                                </div>
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Text Color')}</span></label>
                            <div class="admin__field-control">
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="color" data-bind="value: text_color, event: { change: $parents[1].syncColorToText.bind($parents[1], 'text_color') }"
                                           style="width: 60px; height: 38px; border: 1px solid #adadad; border-radius: 3px; cursor: pointer;" />
                                    <input class="admin__control-text" type="text" data-bind="textInput: text_color"
                                           placeholder="#000000" style="flex: 1;" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="field-group">
                        <div class="field-group-title">${$t('Spacing & Dimensions')}</div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Padding')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: padding" placeholder="10px 15px" />
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Margin')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: margin" placeholder="0 10px" />
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Width')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: width" placeholder="auto or 200px" />
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Height')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: height" placeholder="auto or 50px" />
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Border')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: border" placeholder="1px solid #ddd" />
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Border Radius')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: border_radius" placeholder="4px" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Tab -->
                <div class="edit-tab-content" data-bind="visible: activeTab() === 'advanced', with: editingItem">
                    <div class="field-group">
                        <div class="field-group-title">${$t('Animation & Effects')}</div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Animation Effect')}</span></label>
                            <div class="admin__field-control">
                                <select class="admin__control-select" data-bind="value: animation">
                                    <option value="">${$t('None')}</option>
                                    <option value="fadeIn">${$t('Fade In')}</option>
                                    <option value="fadeInUp">${$t('Fade In Up')}</option>
                                    <option value="fadeInDown">${$t('Fade In Down')}</option>
                                    <option value="slideInLeft">${$t('Slide In Left')}</option>
                                    <option value="slideInRight">${$t('Slide In Right')}</option>
                                    <option value="zoomIn">${$t('Zoom In')}</option>
                                    <option value="bounceIn">${$t('Bounce In')}</option>
                                </select>
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Hover Effect')}</span></label>
                            <div class="admin__field-control">
                                <select class="admin__control-select" data-bind="value: hover_effect">
                                    <option value="">${$t('Default')}</option>
                                    <option value="underline">${$t('Underline')}</option>
                                    <option value="background-change">${$t('Background Change')}</option>
                                    <option value="scale">${$t('Scale / Grow')}</option>
                                    <option value="shadow">${$t('Shadow')}</option>
                                    <option value="glow">${$t('Glow')}</option>
                                </select>
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Hover Background Color')}</span></label>
                            <div class="admin__field-control">
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="color" data-bind="value: hover_bg_color, event: { change: $parents[1].syncColorToText.bind($parents[1], 'hover_bg_color') }"
                                           style="width: 60px; height: 38px; border: 1px solid #adadad; border-radius: 3px; cursor: pointer;" />
                                    <input class="admin__control-text" type="text" data-bind="textInput: hover_bg_color"
                                           placeholder="#f0f0f0" style="flex: 1;" />
                                </div>
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Hover Text Color')}</span></label>
                            <div class="admin__field-control">
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="color" data-bind="value: hover_text_color, event: { change: $parents[1].syncColorToText.bind($parents[1], 'hover_text_color') }"
                                           style="width: 60px; height: 38px; border: 1px solid #adadad; border-radius: 3px; cursor: pointer;" />
                                    <input class="admin__control-text" type="text" data-bind="textInput: hover_text_color"
                                           placeholder="#000000" style="flex: 1;" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="field-group">
                        <div class="field-group-title">${$t('Typography')}</div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Font Family')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: font_family" placeholder="Arial, sans-serif" />
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Font Size')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: font_size" placeholder="14px" />
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Font Weight')}</span></label>
                            <div class="admin__field-control">
                                <select class="admin__control-select" data-bind="value: font_weight">
                                    <option value="">${$t('Default')}</option>
                                    <option value="300">300 (Light)</option>
                                    <option value="400">400 (Normal)</option>
                                    <option value="500">500 (Medium)</option>
                                    <option value="600">600 (Semi Bold)</option>
                                    <option value="700">700 (Bold)</option>
                                    <option value="900">900 (Black)</option>
                                </select>
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Text Transform')}</span></label>
                            <div class="admin__field-control">
                                <select class="admin__control-select" data-bind="value: text_transform">
                                    <option value="">${$t('None')}</option>
                                    <option value="uppercase">${$t('UPPERCASE')}</option>
                                    <option value="lowercase">${$t('lowercase')}</option>
                                    <option value="capitalize">${$t('Capitalize')}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="field-group">
                        <div class="field-group-title">${$t('Shadow & Effects')}</div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Box Shadow')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: box_shadow" placeholder="0 2px 4px rgba(0,0,0,0.1)" />
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Text Shadow')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: text_shadow" placeholder="1px 1px 2px rgba(0,0,0,0.3)" />
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Opacity')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: opacity" placeholder="1.0" />
                            </div>
                        </div>
                    </div>

                    <div class="field-group">
                        <div class="field-group-title">${$t('Behavior')}</div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Tooltip Text')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: tooltip" />
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Click Action')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: click_action" placeholder="JavaScript code" />
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Data Attributes')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: data_attributes" placeholder='{"key":"value"}' />
                            </div>
                        </div>
                    </div>

                    <div class="field-group">
                        <div class="field-group-title">${$t('Accessibility')}</div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('ARIA Label')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: aria_label" />
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Role')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="text" data-bind="textInput: role" placeholder="button, menuitem, etc." />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visibility Tab -->
                <div class="edit-tab-content" data-bind="visible: activeTab() === 'visibility', with: editingItem">
                    <div class="field-group">
                        <div class="field-group-title">${$t('Display Status')}</div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Enable Menu Item')}</span></label>
                            <div class="admin__field-control">
                                <select class="admin__control-select" data-bind="value: is_active">
                                    <option value="1">${$t('Yes')}</option>
                                    <option value="0">${$t('No')}</option>
                                </select>
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Sort Order')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="number" data-bind="textInput: sort_order" min="0" placeholder="0" />
                                <p class="note"><span>${$t('Lower numbers appear first')}</span></p>
                            </div>
                        </div>
                    </div>

                    <div class="field-group">
                        <div class="field-group-title">${$t('Device Visibility')}</div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Show on Desktop')}</span></label>
                            <div class="admin__field-control">
                                <select class="admin__control-select" data-bind="value: show_on_desktop">
                                    <option value="1">${$t('Yes')}</option>
                                    <option value="0">${$t('No')}</option>
                                </select>
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Show on Tablet')}</span></label>
                            <div class="admin__field-control">
                                <select class="admin__control-select" data-bind="value: show_on_tablet">
                                    <option value="1">${$t('Yes')}</option>
                                    <option value="0">${$t('No')}</option>
                                </select>
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Show on Mobile')}</span></label>
                            <div class="admin__field-control">
                                <select class="admin__control-select" data-bind="value: show_on_mobile">
                                    <option value="1">${$t('Yes')}</option>
                                    <option value="0">${$t('No')}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="field-group">
                        <div class="field-group-title">${$t('Schedule')}</div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('Start Date')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="datetime-local" data-bind="textInput: start_date" />
                                <p class="note"><span>${$t('Item will be hidden before this date')}</span></p>
                            </div>
                        </div>

                        <div class="admin__field">
                            <label class="admin__field-label"><span>${$t('End Date')}</span></label>
                            <div class="admin__field-control">
                                <input class="admin__control-text" type="datetime-local" data-bind="textInput: end_date" />
                                <p class="note"><span>${$t('Item will be hidden after this date')}</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                .menu-item-edit-modal { min-height: 500px; }
                .edit-tabs-nav {
                    list-style: none; padding: 0; margin: 0 0 20px 0;
                    border-bottom: 2px solid #ddd; display: flex;
                }
                .edit-tabs-nav li {
                    padding: 12px 20px; cursor: pointer; background: #f5f5f5;
                    border: 1px solid #ddd; border-bottom: none; margin-right: 5px;
                    border-radius: 4px 4px 0 0; transition: all 0.3s;
                }
                .edit-tabs-nav li:hover { background: #e9e9e9; }
                .edit-tabs-nav li.active {
                    background: #fff; font-weight: 600; color: #1979c3;
                    border-bottom-color: #fff; margin-bottom: -2px;
                }
                .edit-tab-content {
                    padding: 20px; background: #fff;
                    border: 1px solid #ddd; border-radius: 0 4px 4px 4px;
                }
                .field-group {
                    margin-bottom: 30px; padding-bottom: 20px;
                    border-bottom: 1px solid #e3e3e3;
                }
                .field-group:last-child { border-bottom: none; }
                .field-group-title {
                    font-size: 14px; font-weight: 600; color: #333;
                    margin-bottom: 15px; padding-bottom: 8px;
                    border-bottom: 1px solid #e8e8e8;
                }
                .admin__field { margin-bottom: 20px; position: relative; }
                .admin__field._required > .admin__field-label > span::after {
                    content: '*'; color: #e02b27;
                    font-size: 1.2rem; margin: 0 0 0 5px;
                    display: inline; position: static;
                }
                .admin__field-label {
                    display: block; margin-bottom: 8px; font-weight: 600;
                    position: relative;
                }
                .admin__field-label > span {
                    position: relative; display: inline;
                }
                .admin__field-control { position: relative; }
                .admin__control-text, .admin__control-select, .admin__control-textarea {
                    width: 100%; padding: 8px 12px;
                    border: 1px solid #ccc; border-radius: 3px; font-size: 14px;
                }
                .admin__control-text:focus, .admin__control-select:focus, .admin__control-textarea:focus {
                    border-color: #1979c3; outline: none;
                    box-shadow: 0 0 5px rgba(25, 121, 195, 0.3);
                }
                .note {
                    margin: 5px 0 0; padding: 5px 0; color: #666;
                    font-size: 12px; line-height: 1.4;
                }
                </style>
            `;
        },

        /**
         * Browse Content - Pure Knockout method (no jQuery event binding)
         * Opens the content browser to select categories, products, pages, etc.
         */
        browseContent: function() {
            var self = this;
            var currentType = this.editingItem() && this.editingItem().item_type ? this.editingItem().item_type() : 'link';

            this.showContentBrowser(currentType, function(selectedUrl, selectedTitle) {
                // Update Knockout observable
                if (self.editingItem() && self.editingItem().url) {
                    self.editingItem().url(selectedUrl);
                }

                // Optionally update title if empty
                if (selectedTitle && (!self.editingItem().title() || self.editingItem().title() === '')) {
                    self.editingItem().title(selectedTitle);
                }
            });
        },

        /**
         * Browse CMS Block - Returns identifier only (not URL)
         * Opens the browser showing only CMS blocks and sets the identifier in cms_block field
         */
        browseCmsBlock: function() {
            var self = this;

            this.showContentBrowser('cms_block', function(selectedUrl, selectedTitle, selectedIdentifier) {
                // Update Knockout observable with identifier only
                if (self.editingItem() && self.editingItem().cms_block) {
                    self.editingItem().cms_block(selectedIdentifier || '');
                }
            });
        },

        /**
         * Choose Image - Pure Knockout method (no jQuery event binding)
         * Triggers the hidden file input to upload an image
         */
        chooseImage: function() {
            // Trigger the hidden file input
            var fileInput = document.getElementById('item-image-file');
            if (fileInput) {
                fileInput.click();
            }
        },

        /**
         * Handle Image File Select - Pure Knockout method
         * Called when a file is selected from the file input
         */
        handleImageFileSelect: function(data, event) {
            var self = this;
            var file = event.target.files[0];

            if (file) {
                this.selectedFileName(file.name);

                // Show preview immediately using FileReader
                var reader = new FileReader();
                reader.onload = function(e) {
                    var imageUrl = e.target.result;
                    self.imagePreviewSrc(imageUrl);
                    self.showImagePreview(true);

                    // Update Knockout observable in editingItem
                    if (self.editingItem() && self.editingItem().image) {
                        self.editingItem().image(imageUrl);
                    }
                };
                reader.readAsDataURL(file);
            }
        },

        /**
         * Remove Image - Pure Knockout method (no jQuery event binding)
         * Clears the image preview and resets the file input
         */
        removeImage: function() {
            // Clear observables
            this.selectedFileName('');
            this.imagePreviewSrc('');
            this.showImagePreview(false);

            // Clear editingItem image
            if (this.editingItem() && this.editingItem().image) {
                this.editingItem().image('');
            }

            // Clear file input
            var fileInput = document.getElementById('item-image-file');
            if (fileInput) {
                fileInput.value = '';
            }
        },

        /**
         * Badge Change Handler - Pure Knockout method
         * Auto-fills badge_text when a preset badge is selected
         */
        onBadgeChange: function() {
            if (!this.editingItem() || !this.editingItem().badge || !this.editingItem().badge_text) {
                return;
            }

            var badgeValue = this.editingItem().badge();
            var badgeTextMap = {
                'new': 'NEW',
                'hot': 'HOT',
                'sale': 'SALE',
                '': '' // None
            };

            // Auto-fill badge_text for preset values
            if (badgeValue !== 'custom' && badgeTextMap.hasOwnProperty(badgeValue)) {
                this.editingItem().badge_text(badgeTextMap[badgeValue]);
            }
            // For 'custom', leave badge_text as is (user will type their own)
        },

        /**
         * Sync Color Picker with Text Input - Pure Knockout method
         * Updates the text input when color picker changes
         */
        syncColorToText: function(field, data, event) {
            var colorValue = event.target.value;
            if (this.editingItem() && this.editingItem()[field]) {
                this.editingItem()[field](colorValue);
            }
        },

        /**
         * Initialize tabs in modal
         */
        initializeTabs: function () {
            $('.menu-item-tabs .tab-header').off('click').on('click', function() {
                var tabId = $(this).data('tab');

                $('.menu-item-tabs .tab-header').removeClass('active');
                $('.menu-item-tabs .tab-content').removeClass('active');

                $(this).addClass('active');
                $('#' + tabId).addClass('active');
            });
        },


        /**
         * Upload image file
         */
        uploadImage: function (file) {
            var self = this;
            var formData = new FormData();
            formData.append('image', file);

            // Add form key for CSRF protection
            var formKey = window.FORM_KEY || $('input[name="form_key"]').val();
            if (formKey) {
                formData.append('form_key', formKey);
            }

            // Show progress bar
            $('#item-image-progress').show();
            $('#item-image-progress-bar').css('width', '0%');

            $.ajax({
                url: self.uploadUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            var percentComplete = (e.loaded / e.total) * 100;
                            $('#item-image-progress-bar').css('width', percentComplete + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    $('#item-image-progress').hide();

                    if (response.url) {
                        // Set the uploaded image URL
                        $('#item-image').val(response.url);
                        $('#item-image-url').val(response.url);
                        $('#item-image-preview-img').attr('src', response.url);
                        $('#item-image-preview').show();

                        self.showNotification('success', $t('Image uploaded successfully'));
                    } else if (response.error) {
                        self.showNotification('error', $t('Upload failed: ') + response.error);
                    }
                },
                error: function(xhr, status, error) {
                    $('#item-image-progress').hide();
                    self.showNotification('error', $t('Upload failed: ') + error);
                }
            });
        },

        /**
         * Initialize URL builder
         */
        initializeUrlBuilder: function () {
            var self = this;

            // Get current item being edited
            var currentItem = this.findItemById(this.editingItemId);
            if (!currentItem) return;

            // Check if item is a parent (root level or has children)
            var isParent = !currentItem.parent_id || currentItem.parent_id == 0 || currentItem.parent_id == '0';
            var hasChildren = this.getChildItems(currentItem.item_id).length > 0;
            var isParentItem = isParent || hasChildren;

            // Disable CMS Block and Widget options for parent items
            if (isParentItem) {
                setTimeout(function() {
                    $('#item-type option[value="cms_block"]').prop('disabled', true).text($t('Static Block (CMS)') + ' - ' + $t('Only for sub-menu items'));
                    $('#item-type option[value="widget"]').prop('disabled', true).text($t('Widget Code') + ' - ' + $t('Only for sub-menu items'));
                    $('#item-type option[value="custom_html"]').prop('disabled', true).text($t('Custom HTML') + ' - ' + $t('Only for sub-menu items'));

                    // If current type is one of these, show warning and reset to link
                    var currentType = $('#item-type').val();
                    if (['cms_block', 'widget', 'custom_html'].indexOf(currentType) !== -1) {
                        self.showNotification('warning', $t('CMS Block, Widget, and Custom HTML can only be used in sub-menu items. Changing to Custom URL.'));
                        $('#item-type').val('link').trigger('change');
                    }
                }, 50);
            }

            // URL mapping for predefined link types (Smart Menu)
            var urlMap = {
                // Customer Pages
                'account': '/customer/account',
                'account_orders': '/sales/order/history',
                'account_wishlist': '/wishlist',
                'account_addresses': '/customer/address',
                'account_edit': '/customer/account/edit',

                // Store Pages
                'contact': '/contact',
                'about': '/about-us',
                'store_locator': '/stores',
                'faq': '/faq',
                'blog': '/blog',

                // Catalog & Search
                'catalog_search': '/catalogsearch/result',
                'layered_navigation': '/catalog/category/view',

                // Shopping
                'cart': '/checkout/cart',
                'checkout': '/checkout',
                'compare': '/catalog/product_compare',

                // Common CMS Pages
                'privacy': '/privacy-policy-cookie-restriction-mode',
                'terms': '/terms-and-conditions',
                'shipping': '/shipping-policy',
                'returns': '/returns-policy'
            };

            // Placeholder messages for types that need selection
            var placeholderMap = {
                'link': 'https://example.com or /page-url',
                'category': 'Click Browse to select a category',
                'product': 'Click Browse to select a product',
                'cms_page': 'Click Browse to select a CMS page',
                'cms_block': 'Click Browse to select a CMS block',
                'brand_page': '/brands/brand-name',
                'attribute_option': '/catalog/category/view?attribute=value'
            };

            // Update URL field based on Link Type
            $('#item-type').off('change').on('change', function() {
                var linkType = $(this).val();
                var urlField = $('#item-url');

                // Hide URL field for types that don't need it
                if (['dropdown', 'divider', 'custom_html'].indexOf(linkType) !== -1) {
                    $('#url-field-container').hide();
                    urlField.val('');
                } else {
                    $('#url-field-container').show();

                    // Auto-populate URL if we have a mapping and field is empty or has placeholder
                    var currentValue = urlField.val();
                    var shouldAutoFill = !currentValue || currentValue === '#' ||
                                       currentValue.indexOf('Click Browse') === 0 ||
                                       currentValue.indexOf('https://example') === 0 ||
                                       Object.values(placeholderMap).indexOf(currentValue) !== -1;

                    if (shouldAutoFill) {
                        if (urlMap[linkType]) {
                            // Set predefined URL
                            urlField.val(urlMap[linkType]);
                            urlField.attr('placeholder', $t('URL auto-populated'));
                        } else if (placeholderMap[linkType]) {
                            // Set placeholder for types needing selection
                            urlField.val('');
                            urlField.attr('placeholder', placeholderMap[linkType]);
                        } else {
                            // Default placeholder
                            urlField.attr('placeholder', $t('Enter URL or click Browse'));
                        }
                    }
                }
            });

            // Trigger initial update
            var initialType = $('#item-type').val();
            if (['dropdown', 'divider', 'custom_html'].indexOf(initialType) !== -1) {
                $('#url-field-container').hide();
            }

            // Browse content button click - opens unified content picker
            $('#browse-content-btn').off('click').on('click', function() {
                self.openContentBrowser();
            });
        },

        /**
         * Open unified content browser
         */
        openContentBrowser: function () {
            var self = this;

            // Close any existing content browser modal
            if (this.contentBrowserModal) {
                try {
                    this.contentBrowserModal.closeModal();
                } catch (e) {}
            }

            var browserContent = `
                <div class="content-browser-container">
                    <div class="admin__field" style="margin-bottom: 20px;">
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="text"
                                   class="content-search-input admin__control-text"
                                   placeholder="${$t('Search categories, products, pages...')}"
                                   style="flex: 1;" />
                            <select class="content-type-filter admin__control-select" style="width: 150px;">
                                <option value="all">${$t('All Content')}</option>
                                <option value="category">${$t('Categories')}</option>
                                <option value="product">${$t('Products')}</option>
                                <option value="cms_page">${$t('CMS Pages')}</option>
                            </select>
                        </div>
                    </div>

                    <div class="content-results-container" style="max-height: 500px; overflow-y: auto; border: 1px solid #ddd; border-radius: 3px; background: #fff;">
                        <div style="text-align: center; padding: 40px; color: #999;">
                            ${$t('Start typing to search, or select Categories to browse all')}
                        </div>
                    </div>
                </div>

                <style>
                    .content-item {
                        padding: 12px 15px;
                        border-bottom: 1px solid #eee;
                        cursor: pointer;
                        transition: background 0.2s;
                    }
                    .content-item:hover {
                        background: #f5f5f5;
                    }
                    .content-item-title {
                        font-weight: 600;
                        color: #333;
                        margin-bottom: 4px;
                    }
                    .content-item-meta {
                        font-size: 12px;
                        color: #888;
                    }
                    .content-item-type {
                        display: inline-block;
                        padding: 2px 8px;
                        border-radius: 3px;
                        font-size: 11px;
                        font-weight: 600;
                        margin-right: 8px;
                    }
                    .content-item-type.type-category { background: #e3f2fd; color: #1976d2; }
                    .content-item-type.type-product { background: #f3e5f5; color: #7b1fa2; }
                    .content-item-type.type-cms_page { background: #e8f5e9; color: #388e3c; }
                    .content-item-type.type-cms_block { background: #fff3e0; color: #f57c00; }
                </style>
            `;

            var browserElement = $('<div/>').html(browserContent);

            var browserOptions = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: $t('Browse Content'),
                modalClass: 'content-browser-modal',
                buttons: [{
                    text: $t('Cancel'),
                    class: 'action-secondary action-dismiss',
                    click: function () {
                        this.closeModal();
                    }
                }],
                closed: function() {
                    // Clean up when modal is closed
                    self.contentBrowserModal = null;
                }
            };

            this.contentBrowserModal = modal(browserOptions, browserElement);
            browserElement.modal('openModal');

            // Initialize search with a slight delay to ensure DOM is ready
            setTimeout(function() {
                self.initializeContentBrowser(browserElement);
            }, 150);
        },

        /**
         * Initialize content browser
         */
        initializeContentBrowser: function (modalElement) {
            var self = this;
            var searchTimer;

            // Find elements within this specific modal using class selectors
            var $searchInput = modalElement.find('.content-search-input');
            var $typeFilter = modalElement.find('.content-type-filter');
            var $resultsContainer = modalElement.find('.content-results-container');

            // Store references for use in other methods
            this.currentBrowserModal = {
                searchInput: $searchInput,
                typeFilter: $typeFilter,
                resultsContainer: $resultsContainer
            };

            // Search input
            $searchInput.on('input', function() {
                clearTimeout(searchTimer);
                var searchTerm = $(this).val();
                var contentType = $typeFilter.val();

                searchTimer = setTimeout(function() {
                    self.searchContent(searchTerm, contentType);
                }, 300);
            });

            // Type filter
            $typeFilter.on('change', function() {
                var contentType = $(this).val();
                var searchTerm = $searchInput.val();

                if (contentType === 'category' && !searchTerm) {
                    // Load all categories when category filter selected
                    self.searchContent('', 'category');
                } else {
                    self.searchContent(searchTerm, contentType);
                }
            });
        },

        /**
         * Search content across all types
         */
        searchContent: function (searchTerm, contentType) {
            var self = this;

            // Get the results container from stored reference
            if (!this.currentBrowserModal || !this.currentBrowserModal.resultsContainer) {
                return;
            }

            var $resultsContainer = this.currentBrowserModal.resultsContainer;

            // Show loading
            $resultsContainer.html('<div style="text-align: center; padding: 40px;"><div class="loader"></div><p>' + $t('Searching...') + '</p></div>');

            $.ajax({
                url: self.getCategoriesUrl,
                type: 'GET',
                data: {
                    type: contentType === 'all' ? 'all' : contentType,
                    search: searchTerm
                },
                dataType: 'json',
                success: function(response) {
                    self.renderContentResults(response, searchTerm);
                },
                error: function(xhr, status, error) {
                    var errorMsg = $t('Error loading content. Please try again.');
                    if (xhr.responseText) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                errorMsg = response.message;
                            }
                        } catch (e) {
                            // Response is not JSON
                            if (xhr.responseText.length < 500) {
                                errorMsg = xhr.responseText;
                            }
                        }
                    }
                    $resultsContainer.html(
                        '<div class="message message-error" style="margin: 20px;">' +
                        errorMsg + '<br><small>Status: ' + xhr.status + '</small>' +
                        '</div>'
                    );
                }
            });
        },

        /**
         * Render content search results
         */
        renderContentResults: function (response, searchTerm) {
            var self = this;
            var html = '';
            var hasResults = false;

            // Get the results container from stored reference
            if (!this.currentBrowserModal || !this.currentBrowserModal.resultsContainer) {
                return;
            }

            var $resultsContainer = this.currentBrowserModal.resultsContainer;

            // Check if response has error
            if (response.error) {
                $resultsContainer.html(
                    '<div class="message message-error" style="margin: 20px;">' +
                    (response.message || 'Unknown error') +
                    '</div>'
                );
                return;
            }

            // Render categories
            if (response.categories && response.categories.length > 0) {
                response.categories.forEach(function(item) {
                    hasResults = true;
                    var indent = (item.level || 0) * 20;
                    // Use the URL from backend which has proper rewrites
                    var categoryUrl = item.url || '/catalog/category/view/id/' + item.id;
                    html += `
                        <div class="content-item" data-url="${categoryUrl}" style="padding-left: ${15 + indent}px;">
                            <div class="content-item-title">
                                <span class="content-item-type type-category">${$t('Category')}</span>
                                ${self.escapeHtml(item.name)}
                            </div>
                            <div class="content-item-meta">ID: ${item.id} | URL: ${categoryUrl}</div>
                        </div>
                    `;
                });
            }

            // Render products
            if (response.products && response.products.length > 0) {
                response.products.forEach(function(item) {
                    hasResults = true;
                    html += `
                        <div class="content-item" data-url="${item.url}">
                            <div class="content-item-title">
                                <span class="content-item-type type-product">${$t('Product')}</span>
                                ${self.escapeHtml(item.name)}
                            </div>
                            <div class="content-item-meta">SKU: ${item.sku}</div>
                        </div>
                    `;
                });
            }

            // Render CMS pages
            if (response.pages && response.pages.length > 0) {
                response.pages.forEach(function(item) {
                    hasResults = true;
                    html += `
                        <div class="content-item" data-url="/${item.identifier}">
                            <div class="content-item-title">
                                <span class="content-item-type type-cms_page">${$t('CMS Page')}</span>
                                ${self.escapeHtml(item.title)}
                            </div>
                            <div class="content-item-meta">${item.identifier}</div>
                        </div>
                    `;
                });
            }

            // Render CMS blocks (removed since we removed it from browse filter)
            if (response.blocks && response.blocks.length > 0) {
                response.blocks.forEach(function(item) {
                    hasResults = true;
                    html += `
                        <div class="content-item" data-url="{{block class=\\"Magento\\\\Cms\\\\Block\\\\Block\\" block_id=\\"${item.identifier}\\"}}">
                            <div class="content-item-title">
                                <span class="content-item-type type-cms_block">${$t('CMS Block')}</span>
                                ${self.escapeHtml(item.title)}
                            </div>
                            <div class="content-item-meta">${item.identifier}</div>
                        </div>
                    `;
                });
            }

            if (!hasResults) {
                html = '<div style="text-align: center; padding: 40px; color: #999;">' +
                       $t('No results found. Try a different search term.') +
                       '</div>';
            }

            $resultsContainer.html(html);

            // Add click handlers to items within this specific modal
            $resultsContainer.find('.content-item').on('click', function() {
                var url = $(this).data('url');
                $('#item-url').val(url);
                if (self.contentBrowserModal) {
                    self.contentBrowserModal.closeModal();
                }
                self.showNotification('success', $t('Content URL set successfully'));
            });
        },

        /**
         * Show notification
         */
        showNotification: function (type, message) {
            var className = type === 'success' ? 'message-success' : 'message-error';
            var notification = $('<div class="messages"><div class="message ' + className + '"><div>' + message + '</div></div></div>');
            $('.menu-item-edit-modal .modal-content').prepend(notification);

            setTimeout(function() {
                notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },

        /**
         * Create edit form HTML
         */
        createEditForm: function (item) {
            // Use the comprehensive form generator
            return itemForm.generateForm(item);
        },

        /**
         * Save edited item from form fields (comprehensive form)
         */
        saveEditedItem: function() {
            var item = this.findItemById(this.editingItemId);
            if (!item) return;

            // Read all field values from the comprehensive form
            item.title = $('#item-title').val() || '';
            item.url = $('#item-url').val() || '';
            item.item_type = $('#item-type').val() || 'link';
            item.target = $('#item-target').val() || '_self';
            item.rel = $('#item-rel').val() || '';

            // Content fields
            item.custom_content = $('#item-custom-content').val() || '';
            item.cms_block = $('#item-cms-block').val() || '';

            // Design fields - Icons & Images
            item.icon = $('#item-icon').val() || '';
            item.image = $('#item-image').val() || '';
            item.image_width = $('#item-image-width').val() || '';
            item.image_height = $('#item-image-height').val() || '';
            item.display_type = $('#item-display-type').val() || '';
            item.badge = $('#item-badge').val() || '';
            item.badge_text = $('#item-badge-text').val() || '';

            // Design fields - Layout & Styling
            item.column_width = $('#item-column-width').val() || '';
            item.css_class = $('#item-css-class').val() || '';
            item.bg_color = $('#item-bg-color').val() || '';
            item.text_color = $('#item-text-color').val() || '';

            // Design fields - Spacing & Dimensions
            item.padding = $('#item-padding').val() || '';
            item.margin = $('#item-margin').val() || '';
            item.width = $('#item-width').val() || '';
            item.height = $('#item-height').val() || '';
            item.border = $('#item-border').val() || '';
            item.border_radius = $('#item-border-radius').val() || '';

            // Advanced fields - Animation & Effects
            item.animation = $('#item-animation').val() || '';
            item.hover_effect = $('#item-hover-effect').val() || '';
            item.hover_bg_color = $('#item-hover-bg-color').val() || '';
            item.hover_text_color = $('#item-hover-text-color').val() || '';

            // Advanced fields - Typography
            item.font_family = $('#item-font-family').val() || '';
            item.font_size = $('#item-font-size').val() || '';
            item.font_weight = $('#item-font-weight').val() || '';
            item.text_transform = $('#item-text-transform').val() || '';

            // Advanced fields - Shadow & Effects
            item.box_shadow = $('#item-box-shadow').val() || '';
            item.text_shadow = $('#item-text-shadow').val() || '';
            item.opacity = $('#item-opacity').val() || '';

            // Advanced fields - Behavior
            item.tooltip = $('#item-tooltip').val() || '';
            item.click_action = $('#item-click-action').val() || '';
            item.data_attributes = $('#item-data-attributes').val() || '';

            // Advanced fields - Accessibility
            item.aria_label = $('#item-aria-label').val() || '';
            item.role = $('#item-role').val() || '';

            // Visibility fields - Display Status
            item.is_active = parseInt($('#item-is-active').val()) || 0;
            item.sort_order = $('#item-sort-order').val() || '';

            // Visibility fields - Device Visibility
            item.show_on_desktop = parseInt($('#item-show-on-desktop').val()) || 0;
            item.show_on_tablet = parseInt($('#item-show-on-tablet').val() || 1);
            item.show_on_mobile = parseInt($('#item-show-on-mobile').val() || 1);

            // Visibility fields - Schedule
            item.start_date = $('#item-start-date').val() || '';
            item.end_date = $('#item-end-date').val() || '';

            // Visibility fields - Store Views & Customer Groups
            var storeViews = [];
            $('.item-store-views:checked').each(function() {
                storeViews.push($(this).val());
            });
            item.store_views = storeViews.length > 0 ? storeViews : [];

            var customerGroups = [];
            $('.item-customer-groups:checked').each(function() {
                customerGroups.push($(this).val());
            });
            item.customer_groups = customerGroups.length > 0 ? customerGroups : [];

            // Update observable to trigger UI refresh
            this.items.valueHasMutated();
            this.saveItems();

            // Refresh sortable after save
            var self = this;
            setTimeout(function () {
                self.refreshSortable();
            }, 300);
        },

        /**
         * Save edited item from Knockout observables
         */
        saveEditedItemKo: function() {
            var self = this;

            var item = this.findItemById(this.editingItemId);
            if (!item) {
                return;
            }
            if (!this.editingItem()) {
                return;
            }

            var editableItem = this.editingItem();
            var oldTitle = item.title;

            // Copy all observable values back to the original item
            var fields = [
                'title', 'url', 'item_type', 'target', 'rel',
                'custom_content', 'cms_block',
                'icon', 'image', 'image_width', 'image_height', 'display_type', 'badge', 'badge_text',
                'column_width', 'css_class', 'bg_color', 'text_color',
                'padding', 'margin', 'width', 'height', 'border', 'border_radius',
                'animation', 'hover_effect', 'hover_bg_color', 'hover_text_color',
                'font_family', 'font_size', 'font_weight', 'text_transform',
                'box_shadow', 'text_shadow', 'opacity',
                'tooltip', 'click_action', 'data_attributes',
                'aria_label', 'role',
                'sort_order', 'start_date', 'end_date'
            ];

            fields.forEach(function(field) {
                if (editableItem[field] && ko.isObservable(editableItem[field])) {
                    item[field] = editableItem[field]();
                }
            });

            // Handle numeric fields
            if (editableItem.is_active && ko.isObservable(editableItem.is_active)) {
                item.is_active = parseInt(editableItem.is_active()) || 0;
            }
            if (editableItem.show_on_desktop && ko.isObservable(editableItem.show_on_desktop)) {
                item.show_on_desktop = parseInt(editableItem.show_on_desktop()) || 0;
            }
            if (editableItem.show_on_tablet && ko.isObservable(editableItem.show_on_tablet)) {
                item.show_on_tablet = parseInt(editableItem.show_on_tablet()) || 0;
            }
            if (editableItem.show_on_mobile && ko.isObservable(editableItem.show_on_mobile)) {
                item.show_on_mobile = parseInt(editableItem.show_on_mobile()) || 0;
            }

            // Handle arrays
            if (editableItem.store_views && ko.isObservable(editableItem.store_views)) {
                item.store_views = editableItem.store_views() || [];
            }
            if (editableItem.customer_groups && ko.isObservable(editableItem.customer_groups)) {
                item.customer_groups = editableItem.customer_groups() || [];
            }

            // Trigger observable update
            this.items.valueHasMutated();
            this.saveItems();

            // Refresh sortable after save
            setTimeout(function () {
                self.refreshSortable();
            }, 300);
        },

        /**
         * Show content browser modal for categories, products, pages
         */
        showContentBrowser: function(itemType, callback) {
            var self = this;

            // Build type selector options based on context
            var typeOptions = '';
            if (itemType === 'cms_block') {
                // CMS Block Identifier browse - only show cms_block
                typeOptions = `<option value="cms_block">${$t('CMS Blocks')}</option>`;
            } else {
                // URL/Link browse - show all except cms_block
                typeOptions = `
                    <option value="category">${$t('Categories')}</option>
                    <option value="product">${$t('Products')}</option>
                    <option value="cms_page">${$t('CMS Pages')}</option>
                `;
            }

            var browserHtml = `
                <div class="content-browser">
                    <div class="browser-controls" style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center;">
                        <input type="text" id="browser-search" class="admin__control-text"
                               placeholder="${$t('Search...')}" style="flex: 1;" />
                        <select id="browser-type" class="admin__control-select" style="width: 200px;">
                            ${typeOptions}
                        </select>
                        <select id="browser-page-size" class="admin__control-select" style="width: 120px;">
                            <option value="5" selected>5 per page</option>
                            <option value="10">10 per page</option>
                            <option value="20">20 per page</option>
                        </select>
                    </div>
                    <div id="browser-results" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-bottom: 15px;">
                        <div class="loader" style="text-align: center; padding: 20px;">
                            <span>${$t('Loading...')}</span>
                        </div>
                    </div>
                    <div id="browser-pagination" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f5f5f5; border-radius: 3px;">
                        <div id="browser-info" style="color: #666; font-size: 13px;"></div>
                        <div id="browser-pages" style="display: flex; gap: 5px;"></div>
                    </div>
                </div>
            `;

            var modalElement = $(browserHtml);
            var modalInstance;

            var currentPage = 1;
            var pageSize = 5;

            var loadContent = function(type, search, page) {
                page = page || 1;
                currentPage = page;
                pageSize = parseInt(modalElement.find('#browser-page-size').val()) || 5;

                // Show loading - scope to modalElement
                modalElement.find('#browser-results').html('<div class="loader" style="text-align: center; padding: 20px;"><span>' + $t('Loading...') + '</span></div>');

                $.ajax({
                    url: self.getCategoriesUrl + '?_=' + new Date().getTime(),
                    data: {
                        type: type,
                        search: search || '',
                        page: page,
                        pageSize: pageSize,
                        isAjax: true
                    },
                    method: 'POST',
                    dataType: 'json',
                    cache: false,
                    success: function(response) {
                        try {
                            var resultsHtml = '';
                            var totalCount = response.total_count || 0;
                            var items = [];

                            if (type === 'category' && response.categories) {
                                items = response.categories;
                                items.forEach(function(cat) {
                                    resultsHtml += `
                                        <div class="browser-item" data-url="${cat.url}" data-title="${cat.name}"
                                             style="padding: 10px; border-bottom: 1px solid #eee; cursor: pointer;">
                                            <div style="font-weight: 600;">${cat.name}</div>
                                            <div style="font-size: 12px; color: #666;">${cat.url}</div>
                                        </div>
                                    `;
                                });
                        } else if (type === 'product' && response.products) {
                            items = response.products;
                            items.forEach(function(prod) {
                                resultsHtml += `
                                    <div class="browser-item" data-url="${prod.url}" data-title="${prod.name}"
                                         style="padding: 10px; border-bottom: 1px solid #eee; cursor: pointer;">
                                        <div style="font-weight: 600;">${prod.name}</div>
                                        <div style="font-size: 12px; color: #666;">SKU: ${prod.sku} | ${prod.url}</div>
                                    </div>
                                `;
                            });
                        } else if (type === 'cms_page' && response.pages) {
                            items = response.pages;
                            items.forEach(function(page) {
                                var url = '/' + page.identifier;
                                resultsHtml += `
                                    <div class="browser-item" data-url="${url}" data-title="${page.title}"
                                         style="padding: 10px; border-bottom: 1px solid #eee; cursor: pointer;">
                                        <div style="font-weight: 600;">${page.title}</div>
                                        <div style="font-size: 12px; color: #666;">${page.identifier}</div>
                                    </div>
                                `;
                            });
                        } else if (type === 'cms_block' && response.blocks) {
                            items = response.blocks;
                            items.forEach(function(block) {
                                resultsHtml += `
                                    <div class="browser-item"
                                         data-url="${block.identifier}"
                                         data-title="${block.title}"
                                         data-identifier="${block.identifier}"
                                         style="padding: 10px; border-bottom: 1px solid #eee; cursor: pointer;">
                                        <div style="font-weight: 600; color: #333;">${block.title}</div>
                                        <div style="font-size: 12px; color: #666; margin-top: 4px;">${block.identifier}</div>
                                    </div>
                                `;
                            });
                        }

                        if (!resultsHtml) {
                            resultsHtml = '<div style="text-align: center; padding: 20px; color: #666;">' + $t('No results found') + '</div>';
                        }

                        modalElement.find('#browser-results').html(resultsHtml);

                        // Render pagination
                        var totalPages = Math.ceil(totalCount / pageSize);
                        var startItem = ((currentPage - 1) * pageSize) + 1;
                        var endItem = Math.min(currentPage * pageSize, totalCount);

                        var infoHtml = totalCount > 0 ?
                            $t('Showing') + ' ' + startItem + '-' + endItem + ' ' + $t('of') + ' ' + totalCount :
                            $t('No items found');
                        modalElement.find('#browser-info').html(infoHtml);

                        var pagesHtml = '';
                        if (totalPages > 1) {
                            // Previous button
                            if (currentPage > 1) {
                                pagesHtml += `<button class="action-default page-btn" data-page="${currentPage - 1}" style="padding: 5px 10px;">&laquo; ${$t('Prev')}</button>`;
                            }

                            // Page numbers
                            var startPage = Math.max(1, currentPage - 2);
                            var endPage = Math.min(totalPages, currentPage + 2);

                            if (startPage > 1) {
                                pagesHtml += `<button class="action-default page-btn" data-page="1" style="padding: 5px 10px;">1</button>`;
                                if (startPage > 2) {
                                    pagesHtml += `<span style="padding: 5px 10px;">...</span>`;
                                }
                            }

                            for (var i = startPage; i <= endPage; i++) {
                                var activeClass = i === currentPage ? 'background: #1979c3; color: white;' : '';
                                pagesHtml += `<button class="action-default page-btn" data-page="${i}" style="padding: 5px 10px; ${activeClass}">${i}</button>`;
                            }

                            if (endPage < totalPages) {
                                if (endPage < totalPages - 1) {
                                    pagesHtml += `<span style="padding: 5px 10px;">...</span>`;
                                }
                                pagesHtml += `<button class="action-default page-btn" data-page="${totalPages}" style="padding: 5px 10px;">${totalPages}</button>`;
                            }

                            // Next button
                            if (currentPage < totalPages) {
                                pagesHtml += `<button class="action-default page-btn" data-page="${currentPage + 1}" style="padding: 5px 10px;">${$t('Next')} &raquo;</button>`;
                            }
                        }
                        modalElement.find('#browser-pages').html(pagesHtml);

                        // Handle pagination clicks using event delegation
                        modalElement.find('#browser-pages').off('click', '.page-btn').on('click', '.page-btn', function() {
                            var page = $(this).data('page');
                            var type = modalElement.find('#browser-type').val();
                            var search = modalElement.find('#browser-search').val();
                            loadContent(type, search, page);
                        });

                        // Handle item click using event delegation
                        modalElement.find('#browser-results').off('click', '.browser-item').on('click', '.browser-item', function() {
                            var url = $(this).data('url');
                            var title = $(this).data('title');
                            var identifier = $(this).data('identifier');
                            callback(url, title, identifier);
                            modalInstance.closeModal();
                        });

                        } catch (e) {
                            modalElement.find('#browser-results').html('<div style="text-align: center; padding: 20px; color: #e02b27;">Error rendering content: ' + e.message + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        modalElement.find('#browser-results').html('<div style="text-align: center; padding: 20px; color: #e02b27;">' + $t('Error loading content') + ': ' + error + '</div>');
                        modalElement.find('#browser-info').html('');
                        modalElement.find('#browser-pages').html('');
                    }
                });
            };

            var options = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: $t('Browse Content'),
                modalClass: 'content-browser-modal',
                buttons: [{
                    text: $t('Cancel'),
                    class: 'action-secondary action-dismiss',
                    click: function() {
                        this.closeModal();
                    }
                }],
                opened: function() {
                    var initialType = itemType === 'category' || itemType === 'product' || itemType === 'cms_page' || itemType === 'cms_block' ? itemType : 'category';

                    // Clear previous content immediately
                    modalElement.find('#browser-results').html('');
                    modalElement.find('#browser-info').html('');
                    modalElement.find('#browser-pages').html('');

                    // Reset search input
                    modalElement.find('#browser-search').val('');

                    // Set dropdown value
                    modalElement.find('#browser-type').val(initialType);

                    // Reset page size to default
                    modalElement.find('#browser-page-size').val('5');

                    // Hide type selector if browsing CMS blocks only
                    if (itemType === 'cms_block') {
                        modalElement.find('#browser-type').hide();
                    } else {
                        modalElement.find('#browser-type').show();
                    }

                    // Load content with the correct type
                    loadContent(initialType, '', 1);

                    // Clean up and attach search handler
                    modalElement.find('#browser-search').off('input').on('input', _.debounce(function() {
                        var search = $(this).val();
                        var type = modalElement.find('#browser-type').val();
                        loadContent(type, search, 1);
                    }, 300));

                    // Clean up and attach type change handler
                    modalElement.find('#browser-type').off('change').on('change', function() {
                        var type = $(this).val();
                        var search = modalElement.find('#browser-search').val();
                        loadContent(type, search, 1);
                    });

                    // Clean up and attach page size change handler
                    modalElement.find('#browser-page-size').off('change').on('change', function() {
                        var type = modalElement.find('#browser-type').val();
                        var search = modalElement.find('#browser-search').val();
                        loadContent(type, search, 1);
                    });
                },
                closed: function() {
                    // Clean up event handlers when modal closes
                    modalElement.find('#browser-search').off('input');
                    modalElement.find('#browser-type').off('change');
                    modalElement.find('#browser-page-size').off('change');
                    modalElement.find('#browser-results').off('click', '.browser-item');
                    modalElement.find('#browser-pages').off('click', '.page-btn');
                }
            };

            modalInstance = modal(options, modalElement);
            modalElement.modal('openModal');
        },


        /**
         * Initialize WYSIWYG editor for HTML fields
         */
        initializeWysiwyg: function() {
            // Use plain textareas for now - Magento's WYSIWYG integration is complex
            // and requires proper form field setup. Users can still enter HTML directly.

            // Add a note to the textareas
            var self = this;
            setTimeout(function() {
                var customContentEl = $('.menu-edit-modal-container').find('textarea[data-bind*="custom_content"]');

                if (customContentEl.length && !customContentEl.next('.field-note').length) {
                    customContentEl.after('<div class="field-note" style="margin-top: 5px; color: #666; font-size: 12px;">You can use HTML tags in this field</div>');
                }
            }, 100);
        },

        /**
         * Preview menu in different device modes (LIVE PREVIEW - no save required)
         */
        previewMenu: function () {
            var self = this;

            // Get latest form data
            var formData = this.getFormData();

            // Generate UNIQUE random secret key for security (single-use only, always new)
            // Format: preview_{timestamp}_{random1}_{random2} for maximum uniqueness
            var timestamp = Date.now();
            var random1 = Math.random().toString(36).substr(2, 9);
            var random2 = Math.random().toString(36).substr(2, 9);
            var secretKey = 'preview_' + timestamp + '_' + random1 + '_' + random2;
            formData.secret_key = secretKey;

            // Create temporary form for POST submission to new tab
            var form = $('<form>', {
                method: 'POST',
                action: window.location.origin + '/panth_menu/preview/index?key=' + secretKey,
                target: '_blank'
            });

            // Add all form data as hidden fields
            $.each(formData, function(key, value) {
                if (key === 'items_json') {
                    // Use textarea for large JSON data to prevent HTML encoding issues
                    form.append($('<textarea>', {
                        name: key,
                        css: { display: 'none' },
                        text: value
                    }));
                } else {
                    form.append($('<input>', {
                        type: 'hidden',
                        name: key,
                        value: value
                    }));
                }
            });

            // Append to body, submit to new tab, then remove
            form.appendTo('body').submit().remove();
        },

        /**
         * Get form data
         */
        getFormData: function() {
            var self = this;
            var data = {
                items_json: '[]',
                container_bg_color: '',
                container_padding: '',
                container_margin: '',
                item_gap: '',
                container_max_width: '',
                container_border: '',
                container_border_radius: '',
                container_box_shadow: '',
                custom_css: '',
                css_class: ''
            };

            // Get container styling fields
            var containerBgColor = $('input[name="container_bg_color"]').val();
            if (containerBgColor) data.container_bg_color = containerBgColor;

            var containerPadding = $('input[name="container_padding"]').val();
            if (containerPadding) data.container_padding = containerPadding;

            var containerMargin = $('input[name="container_margin"]').val();
            if (containerMargin) data.container_margin = containerMargin;

            var itemGap = $('input[name="item_gap"]').val();
            if (itemGap) data.item_gap = itemGap;

            var containerMaxWidth = $('input[name="container_max_width"]').val();
            if (containerMaxWidth) data.container_max_width = containerMaxWidth;

            var containerBorder = $('input[name="container_border"]').val();
            if (containerBorder) data.container_border = containerBorder;

            var containerBorderRadius = $('input[name="container_border_radius"]').val();
            if (containerBorderRadius) data.container_border_radius = containerBorderRadius;

            var containerBoxShadow = $('input[name="container_box_shadow"]').val();
            if (containerBoxShadow) data.container_box_shadow = containerBoxShadow;

            // Get custom CSS
            var customCss = $('textarea[name="custom_css"]').val();
            if (customCss) data.custom_css = customCss;

            // Get CSS class
            var cssClass = $('input[name="css_class"]').val();
            if (cssClass) data.css_class = cssClass;

            // Get items from the builder (current LIVE state from observable)
            var currentItems = this.items() || [];

            data.items_json = JSON.stringify(currentItems);

            return data;
        },

        /**
         * Build preview URL
         */
        buildPreviewUrl: function(formData) {
            var baseUrl = window.location.origin + '/';
            return baseUrl + 'panth_menu/preview/index';
        },

        /**
         * Create preview modal HTML
         */
        createPreviewModalWithForm: function(formData) {
            var formHtml = '<form id="preview-form" method="POST" action="' + window.location.origin + '/panth_menu/preview/index" target="megamenu-preview-iframe">';

            // Add form fields - use textarea for large data like items_json
            for (var key in formData) {
                if (formData.hasOwnProperty(key)) {
                    var value = formData[key];
                    // Use textarea for items_json to handle large data properly
                    if (key === 'items_json') {
                        formHtml += '<textarea name="' + key + '" style="display:none;">' +
                                   $('<div/>').text(value).html() + '</textarea>';
                    } else {
                        formHtml += '<input type="hidden" name="' + key + '" value="' +
                                   $('<div/>').text(value).html() + '">';
                    }
                }
            }

            formHtml += '</form>';
            
            return `
                <div class="preview-device-selector">
                    <button type="button" class="preview-device-btn action-default active" data-device="desktop">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="2" y="3" width="20" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="2"/>
                            <path d="M8 21h8M12 17v4"/>
                        </svg>
                        <span>${$t('Desktop')}</span>
                    </button>
                    <button type="button" class="preview-device-btn action-default" data-device="tablet">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="5" y="2" width="14" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="19" r="1" fill="currentColor"/>
                        </svg>
                        <span>${$t('Tablet')}</span>
                    </button>
                    <button type="button" class="preview-device-btn action-default" data-device="mobile">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="7" y="2" width="10" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="19" r="1" fill="currentColor"/>
                        </svg>
                        <span>${$t('Mobile')}</span>
                    </button>
                </div>
                ${formHtml}
                <div class="preview-iframe-wrapper">
                    <iframe id="megamenu-preview-iframe" name="megamenu-preview-iframe" frameborder="0"></iframe>
                    <div class="preview-loading">
                        <div class="preview-spinner"></div>
                        <p>${$t('Loading preview...')}</p>
                    </div>
                </div>
            `;
        },

        /**
         * Initialize device switcher
         */
        initializeDeviceSwitcher: function(previewElement) {
            var $iframe = previewElement.find('#megamenu-preview-iframe');
            var $wrapper = previewElement.find('.preview-iframe-wrapper');
            var $loading = previewElement.find('.preview-loading');

            // Remove any previous load handlers to avoid duplicates
            $iframe.off('load');

            // Hide loading when iframe loads
            $iframe.on('load', function() {
                $loading.fadeOut(300);
            });

            // Fallback: hide loading after 3 seconds if iframe didn't load
            setTimeout(function() {
                if ($loading.is(':visible')) {
                    $loading.fadeOut(300);
                }
            }, 3000);

            // Device switcher
            previewElement.find('.preview-device-btn').on('click', function() {
                var device = $(this).data('device');

                previewElement.find('.preview-device-btn').removeClass('active');
                $(this).addClass('active');

                $wrapper.removeClass('device-desktop device-tablet device-mobile');
                $wrapper.addClass('device-' + device);
            });
        },

        /**
         * Initialize refresh button
         */
        initializeRefreshButton: function(previewElement) {
            var self = this;

            previewElement.find('.preview-refresh-btn').on('click', function() {

                // Get latest form data (including all unsaved changes)
                var formData = self.getFormData();

                // Add timestamp to force reload
                formData._timestamp = new Date().getTime();

                // Show loading
                var $loading = previewElement.find('.preview-loading');
                $loading.show();

                // Reset iframe to blank first
                var $iframe = previewElement.find('#megamenu-preview-iframe');
                $iframe.attr('src', 'about:blank');

                // Remove old form
                previewElement.find('#preview-form').remove();

                // Wait a moment for iframe to clear, then submit new form
                setTimeout(function() {
                    // Create new form with updated data
                    var formHtml = '<form id="preview-form" method="POST" action="' + window.location.origin + '/panth_menu/preview/index" target="megamenu-preview-iframe">';

                    for (var key in formData) {
                        if (formData.hasOwnProperty(key)) {
                            var value = formData[key];
                            // Use textarea for items_json to handle large data properly
                            if (key === 'items_json') {
                                formHtml += '<textarea name="' + key + '" style="display:none;">' +
                                           $('<div/>').text(value).html() + '</textarea>';
                            } else {
                                formHtml += '<input type="hidden" name="' + key + '" value="' +
                                           $('<div/>').text(value).html() + '">';
                            }
                        }
                    }

                    formHtml += '</form>';

                    // Add form and submit
                    previewElement.find('.preview-device-selector').after(formHtml);
                    previewElement.find('#preview-form').submit();
                }, 100);
            });
        },

        /**
         * Delete item
         */
        deleteItem: function (itemId) {
            var self = this;

            confirm({
                title: $t('Delete Menu Item'),
                content: $t('Are you sure you want to delete this item and all its children? This action cannot be undone.'),
                actions: {
                    confirm: function () {
                        var toDelete = [itemId];

                        // Find all children recursively
                        function findChildren(parentId) {
                            self.items().forEach(function (item) {
                                if (item.parent_id == parentId) {
                                    toDelete.push(item.item_id);
                                    findChildren(item.item_id);
                                }
                            });
                        }
                        findChildren(itemId);

                        // Remove items
                        var newItems = self.items().filter(function (item) {
                            return toDelete.indexOf(item.item_id) === -1;
                        });

                        self.items(newItems);
                        self.saveItems();

                        // Reinitialize sortable after deleting items
                        setTimeout(function () {
                            self.refreshSortable();
                        }, 300);
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },

        /**
         * Import all categories
         */
        importAllCategories: function () {
            var self = this;

            confirm({
                title: $t('Import All Categories'),
                content: $t('This will add categories to your existing menu items. Do you want to continue?'),
                actions: {
                    confirm: function () {
                        // Show loading
                        $('body').trigger('processStart');

                        $.ajax({
                            url: self.getCategoriesUrl,
                            type: 'GET',
                            data: {
                                type: 'category'
                            },
                            dataType: 'json',
                            success: function (response) {
                                $('body').trigger('processStop');

                                // Check for error in response
                                if (response.error) {
                                    alert({
                                        title: $t('Import Failed'),
                                        content: $t('Error: ') + (response.message || 'Unknown error'),
                                        buttons: [{
                                            text: $t('OK'),
                                            class: 'action-primary action-accept',
                                            click: function () {
                                                this.closeModal();
                                            }
                                        }]
                                    });
                                    if (response.trace) {
                                    }
                                    return;
                                }

                                if (response.categories && response.categories.length > 0) {
                                    var categories = response.categories;
                                    var importedItems = [];
                                    var categoryIdMap = {}; // Map category ID to new menu item ID
                                    var processedCategories = {}; // Track processed categories to avoid duplicates

                                    // Get existing items and calculate starting position
                                    var existingItems = self.items() || [];
                                    var startPosition = existingItems.length;

                                    // Sort categories by level to ensure parents are processed first
                                    categories.sort(function(a, b) {
                                        return (a.level || 0) - (b.level || 0);
                                    });

                                    // Convert categories to menu items with ALL FIELDS
                                    // Use nextItemId for unique IDs to avoid conflicts with existing items
                                    categories.forEach(function (category) {
                                        // Skip if already processed
                                        if (processedCategories[category.id]) {
                                            return;
                                        }
                                        processedCategories[category.id] = true;

                                        var menuItemId = 'new_' + self.nextItemId++;

                                        // Map parent_id - only if parent exists in our imported items map
                                        var parentMenuItemId = 0;
                                        if (category.parent_id && category.parent_id > 2 && categoryIdMap[category.parent_id]) {
                                            parentMenuItemId = categoryIdMap[category.parent_id];
                                        }

                                        // Calculate actual level based on parent
                                        var actualLevel = 0;
                                        if (parentMenuItemId !== 0) {
                                            var parentItem = importedItems.find(function(item) {
                                                return item.item_id === parentMenuItemId;
                                            });
                                            if (parentItem) {
                                                actualLevel = (parentItem.level || 0) + 1;
                                            }
                                        }

                                        // Create menu item with ALL fields properly set
                                        var menuItem = {
                                            // Core Fields
                                            item_id: menuItemId,
                                            title: category.name,
                                            url: category.url || '#',
                                            item_type: 'category',
                                            parent_id: parentMenuItemId,
                                            position: startPosition + importedItems.length,
                                            level: actualLevel,
                                            category_id: category.id,

                                            // General Tab
                                            target: '_self',
                                            rel: '',

                                            // Content Tab
                                            custom_content: '',
                                            cms_block: '',

                                            // Design Tab - Icons & Images
                                            icon: '',
                                            image: category.image || '',
                                            image_width: '',
                                            image_height: '',
                                            display_type: '',
                                            badge: '',
                                            badge_text: '',

                                            // Design Tab - Layout & Styling
                                            column_width: '',
                                            css_class: 'category-menu-item',
                                            bg_color: '',
                                            text_color: '',

                                            // Design Tab - Spacing & Dimensions
                                            padding: '',
                                            margin: '',
                                            width: '',
                                            height: '',
                                            border: '',
                                            border_radius: '',

                                            // Advanced Tab - Animation & Effects
                                            animation: '',
                                            hover_effect: '',
                                            hover_bg_color: '',
                                            hover_text_color: '',

                                            // Advanced Tab - Typography
                                            font_family: '',
                                            font_size: '',
                                            font_weight: '',
                                            text_transform: '',

                                            // Advanced Tab - Shadow & Effects
                                            box_shadow: '',
                                            text_shadow: '',
                                            opacity: '',

                                            // Advanced Tab - Behavior
                                            tooltip: '',
                                            click_action: '',
                                            data_attributes: '',

                                            // Advanced Tab - Accessibility
                                            aria_label: category.name,
                                            role: '',

                                            // Visibility Tab - CRITICAL FIELDS:
                                            // Use include_in_menu for is_active (controls menu item visibility)
                                            // Use category position for sort_order (maintains Magento sort order)
                                            is_active: (typeof category.include_in_menu !== 'undefined') ? parseInt(category.include_in_menu) : 1,
                                            sort_order: (typeof category.position !== 'undefined') ? parseInt(category.position) : 0,

                                            // Visibility Tab - Device Visibility
                                            show_on_desktop: 1,
                                            show_on_tablet: 1,
                                            show_on_mobile: 1,

                                            // Visibility Tab - Store View
                                            store_views: [],

                                            // Visibility Tab - Schedule
                                            start_date: '',
                                            end_date: '',

                                            // Visibility Tab - Customer Groups
                                            customer_groups: []
                                        };

                                        importedItems.push(menuItem);
                                        categoryIdMap[category.id] = menuItemId;
                                    });

                                    // Append imported items to existing items
                                    var mergedItems = existingItems.concat(importedItems);
                                    self.items([]);
                                    self.items(mergedItems);
                                    self.saveItems();

                                    // Reinitialize sortable
                                    setTimeout(function () {
                                        self.refreshSortable();
                                    }, 500);

                                    alert({
                                        title: $t('Success'),
                                        content: $t('Imported %1 categories (appended to existing items).').replace('%1', importedItems.length),
                                        buttons: [{
                                            text: $t('OK'),
                                            class: 'action-primary action-accept',
                                            click: function () {
                                                this.closeModal();
                                            }
                                        }]
                                    });
                                } else {
                                    alert({
                                        title: $t('Import Failed'),
                                        content: $t('Failed to import categories: ') + (response.message || 'Unknown error'),
                                        buttons: [{
                                            text: $t('OK'),
                                            class: 'action-primary action-accept',
                                            click: function () {
                                                this.closeModal();
                                            }
                                        }]
                                    });
                                }
                            },
                            error: function (xhr, status, error) {
                                $('body').trigger('processStop');

                                var errorMsg = error;
                                if (xhr.responseText) {
                                    // If response looks like HTML, show a generic message
                                    if (xhr.responseText.indexOf('<!') === 0 || xhr.responseText.indexOf('<html') === 0) {
                                        errorMsg = 'Server returned an error page. Please check if you are still logged in.';
                                    } else {
                                        // Try to parse JSON error
                                        try {
                                            var response = JSON.parse(xhr.responseText);
                                            if (response.message) {
                                                errorMsg = response.message;
                                            }
                                        } catch (e) {
                                            errorMsg += ' (Status: ' + xhr.status + ')';
                                        }
                                    }
                                }

                                alert({
                                    title: $t('Import Error'),
                                    content: $t('An error occurred while importing categories: ') + errorMsg,
                                    buttons: [{
                                        text: $t('OK'),
                                        class: 'action-primary action-accept',
                                        click: function () {
                                            this.closeModal();
                                        }
                                    }]
                                });
                            }
                        });
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },

        /**
         * Clear all items
         */
        clearAllItems: function () {
            var self = this;

            confirm({
                title: $t('Clear All Menu Items'),
                content: $t('Are you sure you want to remove all menu items? This action cannot be undone.'),
                actions: {
                    confirm: function () {
                        self.items([]);
                        self.saveItems();

                        alert({
                            title: $t('Success'),
                            content: $t('All menu items have been removed.'),
                            buttons: [{
                                text: $t('OK'),
                                class: 'action-primary action-accept',
                                click: function () {
                                    this.closeModal();
                                }
                            }]
                        });
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },

        /**
         * Load comprehensive demo menu from JSON file
         * Demonstrates ALL MegaMenu capabilities with 70+ items across 4 levels
         */
        loadDemoMenu: function () {
            var self = this;

            confirm({
                title: $t('Load Sample Data Demo Menu'),
                content: $t('This will:<br><br>') +
                         '<ul style="text-align: left; margin: 10px 0; padding-left: 20px;">' +
                         '<li><strong>Remove all existing menu items</strong></li>' +
                         '<li>Create demo CMS blocks</li>' +
                         '<li>Build a demo menu using Magento sample data categories</li>' +
                         '<li>Women, Men, Gear, Collections, Training, Sale</li>' +
                         '<li>Includes badges, CMS block reference, and 3 levels</li>' +
                         '</ul>' +
                         $t('Do you want to continue?'),
                actions: {
                    confirm: function () {
                        // Show loading indicator
                        $('body').trigger('processStart');

                        // First, create demo CMS blocks
                        $.ajax({
                            url: self.createDemoBlocksUrl,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                form_key: window.FORM_KEY
                            },
                            success: function(blocksResponse) {
                                // CMS blocks created successfully, now create demo menu
                                var demoItems = self.generateComprehensiveDemoMenu();

                                // Clear existing items and set demo items
                                self.items([]);
                                self.items(demoItems);
                                self.saveItems();

                                // Reinitialize sortable
                                setTimeout(function () {
                                    self.refreshSortable();
                                }, 500);

                                // Expand all to show the structure
                                setTimeout(function () {
                                    self.expandAll();
                                }, 600);

                                $('body').trigger('processStop');

                                // Show success message
                                alert({
                                    title: $t('Success!'),
                                    content: $t('Sample data demo menu loaded successfully!<br><br>') +
                                             '<strong>' + demoItems.length + ' menu items</strong> created across 3 levels.<br>' +
                                             '<strong>' + (blocksResponse.blocks ? blocksResponse.blocks.length : 0) + ' CMS blocks</strong> created.<br><br>' +
                                             '<div style="margin-top: 15px; padding: 10px; background: #f0f8ff; border-left: 3px solid #1979c3;">' +
                                             '<strong>Categories included:</strong><br>' +
                                             'Women, Men, Gear, Collections, Training, Sale<br>' +
                                             'With subcategories, badges, and CMS block references.' +
                                             '</div>',
                                    buttons: [{
                                        text: $t('OK'),
                                        class: 'action-primary action-accept',
                                        click: function () {
                                            this.closeModal();
                                        }
                                    }]
                                });
                            },
                            error: function(xhr, status, error) {
                                $('body').trigger('processStop');

                                // Even if CMS block creation fails, still create demo menu
                                var demoItems = self.generateComprehensiveDemoMenu();

                                self.items([]);
                                self.items(demoItems);
                                self.saveItems();

                                setTimeout(function () {
                                    self.refreshSortable();
                                    self.expandAll();
                                }, 500);

                                alert({
                                    title: $t('Demo Menu Loaded'),
                                    content: $t('<strong>' + demoItems.length + ' menu items</strong> created successfully.<br><br>') +
                                             $t('Note: CMS blocks creation failed. Some menu items may not display correctly.<br>Error: ') + error,
                                    buttons: [{
                                        text: $t('OK'),
                                        class: 'action-primary action-accept',
                                        click: function () {
                                            this.closeModal();
                                        }
                                    }]
                                });
                            }
                        });
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },

        /**
         * Generate demo menu using Magento sample data categories
         */
        generateComprehensiveDemoMenu: function () {
            var items = [];
            var counter = 1;
            var ts = Date.now();

            function createItem(data) {
                var defaults = {
                    item_id: 'demo_' + ts + '_' + counter++,
                    target: '_self',
                    rel: '',
                    custom_content: '',
                    cms_block: '',
                    icon: '',
                    image: '',
                    image_width: '',
                    image_height: '',
                    display_type: '',
                    badge: '',
                    badge_text: '',
                    column_width: '',
                    css_class: '',
                    bg_color: '',
                    text_color: '',
                    padding: '',
                    margin: '',
                    width: '',
                    height: '',
                    border: '',
                    border_radius: '',
                    animation: '',
                    hover_effect: '',
                    hover_bg_color: '',
                    hover_text_color: '',
                    font_family: '',
                    font_size: '',
                    font_weight: '',
                    text_transform: '',
                    box_shadow: '',
                    text_shadow: '',
                    opacity: '',
                    tooltip: '',
                    click_action: '',
                    data_attributes: '',
                    aria_label: '',
                    role: '',
                    is_active: 1,
                    sort_order: items.length,
                    show_on_desktop: 1,
                    show_on_tablet: 1,
                    show_on_mobile: 1,
                    store_views: [],
                    start_date: '',
                    end_date: '',
                    customer_groups: []
                };

                return Object.assign({}, defaults, data);
            }

            // =========================================================================
            // Magento Sample Data Categories Demo
            // =========================================================================
            // =========================================================================

            // ── LEVEL 0 — Top-level categories ──

            // 1. Women
            items.push(createItem({
                title: 'Women',
                url: '/women.html',
                item_type: 'category',
                parent_id: 0,
                level: 0,
                category_id: '20',
                position: 1
            }));
            var womenId = items[0].item_id;

            // 2. Men
            items.push(createItem({
                title: 'Men',
                url: '/men.html',
                item_type: 'category',
                parent_id: 0,
                level: 0,
                category_id: '11',
                position: 2
            }));
            var menId = items[1].item_id;

            // 3. Gear
            items.push(createItem({
                title: 'Gear',
                url: '/gear.html',
                item_type: 'category',
                parent_id: 0,
                level: 0,
                category_id: '3',
                position: 3
            }));
            var gearId = items[2].item_id;

            // 4. Collections
            items.push(createItem({
                title: 'Collections',
                url: '/collections.html',
                item_type: 'category',
                parent_id: 0,
                level: 0,
                category_id: '7',
                position: 4
            }));
            var collectionsId = items[3].item_id;

            // 5. Training
            items.push(createItem({
                title: 'Training',
                url: '/training.html',
                item_type: 'category',
                parent_id: 0,
                level: 0,
                category_id: '9',
                position: 5
            }));
            var trainingId = items[4].item_id;

            // 6. Sale
            items.push(createItem({
                title: 'Sale',
                url: '/sale.html',
                item_type: 'category',
                parent_id: 0,
                level: 0,
                category_id: '8',
                position: 6,
                badge: 'sale',
                badge_text: 'SALE',
                text_color: '#dc2626',
                font_weight: '700'
            }));

            // ── LEVEL 1 — Women children ──

            // Women > Tops
            items.push(createItem({
                title: 'Tops',
                url: '/women/tops-women.html',
                item_type: 'category',
                parent_id: womenId,
                level: 1,
                category_id: '21',
                position: 1
            }));
            var womenTopsId = items[items.length - 1].item_id;

            // Women > Bottoms
            items.push(createItem({
                title: 'Bottoms',
                url: '/women/bottoms-women.html',
                item_type: 'category',
                parent_id: womenId,
                level: 1,
                category_id: '22',
                position: 2
            }));
            var womenBottomsId = items[items.length - 1].item_id;

            // ── LEVEL 2 — Women > Tops children ──

            items.push(createItem({
                title: 'Jackets',
                url: '/women/tops-women/jackets-women.html',
                item_type: 'category',
                parent_id: womenTopsId,
                level: 2,
                category_id: '23',
                position: 1,
                badge: 'new',
                badge_text: 'NEW'
            }));

            items.push(createItem({
                title: 'Hoodies & Sweatshirts',
                url: '/women/tops-women/hoodies-and-sweatshirts-women.html',
                item_type: 'category',
                parent_id: womenTopsId,
                level: 2,
                category_id: '24',
                position: 2
            }));

            items.push(createItem({
                title: 'Tees',
                url: '/women/tops-women/tees-women.html',
                item_type: 'category',
                parent_id: womenTopsId,
                level: 2,
                category_id: '25',
                position: 3
            }));

            items.push(createItem({
                title: 'Bras & Tanks',
                url: '/women/tops-women/tanks-women.html',
                item_type: 'category',
                parent_id: womenTopsId,
                level: 2,
                category_id: '26',
                position: 4,
                cms_block: 'megamenu-women-banner'
            }));

            // ── LEVEL 2 — Women > Bottoms children ──

            items.push(createItem({
                title: 'Pants',
                url: '/women/bottoms-women/pants-women.html',
                item_type: 'category',
                parent_id: womenBottomsId,
                level: 2,
                category_id: '27',
                position: 1
            }));

            items.push(createItem({
                title: 'Shorts',
                url: '/women/bottoms-women/shorts-women.html',
                item_type: 'category',
                parent_id: womenBottomsId,
                level: 2,
                category_id: '28',
                position: 2
            }));

            // ── LEVEL 1 — Men children ──

            // Men > Tops
            items.push(createItem({
                title: 'Tops',
                url: '/men/tops-men.html',
                item_type: 'category',
                parent_id: menId,
                level: 1,
                category_id: '12',
                position: 1
            }));
            var menTopsId = items[items.length - 1].item_id;

            // Men > Bottoms
            items.push(createItem({
                title: 'Bottoms',
                url: '/men/bottoms-men.html',
                item_type: 'category',
                parent_id: menId,
                level: 1,
                category_id: '13',
                position: 2
            }));
            var menBottomsId = items[items.length - 1].item_id;

            // ── LEVEL 2 — Men > Tops children ──

            items.push(createItem({
                title: 'Jackets',
                url: '/men/tops-men/jackets-men.html',
                item_type: 'category',
                parent_id: menTopsId,
                level: 2,
                category_id: '14',
                position: 1
            }));

            items.push(createItem({
                title: 'Hoodies & Sweatshirts',
                url: '/men/tops-men/hoodies-and-sweatshirts-men.html',
                item_type: 'category',
                parent_id: menTopsId,
                level: 2,
                category_id: '15',
                position: 2
            }));

            items.push(createItem({
                title: 'Tees',
                url: '/men/tops-men/tees-men.html',
                item_type: 'category',
                parent_id: menTopsId,
                level: 2,
                category_id: '16',
                position: 3
            }));

            items.push(createItem({
                title: 'Tanks',
                url: '/men/tops-men/tanks-men.html',
                item_type: 'category',
                parent_id: menTopsId,
                level: 2,
                category_id: '17',
                position: 4
            }));

            // ── LEVEL 2 — Men > Bottoms children ──

            items.push(createItem({
                title: 'Pants',
                url: '/men/bottoms-men/pants-men.html',
                item_type: 'category',
                parent_id: menBottomsId,
                level: 2,
                category_id: '18',
                position: 1
            }));

            items.push(createItem({
                title: 'Shorts',
                url: '/men/bottoms-men/shorts-men.html',
                item_type: 'category',
                parent_id: menBottomsId,
                level: 2,
                category_id: '19',
                position: 2
            }));

            // ── LEVEL 1 — Gear children ──

            items.push(createItem({
                title: 'Bags',
                url: '/gear/bags.html',
                item_type: 'category',
                parent_id: gearId,
                level: 1,
                category_id: '4',
                position: 1
            }));

            items.push(createItem({
                title: 'Fitness Equipment',
                url: '/gear/fitness-equipment.html',
                item_type: 'category',
                parent_id: gearId,
                level: 1,
                category_id: '5',
                position: 2
            }));

            items.push(createItem({
                title: 'Watches',
                url: '/gear/watches.html',
                item_type: 'category',
                parent_id: gearId,
                level: 1,
                category_id: '6',
                position: 3
            }));

            // ── LEVEL 1 — Collections children ──

            items.push(createItem({
                title: 'Erin Recommends',
                url: '/collections/erin-recommends.html',
                item_type: 'category',
                parent_id: collectionsId,
                level: 1,
                category_id: '34',
                position: 1
            }));

            items.push(createItem({
                title: 'Performance Fabrics',
                url: '/collections/performance-fabrics.html',
                item_type: 'category',
                parent_id: collectionsId,
                level: 1,
                category_id: '35',
                position: 2
            }));

            items.push(createItem({
                title: 'Eco Friendly',
                url: '/collections/eco-friendly.html',
                item_type: 'category',
                parent_id: collectionsId,
                level: 1,
                category_id: '36',
                position: 3
            }));

            // ── LEVEL 1 — Training children ──

            items.push(createItem({
                title: 'Video Download',
                url: '/training/training-video.html',
                item_type: 'category',
                parent_id: trainingId,
                level: 1,
                category_id: '10',
                position: 1
            }));

            return items;
        },

        /**
         * Check if item has children
         */
        hasChildren: function (itemId) {
            var children = this.getChildItems(itemId);
            var hasKids = Array.isArray(children) && children.length > 0;
            // Removed excessive console logging
            return hasKids;
        },

        /**
         * Toggle children visibility
         */
        toggleChildren: function (itemId) {
            if (!this.expandedItems) {
                this.expandedItems = {};
            }

            // Toggle the state
            var currentState = this.expandedItems[itemId];
            this.expandedItems[itemId] = !currentState;

            // Force UI update by mutating the observable
            this.items.valueHasMutated();

            // Also trigger a direct render update
            var self = this;
            setTimeout(function() {
                self.items.valueHasMutated();
            }, 10);
        },

        /**
         * Toggle expand/collapse (alias for toggleChildren)
         */
        toggleExpand: function (itemId) {
            this.toggleChildren(itemId);
        },

        /**
         * Check if item is expanded
         */
        isExpanded: function (itemId) {
            // Access items() to create Knockout dependency
            // This ensures the binding re-evaluates when items.valueHasMutated() is called
            this.items();

            if (!this.expandedItems) {
                this.expandedItems = {};
            }
            // By default, all items are collapsed (false)
            // Return true if explicitly set to true
            return this.expandedItems[itemId] === true;
        },

        /**
         * Expand all items
         */
        expandAll: function () {
            var self = this;
            this.expandedItems = {};
            this.items().forEach(function (item) {
                self.expandedItems[item.item_id] = true;
            });
            this.items.valueHasMutated();
        },

        /**
         * Collapse all items
         */
        collapseAll: function () {
            var self = this;
            this.expandedItems = {};
            this.items().forEach(function (item) {
                self.expandedItems[item.item_id] = false;
            });
            this.items.valueHasMutated();
        },

        /**
         * Initialize jQuery UI Sortable for drag and drop with multi-level support
         */
        initializeSortable: function () {
            var self = this;

            try {
                // Destroy existing sortable instances
                $('.menu-items-list').each(function() {
                    var $el = $(this);
                    if ($el.data('ui-sortable')) {
                        try {
                            $el.sortable('destroy');
                        } catch (e) {}
                    }
                });

                // Wait for DOM to be ready
                setTimeout(function() {
                    // Find all sortable list containers that aren't already initialized
                    var $containers = $('.menu-items-list').not(function() {
                        return $(this).data('ui-sortable');
                    });

                    if ($containers.length === 0) {
                        return;
                    }

                    // Initialize jQuery UI Sortable on all list containers
                    $containers.sortable({
                        handle: '.drag-handle',
                        connectWith: '.menu-items-list',
                        placeholder: 'ui-sortable-placeholder',
                        forcePlaceholderSize: true,
                        tolerance: 'pointer',
                        cursor: 'move',
                        opacity: 0.7,
                        delay: 50,
                        distance: 3,
                        items: '> .menu-item',
                        axis: false,
                        dropOnEmpty: true,

                        start: function(event, ui) {
                            // Store original parent for potential cancel
                            ui.item.data('original-parent-list', ui.item.parent());

                            var itemId = ui.item.attr('data-item-id');

                            // Get all children recursively and store them
                            var $children = ui.item.find('.menu-item');
                            ui.item.data('all-children', $children);

                            // Add dragging class - this will hide children via CSS
                            ui.item.addClass('is-dragging');
                            $('body').addClass('sortable-active');
                        },

                        stop: function(event, ui) {
                            // Remove dragging class - children will be shown again via CSS
                            ui.item.removeClass('is-dragging');
                            $('body').removeClass('sortable-active');

                            // Clean up stored data
                            ui.item.removeData('original-parent-list');
                            ui.item.removeData('all-children');
                        },

                        update: function(event, ui) {
                            // Only process if this is the receiving container
                            if (this === ui.item.parent()[0]) {
                                // Save state for undo
                                self.saveStateForUndo();

                                // jQuery UI has moved the DOM - read the new positions immediately
                                // before destroying anything
                                var processedIds = [];
                                var position = 0;
                                var updatedItems = [];

                                // Process items in new DOM order
                                function processItem($li, parentId, level) {
                                    var itemId = $li.attr('data-item-id');
                                    if (!itemId || processedIds.indexOf(itemId) !== -1) {
                                        return;
                                    }

                                    var item = self.findItemById(itemId);
                                    if (item) {
                                        item.parent_id = parentId || 0;
                                        item.level = level;
                                        item.position = position++;
                                        processedIds.push(itemId);
                                        updatedItems.push(item);

                                        // Process children
                                        $li.find('> .menu-children > .menu-item').each(function() {
                                            processItem($(this), itemId, level + 1);
                                        });
                                    }
                                }

                                // Read all items in new DOM order
                                $('.menu-level-0 > .menu-item').each(function() {
                                    processItem($(this), 0, 0);
                                });

                                // Now destroy sortables
                                $('.menu-items-list').each(function() {
                                    if ($(this).data('ui-sortable')) {
                                        try {
                                            $(this).sortable('destroy');
                                        } catch (e) {}
                                    }
                                });

                                // Update the model by clearing and rebuilding - this forces clean re-render
                                setTimeout(function() {
                                    // Clear then rebuild to avoid duplication
                                    self.items([]);

                                    setTimeout(function() {
                                        self.items(updatedItems);
                                        self.saveItems();

                                        // Reinitialize sortable after Knockout re-renders
                                        setTimeout(function() {
                                            self.initializeSortable();
                                        }, 100);
                                    }, 10);
                                }, 20);
                            }
                        }
                    });

                }, 100);

            } catch (e) {}
        },

        /**
         * Refresh or reinitialize sortable
         */
        refreshSortable: function () {
            var self = this;

            // Clear sortable-initialized flags to force re-initialization
            $('.menu-items-list, .menu-items-children').removeData('sortable-initialized');

            // Reinitialize
            this.initializeSortable();

            // Force Knockout to re-evaluate bindings after sortable refresh
            setTimeout(function() {
                self.items.valueHasMutated();
            }, 150);
        },

        /**
         * Update item positions after drag and drop
         */
        updateItemPositions: function () {
            var self = this;
            var processedIds = [];
            var position = 0;

            // Helper function to recursively process items in DOM order
            function processItem($li, parentId, level) {
                var itemId = $li.attr('data-item-id');
                if (!itemId || processedIds.indexOf(itemId) !== -1) {
                    return;
                }

                var item = self.findItemById(itemId);
                if (item) {
                    // Update item properties based on new position
                    item.parent_id = parentId || 0;
                    item.level = level;
                    item.position = position++;
                    processedIds.push(itemId);

                    // Process children in the nested list
                    $li.find('> .menu-children > .menu-item').each(function() {
                        processItem($(this), itemId, level + 1);
                    });
                }
            }

            // Process all root level items (direct children of .menu-level-0)
            $('.menu-level-0 > .menu-item').each(function() {
                processItem($(this), 0, 0);
            });

            // Reorder items array to match processed order
            var orderedItems = [];
            processedIds.forEach(function(id) {
                var item = self.findItemById(id);
                if (item) {
                    orderedItems.push(item);
                }
            });

            // Update observable array
            this.items(orderedItems);

            // Force UI update to ensure toggle buttons visibility is recalculated
            this.items.valueHasMutated();

            this.saveItems();
        },

        /**
         * Escape HTML
         */
        escapeHtml: function (text) {
            if (!text) return '';
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, function (m) { return map[m]; });
        },

        /**
         * Initialize keyboard shortcuts
         */
        initializeKeyboardShortcuts: function() {
            var self = this;

            $(document).on('keydown.menuBuilder', function(e) {
                // Only process shortcuts if not in an input field
                if ($(e.target).is('input, textarea, select')) {
                    return;
                }

                // Cmd+Z / Ctrl+Z - Undo
                if ((e.metaKey || e.ctrlKey) && e.key === 'z' && !e.shiftKey) {
                    e.preventDefault();
                    self.undo();
                    return false;
                }

                // Cmd+Shift+Z / Ctrl+Shift+Z or Cmd+Y / Ctrl+Y - Redo
                if (((e.metaKey || e.ctrlKey) && e.key === 'z' && e.shiftKey) ||
                    ((e.metaKey || e.ctrlKey) && e.key === 'y')) {
                    e.preventDefault();
                    self.redo();
                    return false;
                }

                // Cmd+E / Ctrl+E - Expand All
                if ((e.metaKey || e.ctrlKey) && e.key === 'e' && !e.shiftKey) {
                    e.preventDefault();
                    self.expandAll();
                    return false;
                }

                // Cmd+Shift+E / Ctrl+Shift+E - Collapse All
                if ((e.metaKey || e.ctrlKey) && e.key === 'e' && e.shiftKey) {
                    e.preventDefault();
                    self.collapseAll();
                    return false;
                }
            });
        },

        /**
         * Save state for undo
         */
        saveStateForUndo: function() {
            var currentState = JSON.stringify(this.items());
            this.undoStack.push(currentState);

            // Limit undo stack to 20 items
            if (this.undoStack.length > 20) {
                this.undoStack.shift();
            }

            // Clear redo stack when new action is performed
            this.redoStack = [];
        },

        /**
         * Undo last action
         */
        undo: function() {
            if (this.undoStack.length === 0) {
                return;
            }

            // Save current state to redo stack
            var currentState = JSON.stringify(this.items());
            this.redoStack.push(currentState);

            // Restore previous state
            var previousState = this.undoStack.pop();
            this.items(JSON.parse(previousState));
            this.saveItems();

            // Refresh sortable
            var self = this;
            setTimeout(function() {
                self.refreshSortable();
            }, 300);
        },

        /**
         * Redo last undone action
         */
        redo: function() {
            if (this.redoStack.length === 0) {
                return;
            }

            // Save current state to undo stack
            var currentState = JSON.stringify(this.items());
            this.undoStack.push(currentState);

            // Restore next state
            var nextState = this.redoStack.pop();
            this.items(JSON.parse(nextState));
            this.saveItems();

            // Refresh sortable
            var self = this;
            setTimeout(function() {
                self.refreshSortable();
            }, 300);
        },

        /**
         * Toggle item active status quickly
         */
        toggleItemActive: function(itemId) {
            var allItems = this.items();
            var newItems = allItems.map(function(item) {
                if (item.item_id == itemId) {
                    // Create a new object with toggled is_active
                    return $.extend({}, item, {
                        is_active: item.is_active ? 0 : 1
                    });
                }
                return item;
            });

            // Update the observable array with the new items
            this.items(newItems);

            // Save to backend
            this.saveItems();
        },

        /**
         * Duplicate item with all its children
         */
        duplicateItem: function(itemId) {
            var item = this.findItemById(itemId);
            if (!item) return;

            var self = this;
            var currentItems = this.items();
            var itemIndex = -1;

            // Find the original item's index
            for (var i = 0; i < currentItems.length; i++) {
                if (currentItems[i].item_id === itemId) {
                    itemIndex = i;
                    break;
                }
            }

            if (itemIndex === -1) return;

            // Map to store old ID -> new ID mappings for children
            var idMap = {};

            // Recursive function to duplicate an item and all its children
            function duplicateItemRecursive(sourceItem, newParentId) {
                // Create duplicate of the item
                var duplicatedItem = $.extend(true, {}, sourceItem);
                var newId = 'dup_' + self.nextItemId++;

                // Store the ID mapping
                idMap[sourceItem.item_id] = newId;

                // Update the duplicated item
                duplicatedItem.item_id = newId;
                duplicatedItem.parent_id = newParentId;

                // Add (Copy) to title only for the root duplicated item
                if (sourceItem.item_id === itemId) {
                    duplicatedItem.title = sourceItem.title + ' (Copy)';
                }

                return duplicatedItem;
            }

            // Get all items to duplicate (original + all descendants)
            var itemsToDuplicate = [];

            function collectItemAndChildren(parentItemId) {
                var parentItem = self.findItemById(parentItemId);
                if (!parentItem) return;

                itemsToDuplicate.push(parentItem);

                // Get children
                var children = self.getChildItems(parentItemId);
                children.forEach(function(child) {
                    collectItemAndChildren(child.item_id);
                });
            }

            collectItemAndChildren(itemId);

            // Duplicate all items
            var newItems = [];
            itemsToDuplicate.forEach(function(sourceItem) {
                var newParentId = sourceItem.parent_id;

                // If parent was also duplicated, use the new parent ID
                if (idMap[sourceItem.parent_id]) {
                    newParentId = idMap[sourceItem.parent_id];
                }

                var duplicated = duplicateItemRecursive(sourceItem, newParentId);
                newItems.push(duplicated);
            });

            // Insert all duplicated items right after the original item
            var updatedItems = currentItems.slice();
            Array.prototype.splice.apply(updatedItems, [itemIndex + 1, 0].concat(newItems));

            // Update the observable
            this.items(updatedItems);
            this.saveItems();

            // Refresh sortable and UI
            setTimeout(function() {
                self.items.valueHasMutated();
                self.refreshSortable();
            }, 300);
        }
    });
});
