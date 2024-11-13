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
        $('#modal-account form').on('afterajax.redseanet', function (e, json) {
            if (json.data) {
                var ol = $('[for="account_id-' + json.data.id + '"]');
                var detail = typeof json.data.detail === 'string' ? JSON.parse(json.data.detail) : json.data.detail;
                $('.type', ol).text(json.data.type_name);
                $('.id', ol).text(detail.id);
                $('.name', ol).text(detail.name);
                var info = json.data;
                delete info.detail;
                for (var i in detail) {
                    info['detail[' + i + ']'] = detail[i];
                }
                $(ol).siblings('[data-info]').attr('data-info', JSON.stringify(info));
            }
            $('#modal-account').modal('hide');
        });
    });
}));
