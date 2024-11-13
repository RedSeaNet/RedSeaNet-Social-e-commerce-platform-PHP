(function (factory) {
    if (typeof define === "function" && define.amd) {
        define(["jquery", "jquery.ui.sortable"], factory);
    } else if (typeof module === "object" && module.exports) {
        module.exports = factory(require(["jquery", "jquery.ui.sortable"]));
    } else {
        factory(jQuery);
    }
}(function ($) {
    "use strict";
    $(function () {
        var ajax = null;
        $('.grid .table .sortable').sortable({
            items: 'li',
            connectWith: '.sortable',
            placeholder: 'placeholder',
            revert: true,
            start: function () {
                $('.grid .table .sortable').each(function () {
                    if (!$(this).children().length) {
                        $(this).addClass('moving');
                    }
                });
            },
            stop: function () {
                $('.grid .table .sortable.moving').removeClass('moving');
            },
            update: function (e, ui) {
                $(ui.item).removeAttr('style');
                var id = $(ui.item).parents('[data-id]').first().data('id');
                $(ui.item).children('input[name^=order]').each(function () {
                    this.value = id;
                });
                $(window).on('beforeupload.redseanet.ajax', function () {
                    confirm();
                });
                if (ajax) {
                    ajax.readyState < 4 ? ajax = null : ajax.abort();
                }
                ajax = $.post(GLOBAL.BASE_URL + GLOBAL.ADMIN_PATH + '/forum_category/order/', $('.grid .table input').serialize(), function () {
                    $(window).off('beforeupload.redseanet.ajax');
                    ajax = null;
                });
            }
        });
    });
}));