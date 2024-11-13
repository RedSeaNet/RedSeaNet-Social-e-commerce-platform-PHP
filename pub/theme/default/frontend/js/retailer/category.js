(function (factory) {
    if (typeof define === "function" && define.amd) {
        define(["jquery", "jquery.ui.selectable"], factory);
    } else if (typeof module === "object" && module.exports) {
        module.exports = factory(require(["jquery", "jquery.ui.selectable"]));
    } else {
        factory(jQuery);
    }
}(function ($) {
    $(function () {
        "use strict";
        var ajax = null;
        $('.retailer-category .categories-list,.retailer-category .categories-list ul').sortable({
            handle: ".move",
            placeholder: "ui-state-highlight",
            update: function (e, ui) {
                $(window).on('beforeupload.redseanet.ajax', function () {
                    confirm();
                });
                if (ajax) {
                    ajax.readyState < 4 ? ajax = null : ajax.abort();
                }
                ajax = $.post(GLOBAL.BASE_URL + 'retailer/category/move/', $(ui.item).parent('ul').find('[name^=order]').serialize(), function () {
                    $(window).off('beforeupload.redseanet.ajax');
                    ajax = null;
                });
            }
        }).disableSelection();
        $('.retailer-category .categories-list>li>.category-name').click(function () {
            $(this).toggleClass('active');
            $(this).siblings('.children').slideToggle();
        });
    });
    $(".selectall").click(function () {
        if (this.checked) {
            $("input[name='category_store']").prop('checked', true)
        } else {
            $("input[name='category_store']").prop('checked', false)
        }
    });
}));
