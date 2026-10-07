define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return function () {
        $.validator.addMethod(
            'validate-json',
            function (value) {
                if ($.trim(value) === '') {
                    return true;
                }
                try {
                    JSON.parse(value);
                    return true;
                } catch (e) {
                    return false;
                }
            },
            $t('Please enter valid JSON data.')
        );
    };
});
