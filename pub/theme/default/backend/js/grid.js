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
        $.fn.grid = function () {
            var t = $(this).find('[data-id]');
            if ($(this).find('[type=checkbox].selectall').length) {
                $(this).on('click', '[type=checkbox]', function () {
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
            }
            $(this).find('tbody td').click(function () {
                if ($(this).find('a,button,input,select,textarea').length) {
                    return;
                } else if ($(this).siblings('.checkbox').length) {
                    $(this).siblings('.checkbox').children('[type=checkbox]').trigger('click');
                } else if ($(this).parent('tr').data('href')) {
                    location.href = $(this).parent('tr').data('href');
                }
            });
            if ($(t).find('.action').length) {
                $(t).on('contextmenu', function (e) {
                    var m = $('<menu class="context"></menu>');
                    $(this).children('.action').children('a').each(function () {
                        var o = this;
                        var oa = $('<a href="' + this.href + '">' + $(this).html() + '</a>');
                        if ($(this).is('[data-method]')) {
                            $(oa).one('click', function () {
                                $(o).trigger('click');
                                return false;
                            });
                        }
                        $(oa).find('.sr-only').removeClass('sr-only');
                        var oli = $('<li></li>');
                        oli.append(oa);
                        $(m).append(oli);
                    });
                    $(m).css({top: e.pageY - 4 + 'px', left: e.pageX - 4 + 'px'}).on('mouseleave', function () {
                        $(this).remove();
                    }).appendTo(document.body);
                    return false;
                });
            }

            $(this).find('input[type=text],textarea').on('blur', function (e) {
                if ($(this).data('old-value') != $(this).val()) {
                    var data = $(this).data('params');
                    data = data + '&value=' + $(this).val();
                    data = data + '&id=' + $(this).closest('tr').data('id');
                    var _this = $(this);
                    $.ajax($(this).data('href'), {
                        type: $(this).data('method'),
                        data: data,
                        success: function (xhr) {
                            if (xhr.message.length) {
                                addMessages(xhr.message);
                            }
                            _this.trigger('afterajax.redseanet', xhr);
                        }
                    });
                }
            }).on('focus', function (e) {
                $(this).data('old-value', $(this).val());
            });

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

        if ($('.grid table.table').length > 0) {
            $('.grid table.table').grid();
            $('.grid table.table').DataTable({
                searching: false,
                lengthChange: false,
                ordering: false,
                paging: false,
                info: false,
                responsive: true
            });
        }



    });
}));