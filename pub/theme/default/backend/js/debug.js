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
        $('#debug-toolbar .nav').on('click', '.active a', function () {
            $(this).parent().removeClass('active');
            $($(this).attr('href')).removeClass('active in');
            return false;
        });
        if (typeof $.fn.tab === 'undefined') {
            $('#debug-toolbar .nav').on('click', 'li:not(.active) a', function () {
                $(this).parent().addClass('active');
                $(this).parent().siblings('.active').removeClass('active');
                $($(this).attr('href')).addClass('active in');
                $($(this).attr('href')).siblings('.active').removeClass('active in');
                return false;
            });
        }
        $('#debug-toolbar .tab-content').on('click.redseanet.ajax', 'a:not(.loaded)', function () {
            var o = this;
            var p = $(this).parents('tr').first();
            $(this).parents('.tab-content').first().scrollTop(p[0].offsetTop);
            var params = $(this).data('params');
            $.ajax({
                url: $(this).attr('href'),
                type: params ? 'post' : 'get',
                data: params ? params : '',
                success: function (xhr) {
                    $(o).addClass('loaded');
                    if (typeof xhr === 'object') {
                        var html = '<div class="table-responsive"><table class="table"><tbody>';
                        var thead = '<thead><tr>';
                        var flag = true;
                        for (var i in xhr) {
                            html += '<tr>';
                            for (var j in xhr[i]) {
                                if (flag) {
                                    thead += '<th>' + j + '</th>';
                                }
                                html += '<td>' + xhr[i][j] + '</td>';
                            }
                            flag = false;
                            html += '</tr>'
                        }
                        $(p).after('<tr><td colspan="' + $(p).children().length + '">' + html + '</tbody>' + thead + '</tr></thead></table></div>' + '</td></tr>');
                    } else {
                        $(p).after('<tr><td colspan="' + $(p).children().length + '">' + xhr + '</td></tr>');
                    }
                }
            });
            return false;
        }).on('click', 'a.loaded', function () {
            $(this).parents('tr').first().next('tr').toggle();
            return false;
        }).on('click', '[data-url]', function () {
            $.get($(this).data('url')).then(function () {
                location.reload();
            });
        });
        $('#debug-toolbar .toggle-button').click(function () {
            $('#debug-toolbar').toggleClass('collapsed');
            if (window.sessionStorage) {
                sessionStorage['debug-toolbar'] = $('#debug-toolbar').is('.collapsed') ? 1 : 0;
            }
        });
        if (sessionStorage['debug-toolbar'] == 1) {
            $('#debug-toolbar').css('transition', 'none').addClass('collapsed');
            window.setTimeout(function () {
                $('#debug-toolbar').removeAttr('style');
            }, 300);
        }
    });
}));
