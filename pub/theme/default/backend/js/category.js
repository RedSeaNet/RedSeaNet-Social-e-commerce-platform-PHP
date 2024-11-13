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
                ajax = $.post(GLOBAL.BASE_URL + GLOBAL.ADMIN_PATH + '/catalog_category/order/', $('.grid .table input').serialize(), function () {
                    $(window).off('beforeupload.redseanet.ajax');
                    ajax = null;
                });
            }
        });
        $('.edit form').on('submit', function () {
            if ($(this).valid()) {
                $('#tab-product.tab-pane .filters input').prop('disabled', true);
            }
        });
        $('#tab-product.tab-pane').on('click', 'tbody [type=checkbox]', function () {
            $(this).parents('tr').first().find('[name^=order]').prop('disabled', !$(this).prop('checked'));
        }).on('click', '.filters a.btn', function () {
            $('#tab-product.tab-pane').load($('#tab-product.tab-pane').data('load'));
            return false;
        }).on('click', '.filters .btn[formaction]', function () {
            $.ajax($(this).attr('formaction') + '&' + $('#tab-product.tab-pane .filters [name]').serialize(), {
                type: 'post',
                data: $('tbody [name^=order],tfoot [name]', '#tab-product.tab-pane').serialize(),
                success: function (xhr) {
                    $('#tab-product.tab-pane .grid').html($(xhr).html());
                }
            });
            return false;
        }).on('click', '.pager a', function () {
            $.ajax($(this).attr('href'), {
                type: 'post',
                data: $('tbody [name^=order],tfoot [name]', '#tab-product.tab-pane').serialize(),
                success: function (xhr) {
                    $('#tab-product.tab-pane .grid').html($(xhr).html());
                }
            });
            return false;
        }).on('click', '[type=checkbox]', function () {
            var flag = this.checked;
            var parent = $(this).parents('.table').last();
            if ($(this).is('.selectall')) {
                $(parent).find('[type=checkbox]').not(this).each(function () {
                    this.checked = flag;
                });
            } else if (flag && !$(parent).find('[type=checkbox]').not('.selectall,:checked').length) {
                $(parent).find('.selectall').each(function () {
                    this.checked = flag;
                });
            } else if (!flag && $(parent).find('[type=checkbox]').not('.selectall,:checked').length) {
                $(parent).find('.selectall').each(function () {
                    this.checked = flag;
                });
            }
        });
    });
}));