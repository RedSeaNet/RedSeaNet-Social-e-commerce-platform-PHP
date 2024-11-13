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
        window.formatPrice = function (price) {
            return GLOBAL.FORMAT.replace(/\%(?:\d\$)?(?:\.\d+)?[fd]/, parseFloat(price).toFixed(GLOBAL.FORMAT.indexOf('.') === -1 ? 0 : GLOBAL.FORMAT.replace(/^.+\.(\d+)[fd]$/, '$1')))
        };
        $('#nav-toggle').click(function () {
            $('.nav-container .open').removeClass('open');
            if ($(this).is('.active')) {
                $(this).removeClass('active');
                $('.nav-container').removeClass('active');
                $('.nav-container .dropdown-toggle').attr('data-toggle', 'dropdown');
                var flag = 0;
            } else {
                $(this).addClass('active');
                $('.nav-container').addClass('active');
                $('.nav-container .dropdown-toggle').removeAttr('data-toggle');
                var flag = 1;
            }
            if (localStorage) {
                localStorage.admin_nav = flag;
            }
            if ($('#canvas').length) {
                setTimeout(function () {
                    $('#canvas').highcharts().reflow();
                }, 600);
            }
        });
        if (localStorage) {
            if (localStorage.admin_nav == 1) {
                $('.nav-container,.main-container').addClass('no-transition');
                $('#nav-toggle').addClass('active');
                $('.nav-container').addClass('active');
                $('.nav-container .dropdown-toggle').removeAttr('data-toggle');
                if ($('#canvas').length) {
                    $('#canvas').highcharts().reflow();
                }
                if (localStorage.admin_nav_open) {
                    $('.nav-container>.nav>.dropdown').eq(localStorage.admin_nav_open).addClass('open');
                }
                setTimeout(function () {
                    $('.nav-container,.main-container').removeClass('no-transition');
                }, 600);
            }
            if (!$('.nav-container .active').length) {
                $('[href="' + localStorage.lastActiveNav + '"]').addClass('active');
            } else {
                localStorage.lastActiveNav = $('.nav-container .active').attr('href');
            }
        }
        $('.nav-container').on('click', '.dropdown-toggle:not([data-toggle=dropdown])', function () {
            var parent = $(this).parent('.dropdown');
            $(parent).siblings('.open').removeClass('open');
            $(parent).toggleClass('open');
            if (localStorage) {
                localStorage.admin_nav_open = $(parent).prevAll('.dropdown').length;
            }
        });
        $('img.captcha').on('click reload', function () {
            $(this).attr('src', $(this).attr('src').replace(/\?.+$/, '') + '?' + (new Date().getTime()));
        });
        window.addMessages = function (messages) {
            var html = '';
            for (var i in messages) {
                html += '<div class="alert alert-' + messages[i].level + '">' + messages[i].message + '</div>';
            }
            $('.header .top-menu .messages .message-box').append(html);
            $('.header .top-menu .messages .badge').text($('.message-box>.alert').length);
            $('.header .top-menu .messages').addClass('has-message');
        };
        var responseHandler = function (json) {
            var o = this;
            if (typeof json === 'string') {
                json = eval('(' + json + ')');
            }
            if (json.cookie && $.cookie) {
                $.cookie(json.cookie.key, json.cookie.value, json.cookie);
            }
            if (json.redirect) {
                location.href = json.redirect;
            } else if (json.reload) {
                location.reload();
            } else if (json.message.length) {
                addMessages(json.message);
            }
            if (json.removeLine) {
                if (typeof json.removeLine == 'object') {
                    var t = $(o).parents('table,ul,ol,dl').last();
                    $(json.removeLine).each(function () {
                        if ($(t).find('[data-id=' + this + ']').next().hasClass('child')) {
                            $(t).find('[data-id=' + this + ']').next().remove();
                        }
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
            $(o).trigger('afterajax.redseanet', json);
        };
        $(document.body).on('click.redseanet.ajax', 'a[data-method]', function () {
            var o = this;
            if ($(o).data('method') !== 'delete' || confirm(translate($(o).is('[data-serialize]') ? 'Are you sure to delete these records?' : 'Are you sure to delete this record?'))) {
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
                        responseHandler.call(o, xhr.responseText ? xhr.responseText : xhr);
                    }
                });
            }
            return false;
        }).on('submit.redseanet.ajax', 'form[data-ajax]', function () {
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
                    responseHandler.call(o, xhr.responseText ? xhr.responseText : xhr);
                    if ($(o).parents('.modal').length) {
                        $(o).parents('.modal').modal('hide');
                    }
                }
            });
            return false;
        });
        $('#modal-send-email').on({
            'show.bs.modal': function (e) {
                $(this).find('#sendmail-template_id').val($(e.relatedTarget).data('id'));
            }
        });
        $('#modal-edit-address form').on('afterajax.redseanet', function (e, json) {
            console.log(json);
            if (json.error == 0) {
                var target = $('#address-book [data-id=' + json.data.id + ']');
                if (target.length) {
                    $('#address-book [data-id=' + json.data.id + '] .content').html(json.address);
                    $('#address-book [data-id=' + json.data.id + '] [data-info]').attr('data-info', JSON.stringify(json.data));
                } else if ($('#address-book .address-book').length) {
                    $('#address-book').append(
                            function () {
                                var set = $('#address-book .address-book .buttons-set').first().clone(false);
                                $(set).find('[data-params]').attr('data-params', function () {
                                    return $(this).data('params').replace(/id=[^\&]+/, 'id=' + json.data.id);
                                });
                                $(set).find('[data-info]').attr('data-info', JSON.stringify(json.data));
                                var odiv = document.createElement('div');
                                $(odiv).attr({class: 'address-book', 'data-id': json.data.id})
                                        .html('<div class="content">' + json.address + '</div>')
                                        .prepend(set);
                                return odiv;
                            });
                } else {
                    location.reload();
                }
                if (json.data.is_default == 1) {
                    $('#address-book .active').removeClass('active');
                    $('#address-book [data-id=' + json.data.id + ']').addClass('active');
                }
            }
        });
        $('.modal').on({
            'show.bs.modal': function (e) {
                if ($(e.relatedTarget).is('[data-info]')) {
                    var info = $(e.relatedTarget).data('info');
                    if (typeof info === 'string') {
                        info = eval('(' + info + ')');
                    }
                    $(this).find('form').trigger('reset');
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
                                    $(this).val(info[i]).trigger('change.redseanet');
                                }
                            });
                        }
                    }
                }
            }
        });
        if ($('.message-box>.alert').length) {
            addMessages();
        }
        $('.scope').each(function () {
            var selected = $(this).find('.dropdown-menu>.selected');
            if (selected.length) {
                $(this).find('.dropdown-toggle>span').text($(selected).text());
            } else {
                $(this).find('.dropdown-toggle>span').text($(this).find('.dropdown-menu>:first-child').text());
                $('[type=hidden][name=scope]').val('m' + $(this).find('.dropdown-menu>:first-child').data('id'));
            }
        });
        $('a[href="' + location.href + '"]').addClass('active');
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
                toggle($(i).is('[type=radio]:not(:checked),[type=checkbox]:not(:checked)') ? null : $(i).val(), target[i]);
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
        $('.pager .btn').click(function () {
            location.href = $(this).data('url') + $(this).siblings('input').val();
        });
        $('[type=checkbox][name][value]').change(function () {
            var t = $('[type=checkbox][name="' + $(this).attr('name') + '"][value="' + $(this).attr('value') + '"]').not(this);
            var f = this.checked;
            if (t.length) {
                $(t).each(function () {
                    this.checked = f;
                });
            }
        });
        $('.edit form').on('change', '[name]', function () {
            var i = $('input[name="_changed_fields"]', this.form);
            if (!i.length) {
                i = document.createElement('input');
                i.type = 'hidden';
                i.name = '_changed_fields';
                i.value = ',';
                $(this.form).append(i);
            }
            var v = $(i).val();
            var n = $(this).attr('name').replace(/^([^\[]+)\[.+$/, '$1');
            if ($.inArray(n, v.split(',')) === -1) {
                v += ',' + n;
                $(i).val(v.replace(/^\,/, '').replace(/\,{2,}/, ','));
            }
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
        if ($('[data-toggle=datetimepicker]').length) {
            $.getScript(GLOBAL.PUB_URL + 'backend/js/datetimepicker/datetimepicker.js', function () {
                var l = $('html').attr('lang') || 'en';
                var param = {};
                var t = function () {
                    $('[data-toggle=datetimepicker]').each(function () {
                        param.startDate = null;
                        param.endDate = null;
                        param.format = 'yyyy-mm-dd hh:ii';
                        param.startView = 2;
                        if ($(this).is('[data-min-date]')) {
                            param.startDate = $(this).data('min-date');
                        }
                        if ($(this).is('[data-max-date]')) {
                            param.endDate = $(this).data('max-date');
                        }
                        $(this).attr({type: 'text', readonly: 'readonly'}).datetimepicker(param);
                        if ($(this).parent().is('.range') && $(this).nextAll('[data-toggle=datepicker],[data-toggle=datetimepicker]').length) {
                            $(this).on('changeDate', function (ev) {
                                $(this).nextAll('[data-toggle=datepicker],[data-toggle=datetimepicker]').datetimepicker('setStartDate', ev.date.getFullYear() + '-' + (ev.date.getMonth() + 1) + '-' + ev.date.getDate());
                            });
                        }
                    });
                };
                if (/(?:ar|az|bg|bn|ca|cs|da|de|ee|en|el|es|fi|fr|he|hr|hu|hy|id|is|it|ja|ka|ko|lt|lv|mx|nb|nl|no|pl|pt|ro|rs|ru|sk|sl|sv|sw|th|tr|ua|uk)/.test(l.substr(0, 2))) {
                    $.getScript(GLOBAL.PUB_URL + 'backend/js/datetimepicker/locales/datetimepicker.' + l.substr(0, 2) + '.js', function () {
                        param.language = l.substr(0, 2);
                        t();
                    });
                } else if (/(?:pt\-br|rs\-latin|zh\-cn|zh\-tw|zh\-hk)/.test(l.toLocaleLowerCase())) {
                    $.getScript(GLOBAL.PUB_URL + 'backend/js/datetimepicker/locales/datetimepicker.' + l.toLocaleLowerCase() + '.js', function () {
                        param.language = l;
                        t();
                    });
                }
            });
        }
    });
}));
