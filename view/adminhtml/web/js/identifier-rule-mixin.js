define([
    'mage/translate'
], function ($t) {
    'use strict';

    return function (validator) {
        validator.addRule(
            'validate-menu-identifier',
            function (value) {
                return value === '' || value === null || value === undefined || /^[a-z0-9_]+$/.test(value);
            },
            $t('Use lowercase letters, numbers and underscores only.')
        );

        return validator;
    };
});
