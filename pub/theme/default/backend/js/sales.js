(function (factory) {
    if (typeof define === "function" && define.amd) {
        define(["jquery"], factory);
    } else if (typeof module === "object" && module.exports) {
        module.exports = factory(require(["jquery", "jquery.ui.sortable"]));
    } else {
        factory(jQuery);
    }
}(function ($) {
    "use strict";
    $(function () {
        $('.form-sales').submit(function () {
            var q = 0;
            $('[name^=qty]').each(function () {
                q += $(this).val();
            });
            if (q == 0) {
                addMessages([{message: 'Please select 1 product at least.', level: 'danger'}]);
                return false;
            }
        });
        $('[name^=qty]').change(function () {
            var p = $(this).parent().siblings('.price');
            $(this).parent().siblings('.total').text(formatPrice($(p).data('price') * $(this).val()));
            $('.totals .subtotal').trigger('collate');
            $('.totals .total').trigger('collate');
        });
        $('.totals [type=checkbox]').click(function () {
            $('.totals .total').trigger('collate');
        });
        $('.totals .subtotal').on('collate', function () {
            var t = 0;
            $('[name^=qty]').each(function () {
                t += $(this).val() * $(this).parent().siblings('.price').data('price');
            });
            $(this).text(formatPrice(t));
        });
        $('.totals .total').on('collate', function () {
            var t = 0;
            $('[name^=qty]').each(function () {
                t += $(this).val() * $(this).parent().siblings('.price').data('price');
            });
            $('.totals [type=checkbox]:checked').each(function () {
                t += parseFloat($(this).siblings('.price').data('price'));
            });
            $(this).text(formatPrice(t));
        });
        $('.carrier #carrier-code').change(function () {
            $('.carrier #carrier').val($(this).val() ? $(this).find(':selected').attr('title') : '');
        });
    });
}));

