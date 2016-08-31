/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Initialization of the selectTree js component used e.g. for category tree rendering
 */
define(['jquery', 'TYPO3/CMS/Backend/FormEngine/Element/SelectTree'], function ($, SelectTree) {
    'use strict';
    $(document).ready(function () {
        $('.typo3-tceforms-tree .treeRecord').each(function (i, element) {

            /**
             * Hidden input field storing selected elements.
             * Tree is initialized based on values stored in it's data attributes
             *
             * @type {*|jQuery|HTMLElement}
             */
            var treeInput = $(element);
            var dataParams = {
                table: treeInput.data('table'),
                field: treeInput.data('field'),
                uid: treeInput.data('uid'),
                flex_form_field_name: treeInput.data('flex-form-field-name'),
                command: treeInput.data('command')
            };
            var $wrapper = treeInput.parent().siblings('.svg-tree-wrapper');
            var dataUrl = TYPO3.settings.ajaxUrls['record_tree_data'] + '&' + TYPO3.jQuery.param(dataParams);
            var tree = new SelectTree();
            tree.initialize($wrapper, {
                'dataUrl': dataUrl,
                'showIcons': true,
                'showCheckboxes': true,
                'readOnlyMode': treeInput.data('read-only'),
                'input': treeInput,
                'exclusiveNodesIdentifiers': treeInput.data('tree-exclusive-keys'),
                'validation': treeInput.data('formengine-validation-rules')[0],
                'expandUpToLevel': treeInput.data('tree-expand-up-to-level')
            });
            tree.dispatch.on('nodeSelectedAfter.requestUpdate', window[$wrapper.attr('id')]);

            if (treeInput.data('tree-show-toolbar')) {
                require(['TYPO3/CMS/Backend/FormEngine/Element/TreeToolbar'], function (TreeToolbar) {
                    var selectTreeToolbar = new TreeToolbar();
                    selectTreeToolbar.initialize($wrapper);
                });
            }
        });
    });
});