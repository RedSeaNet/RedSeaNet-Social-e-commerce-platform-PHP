(function (factory) {
    if (typeof define === "function" && define.amd) {
        define(["jquery"], factory);
    } else if (typeof module === "object" && module.exports) {
        module.exports = factory(require("jquery"));
    } else {
        factory(jQuery);
    }
}(function ($) {
    $(function () {
        "use strict";
        $('body').on('click', 'img[data-zoombox],img.zoombox', function () {
            var oimg = document.createElement('img');
            $(oimg).attr({'src': $(this).is('[data-zoombox]') ? $(this).data('zoombox') : $(this).attr('src'), 'class': 'loading'}).on('load', function () {
                var p = $(this).parents('.loading');
                if (this.naturalWidth) {
                    $(p).width(this.naturalWidth + 20).height(this.naturalHeight + 20);
                }
                $(p).removeClass('loading');
            });
            var m = $('<div class="modal fade modal-zoombox" tabindex="-1"><div class="modal-dialog loading"><div class="modal-content"><span class="fa fa-spinner fa-spin"></span></div></div></div>');
            $('.modal-content', m).prepend(oimg);
            $('body>.modal-zoombox').remove();
            $('body').append(m);
            $(m).modal('show');
        });
    });
}));
