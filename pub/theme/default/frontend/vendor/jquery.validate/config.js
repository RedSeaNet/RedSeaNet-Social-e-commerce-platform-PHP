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
        if (lang) {
            var url = GLOBAL.PUB_URL + ($('body').is('.admin') ? 'backend' : 'frontend');
            if (typeof module === "object" && module.exports) {
                var i18n = function (src) {
                    require('./localization/' + src);
                };
            } else {
                var i18n = function (src) {
                    var os = document.createElement('script');
                    os.async = 'async';
                    os.src = url + '/vendor/jquery.validate/localization/' + src + '.js';
                    document.head.appendChild(os);
                };
            }
            if (/^(?:bn\-BD|es\-AR|es\-PE|hy\-AM|pt\-BR|pt\-BT|sr\-lat|zh\-TW)$/.test(lang)) {
                i18n('messages_' + lang);
            } else if (/^(?:ar|bg|ca|cs|da|de|el|es|et|eu|fa|fi|fr|ge|gl|he|hr|hu|id|is|it|ja|ka|kk|ko|lt|lv|mk|my|nl|no|pl|ro|ru|si|sk|sl|sr|sv|th|th|tr|uk|vi|zh)/.test(lang)) {
                i18n('messages_' + lang.replace(/^([a-z]{2})\-.+$/, '$1'));
            }
        }
        $.validator.addMethod("minAge18", function (value, element) {
            var today = new Date();
            var birthDate = new Date(value);
            var age = today.getFullYear() - birthDate.getFullYear();
            if (age > 18 + 1) {
                return true;
            }
            var m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            return age >= 18;
        }, "Confirm that you are at least 18 years old");
        $.extend($.validator.defaults, {
            errorClass: 'invalid',
            ignore: '[type=hidden]',
            errorPlacement: function (error, element) {
                if ($(element).parents('.tab-pane').length) {
                    $(element).parents('.tab-pane').each(function () {
                        var t = $('.nav-tabs>li>[data-bs-toggle=tab][href="#' + $(this).attr('id') + '"]').parent('li');
                        if ($(this).find('input.invalid,select.invalid,textarea.invalid').length) {
                            $(t).addClass('error');
                        } else {
                            $(t).removeClass('error');
                        }
                    });
                    $('.nav-tabs>li.error>[data-bs-toggle=tab]').first().tab('show');
                }
                if ($(error).html() && $(element).data("vaild-alert")) {
                    alert($(error).html());
                }
                error.insertAfter(element);
            },
            success: function (error, element) {
                if ($(element).parents('.tab-pane').length) {
                    $(element).parents('.tab-pane').each(function () {
                        var t = $('.nav-tabs>li>[data-bs-toggle=tab][href="#' + $(this).attr('id') + '"]').parent('li');
                        if ($(this).find('input.invalid,select.invalid,textarea.invalid').length) {
                            $(t).addClass('error');
                        } else {
                            $(t).removeClass('error');
                        }
                    });
                }
                $(error).remove();
            }
        });
        $(document.body).on('submit.redseanet', 'form', function () {
            return $(this).valid();
        });
    });
}));
