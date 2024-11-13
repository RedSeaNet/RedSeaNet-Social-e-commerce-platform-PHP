(function (factory) {
    if (typeof define === "function" && define.amd) {
        define(["jquery", "modernizr"], factory);
    } else if (typeof module === "object" && module.exports) {
        module.exports = factory(require("jquery", "modernizr"));
    } else {
        factory(jQuery);
    }
}(function ($) {
    $(function () {
        "use strict";
        new WOW().init();
        $("body").animsition({
            inClass: 'fade-in',
            outClass: 'fade-out',
            inDuration: 1500,
            outDuration: 800,
            linkElement: '.animsition-link',
            loading: true,
            loadingParentElement: 'body', //animsition wrapper element
            loadingClass: 'animsition-loading',
            loadingInner: '', // e.g '<img src="loading.svg" />'
            timeout: false,
            timeoutCountdown: 5000,
            onLoadEvent: true,
            browser: ['animation-duration', '-webkit-animation-duration'],
            // "browser" option allows you to disable the "animsition" in case the css property in the array is not supported by your browser.
            // The default setting is to disable the "animsition" in a browser that does not support "animation-duration".
            overlay: false,
            overlayClass: 'animsition-overlay-slide',
            overlayParentElement: 'body',
            transition: function (url) {
                window.location.href = url;
            }
        });
        $('.pager .btn').click(function () {
            location.href = $(this).data('url') + ('?page=') + $(this).siblings('input').val();
        });
        window.addMessages = function (messages) {
            var html = '';
            for (var i in messages) {
                html += '<div class="alert alert-' + messages[i].level + '">' + messages[i].message + '</div>';
            }
            $('.message-box').append(html);
        };
        window.responseHandler = function (json) {
            var o = this;
            $(o).trigger('afterajax.redseanet', json);
            try {
                if (typeof json === 'string') {
                    json = eval('(' + json + ')');
                }
                if (json.cookie && $.cookie) {
                    $.cookie(json.cookie.key, json.cookie.value, json.cookie);
                }
                if (json.redirect) {
                    location.href = json.redirect;
                    return;
                } else if (json.reload) {
                    location.reload();
                    return;
                } else if (json.message.length) {
                    addMessages(json.message);
                }
                if (json.removeLine) {
                    if (typeof json.removeLine == 'object') {
                        var t = $(o).parents('table,ul,ol,dl,.table').first();
                        $(json.removeLine).each(function () {
                            $(t).find('[data-id=' + this + ']').remove();
                        });
                    } else {
                        if ($(o).is('menu a')) {
                            var t = $('.grid [href="' + $(o).attr('href') + '"][data-params="' + $(o).data('params') + '"]').parentsUntil('tbody,ul,ol,dl').last();
                        } else {
                            var t = $(o).parentsUntil('tbody,ul,ol,dl').last();
                        }
                        if ($(t).is('tr,li,dt,dd')) {
                            $(t).remove();
                        }
                    }
                }
            } catch (e) {

            }
        };
        window.formatPrice = function (price) {
            return GLOBAL.FORMAT.replace(/\%(?:\d\$)?(?:\.\d+)?[fd]/, parseFloat(price).toFixed(GLOBAL.FORMAT.indexOf('.') === -1 ? 0 : GLOBAL.FORMAT.replace(/^.+\.(\d+)[fd]$/, '$1')))
        };
        $(document.body).on('click.redseanet.ajax', 'a[data-method]', {}, function () {
            var o = this;
            if ($(o).data('method') !== 'delete' || confirm(translate($(o).data('confirm') ? $(o).data('confirm') : 'Are you sure to delete this record?'))) {
                if ($(o).is('[data-params]')) {
                    var data = $(o).data('params');
                } else if ($(o).is('[data-serialize]')) {
                    var data = $($(o).data('serialize')).find('input:not([type=radio]):not([type=checkbox]),[type=radio]:checked,[type=checkbox]:checked,select,textarea,button[name]').serialize();
                } else {
                    var data = '';
                }
                if (!GLOBAL.AJAX) {
                    GLOBAL.AJAX = {};
                } else if (GLOBAL.AJAX[$(o).attr('href')]) {
                    GLOBAL.AJAX[$(o).attr('href')].readyState < 4 ? GLOBAL.AJAX[$(o).attr('href')] = null : GLOBAL.AJAX[$(o).attr('href')].abort();
                }
                GLOBAL.AJAX[$(o).attr('href')] = $.ajax($(o).attr('href'), {
                    type: $(o).data('method'),
                    data: data,
                    success: function (xhr) {
                        GLOBAL.AJAX[$(o).attr('href')] = null;
                        responseHandler.call(o, xhr.responseText ? xhr.responseText : xhr);
                        if ($(o).is('[data-pjax]')) {
                            window.history.pushState({}, '', $(o).attr('href'));
                        }
                    }
                });
            }
            return false;
        }).on('submit.redseanet.ajax', 'form[data-ajax]', function () {
            if (!$(this).valid()) {
                return false;
            }
            var o = this;
            if (!GLOBAL.AJAX) {
                GLOBAL.AJAX = {};
            } else if (GLOBAL.AJAX[$(o).attr('action')]) {
                GLOBAL.AJAX[$(o).attr('action')].readyState < 4 ? GLOBAL.AJAX[$(o).attr('action')] = null : GLOBAL.AJAX[$(o).attr('action')].abort();
            }
            GLOBAL.AJAX[$(o).attr('action')] = $.ajax($(o).attr('action'), {
                type: $(o).attr('method'),
                data: $(this).serialize(),
                success: function (xhr) {
                    GLOBAL.AJAX[$(o).attr('action')] = null;
                    responseHandler.call(o, xhr.responseText ? xhr.responseText : xhr);
                    if ($(o).parents('.modal').length) {
                        $(o).parents('.modal').modal('hide');
                    }
                    if ($(o).is('[data-pjax],[data-ajax=pjax]')) {
                        window.history.pushState({}, '', $(o).attr('href'));
                    }
                }
            });
            return false;
        }).on('click reload', 'img.captcha', function () {
            if ($(this).is('[data-src]')) {
                $(this).attr('src', $(this).data('src') + '?' + (new Date().getTime())).removeAttr('data-src');
            } else {
                $(this).attr('src', $(this).attr('src').replace(/\?.+$/, '') + '?' + (new Date().getTime()));
            }
        });
        $('.modal').on({
            'show.bs.modal': function (e) {
                if ($(e.relatedTarget).is('[data-info]')) {
                    var info = $(e.relatedTarget).attr('data-info');
                    if (typeof info === 'string') {
                        info = JSON.parse(info);
                        if (typeof info === 'string') {
                            info = JSON.parse(info);
                        }
                    }
                    $(this).find('form').trigger('reset');
                    $(this).find('[type=hidden]:not([name=csrf])').val('');
                    for (var i in info) {
                        var t = $(this).find('[name="' + i + '"]');
                        if (t.length) {
                            $(t).each(function () {
                                if ($(this).is('[type=radio],[type=checkbox]')) {
                                    if ($(this).val() == info[i]) {
                                        this.checked = true;
                                    }
                                } else {
                                    if ($(this).is('select')) {
                                        $(this).attr('data-default-value', info[i]);
                                    }
                                    $(this).val(info[i]);
                                }
                            });
                        }
                    }
                    $('[name]', this).trigger('change');
                }
            }
        });
        $('.carousel .carousel-inner').on('touchstart', function (e) {
            GLOBAL.PAGEX = e.originalEvent.touches[0].pageX;
            GLOBAL.PAGEY = e.originalEvent.touches[0].pageY;
            if (GLOBAL.CAROUSELTIMEOUT) {
                window.clearTimeout(GLOBAL.CAROUSELTIMEOUT);
                GLOBAL.CAROUSELTIMEOUT = null;
            }
            $('.carousel .item').css('transition', 'none');
        }).on('touchmove', function (e) {
            var x = e.originalEvent.touches[0].pageX;
            var y = e.originalEvent.touches[0].pageY;
            if (Math.abs(y - GLOBAL.PAGEY) < 30) {
                var c = $(this).children('.item.active');
                if (x < GLOBAL.PAGEX) {
                    var t = $(c).is('.item:last-child') ? $(this).children('.item:first-child') : $(c).next('.item');
                    $(t).removeClass('prev').addClass('next');
                    $(c).css('left', x - GLOBAL.PAGEX);
                    $(t).css('left', x - GLOBAL.PAGEX);
                } else {
                    var t = $(c).is('.item:first-child') ? $(this).children('.item:last-child') : $(c).prev('.item');
                    $(t).removeClass('next').addClass('prev');
                    $(c).css('left', x - GLOBAL.PAGEX);
                    $(t).css('left', x - GLOBAL.PAGEX);
                }
            }
        }).on('touchend', function (e) {
            var t = $(this).children('.item.prev,.item.next');
            if (t.length) {
                var p = $(this).parent('.carousel');
                var c = $(this).children('.item.active');
                if (Math.abs($(c).css('left').replace('px', '')) > $(c).width() / 20) {
                    var w = $(c).width();
                    if ($(t[0]).is('.prev')) {
                        $(t).animate({left: w}, 300);
                        $(c).animate({left: w}, 300, function () {
                            $(t).removeClass('prev').removeClass('next').addClass('active').css('left', 0);
                            $(this).removeClass('active');
                        });
                    } else {
                        $(t).animate({left: -w}, 300);
                        $(c).animate({left: -w}, 300, function () {
                            $(t).removeClass('prev').removeClass('next').addClass('active').css('left', 0);
                            $(this).removeClass('active');
                        });
                    }
                } else {
                    $(t).animate({left: 0}, 300);
                    $(c).animate({left: 0}, 300, function () {
                        $(t).removeClass('prev').removeClass('next');
                    });
                }
                GLOBAL.CAROUSELTIMEOUT = window.setTimeout(function () {
                    $('.item', p).removeAttr('style');
                    $('.carousel-indicators [data-slide-to]', p).removeClass('active');
                    $('.carousel-indicators [data-slide-to=' + $('.item.active', p).prevAll('.item').length + ']', p).addClass('active');
                    $(p).carousel('cycle');
                }, 600);
            }
        });
        $('.qty .spin').click(function () {
            var t = $('#' + $(this).attr('for'));
            var v = parseFloat($(t).val());
            var s = parseFloat($(t).attr('step'));
            var min = parseFloat($(t).attr('min'));
            var max = parseFloat($(t).attr('max'));
            if ($(this).is('.minus') && (isNaN(min) || v > min)) {
                $(t).val(min ? Math.max(min, v - (s ? s : 1)) : v - (s ? s : 1));
                $(t).trigger('change.redseanet');
            } else if ($(this).is('.plus') && (isNaN(max) || v < max)) {
                $(t).val(max ? Math.min(max, v + (s ? s : 1)) : v + (s ? s : 1));
                $(t).trigger('change.redseanet');
            }
        });
        $('.filters .more a').click(function () {
            $(this).parents('div').toggleClass('all');
        });
        $('.avatar~[type=file]').change(function () {
            var oimg = $(this).siblings('.avatar').find('img');
            if (typeof FileReader !== 'undefined') {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $(oimg).attr('src', e.target.result);
                }
                reader.readAsDataURL(this.files[0]);
            } else {
                this.select();
                var src = document.selection.createRange().text;
                $(oimg).css('filter', 'progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src="' + src + '"')
            }
        });
        $('[type=file].preview').each(function () {
            $(this).change(function () {
                $(this).siblings('.preview').remove();
                if (typeof FileReader !== 'undefined') {
                    var oimg = document.createElement('img');
                    $(oimg).addClass('preview');
                    $(this).after(oimg);
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        oimg.src = e.target.result;
                    }
                    reader.readAsDataURL(this.files[0]);
                } else {
                    this.select();
                    var src = document.selection.createRange().text;
                    $(this).after('<div class="preview" style=\'filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src="' + src + '"\'></div>')
                }
            });
        });
        $('[data-base]').each(function () {
            var o = this;
            var p = $(o).parents('.input-box').first();
            $(p).hide();
            $(o).find('input,select,textarea,button').each(function () {
                this.disabled = true;
            });
            o.disabled = true;
            var base = $(o).data('base');
            try {
                var target = eval('(' + base + ')');
            } catch (e) {
                var target = base.indexOf(':') === -1 ? eval('({"' + base + '":"1"})') : eval('({' + base + '})');
            }
            var toggle = function (s, t) {
                if (typeof s !== 'object') {
                    s = [s];
                }
                if (typeof t !== 'object') {
                    t = [t];
                }
                var f = false;
                for (var i in s) {
                    if ($.inArray(s[i], t) !== -1) {
                        f = true;
                        break;
                    }
                }
                if (f) {
                    $(p).show();
                    o.disabled = false;
                    $(o).find('input,select,textarea,button').each(function () {
                        this.disabled = false;
                    });
                } else {
                    $(p).hide();
                    o.disabled = true;
                    $(o).find('input,select,textarea,button').each(function () {
                        this.disabled = true;
                    });
                }
            };
            for (var i in target) {
                toggle($(i).is('[type=radio],[type=checkbox]') ? $(i).filter(':checked').val() : $(i).val(), target[i]);
                if ($(i).is('[type=radio],[type=checkbox]')) {
                    $(i).click(function () {
                        toggle(this.checked ? this.value : null, target[i]);
                    });
                } else {
                    $(i).change(function () {
                        toggle($(this).val(), target[i]);
                    });
                }
            }
        });
        $('form[enctype="multipart/form-data"]').on('change', '.images [hidden][type=file][accept^=image]', function () {
            var odiv = $('<div class="preview"></div>');
            if (typeof FileReader !== 'undefined') {
                if (this.files[0].size > 2097152) {
                    alert(translate('Each image should not be over 2MB.'));
                } else {
                    var oimg = document.createElement('img');
                    $(odiv).append(oimg);
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        oimg.src = e.target.result;
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            } else {
                this.select();
                var src = document.selection.createRange().text;
                $(odiv).css('filter', 'progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src="' + src + '"');
            }
            $(this).before(odiv);
            if ($(this).siblings('[name="' + $(this).attr('name') + '"]').length < 4) {
                $(this).after('<input type="file" hidden="hidden" name="' + $(this).attr('name') + '" id="' + $(this).attr('id') + '" accept="' + $(this).attr('accept') + '" />').removeAttr('id');
            }
        }).on('click', '.images .preview', function () {
            var o = $(this).next('[type=file]');
            if ($(this).siblings('.preview').length) {
                if ($(o).is('[id]')) {
                    $(this).siblings('[type=file]').last().attr('id', $(o).attr('id'));
                }
                $(o).remove();
                $(this).remove();
            } else {
                $(o).val('');
                $(this).remove();
            }
        });
        if ($.fn.datepicker && $('[type=date]').length) {
            $('[type=date]').each(function () {
                var param = {dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true};
                if ($(this).parent().is('.range') && $(this).nextAll('[type=date]').length) {
                    param.onSelect = function () {
                        $(this).nextAll('.date').datepicker('option', 'minDate', $.datepicker.parseDate('yy-mm-dd', this.value));
                    };
                }
                if ($(this).is('[data-min-date]')) {
                    param.minDate = $(this).data('min-date');
                }
                if ($(this).is('[data-max-date]')) {
                    param.maxDate = $(this).data('max-date');
                }
                $(this).attr({type: 'text', readonly: 'readonly'}).datepicker(param);
            });
        }
        var tables = [];
        $('[type=checkbox].selectall').each(function () {
            var p = $(this).parents().parents('.table').last();
            if ($.inArray(p, tables) === -1) {
                $(p).on('click', '[type=checkbox]', function () {
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
                tables.push(p);
            }
        });
        $('body').on('focus', 'input,textarea', function () {
            $('footer .mobile-footer-bar').attr('hidden', 'hidden');
        }).on('blur', 'input,textarea', function () {
            $('footer .mobile-footer-bar').removeAttr('hidden');
        });
        if ($('[data-toggle=datepicker]').length) {
            var l = document.createElement('link');
            l.href = GLOBAL.PUB_URL + 'backend/css/jquery-ui.min.css';
            l.rel = 'stylesheet';
            $(l).appendTo('head');
            $.getScript(GLOBAL.PUB_URL + 'backend/js/jquery.ui/datepicker.js', function () {
                $('[data-toggle=datepicker]').each(function () {
                    var param = {dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true};
                    if ($(this).parent().is('.range') && $(this).nextAll('[data-toggle=datepicker]').length) {
                        param.onSelect = function () {
                            $(this).nextAll('.date').datepicker('option', 'minDate', $.datepicker.parseDate('yy-mm-dd', this.value));
                        };
                    }
                    if ($(this).is('[data-min-date]')) {
                        param.minDate = $(this).data('min-date');
                    }
                    if ($(this).is('[data-max-date]')) {
                        param.maxDate = $(this).data('max-date');
                    }
                    $(this).attr({type: 'text', readonly: 'readonly'}).datepicker(param);
                });
            });
        }
    });
    $(window).on("load", function () {
        if (!Modernizr.formattribute) {
            $('[form]').on('click', function () {
                $('#' + $(this).attr('form')).submit();
                return false;
            });
        }
        if (!Modernizr.inputformaction) {
            $('[formaction]').on('click', function () {
                var f = this.form || $('#' + $(this).attr('form'));
                $(f).attr('action', $(this).attr('formaction')).submit();
                return false;
            });
        }
    });

}));
