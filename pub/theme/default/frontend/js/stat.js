(function (factory) {
    if (typeof define === "function" && define.amd) {
        define(["jquery"], factory);
    } else if (typeof module === "object" && module.exports) {
        module.exports = factory(require(["jquery"]));
    } else {
        factory(jQuery);
    }
}(function ($) {
    $(function () {
        $('.panel-stat [data-url]').each(function () {
            var o = this;
            $.get($(o).data('url'),function (xhr) {
                var r = typeof xhr === 'string' ? eval('(' + xhr + ')') : xhr;
                for (var i in r) {
                    $(o).find('.data.' + i).text(r[i]);
                }
                $('.data.time').text(r.time);
            });
        });
    });
}));