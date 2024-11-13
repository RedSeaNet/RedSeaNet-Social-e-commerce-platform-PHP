(function (factory) {
    if (typeof define === "function" && define.amd) {
        define(["jquery", "jquery.validate"], factory);
    } else if (typeof module === "object" && module.exports) {
        module.exports = factory(require(["jquery", "jquery.validate"]));
    } else {
        factory(jQuery);
    }
}(function ($) {
    $(function () {
        "use strict";
        var lang = $('html').attr('lang');
        if (!lang) {
            lang = en;
        }
        let selects = $("select.select2Adapter");
        if (selects.length > 0) {
            for (let s = 0; s < selects.length; s++) {
                if ($(selects[s]).data('ajax--url') && $(selects[s]).data('ajax--url') != '') {
                    $(selects[s]).select2({
                        ajax: {
                            method: 'post',
                            type: 'post',
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return {
                                    q: params.term,
                                    page: params.page
                                };
                            },
                            processResults: function (data, params) {
                                params.page = params.page || 1;
                                return {
                                    results: data.results,
                                    pagination: {
                                        more: (params.page * 20) < data.total_count
                                    }
                                };
                            },
                            cache: false
                        },
                        placeholder: $(selects[s]).data('placeholder') ? $(selects[s]).data('placeholder') : 'Please choose',
                        language: lang,
                        minimumInputLength: 2,
                        multiple: true,
                        allowClear: true,
                        maximumSelectionLength: 15,
                        debug: true
                    });
                } else {
                    $(selects[s]).select2({
                        placeholder: $(selects[s]).data('placeholder') ? $(selects[s]).data('placeholder') : 'Please choose',
                        language: lang,
                        minimumInputLength: 2,
                        multiple: true,
                        allowClear: true,
                        maximumSelectionLength: 15,
                        debug: true
                    });
                }
            }
        }


    });
}));
