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
        $('.condition').on('click', '.add', function () {
            $(this).parent('li').before(function () {
                return $($(this).parent('ul').data('tmpl')).html();
            });
        }).on('change', 'select:not([name])', function () {
            var selected = $(this).find(':selected');
            var value = $('#tmpl-condition-value-' + $(this).val());
            var html = '';
            if ($(selected).is('[data-type]')) {
                html = $('#tmpl-condition-' + $(selected).data('type')).html().replace('{$identifier}', $(this).val())
                        .replace('{$identifier_text}', $(selected).text());
                if (value.length) {
                    html = html.replace('{$value}', $(value).html());
                }
            } else if (value.length) {
                html = $(value).html();
            }
            $(this).after(html).remove();
        }).on('click', '.delete', function () {
            $(this).parents('li').first().remove();
        });
        $('.handler').on('click', '.add', function () {
            $(this).parent('li').before(function () {
                return $($(this).parent('ul').data('tmpl')).html();
            });
        }).on('change', 'select:not([name])', function () {
            var selected = $(this).find(':selected');
            var value = $('#tmpl-handler-value-' + $(this).val());
            var html = '';
            if ($(selected).is('[data-type]')) {
                html = $('#tmpl-handler-' + $(selected).data('type')).html().replace('{$identifier}', $(this).val())
                        .replace('{$identifier_text}', $(selected).text());
                if (value.length) {
                    html = html.replace('{$value}', $(value).html());
                }
            } else if (value.length) {
                html = $(value).html();
            }
            $(this).after(html).remove();
        }).on('click', '.delete', function () {
            $(this).parents('li').first().remove();
        });
        $('.edit form').on('submit', function () {
            if ($(this).valid()) {
                $('.tree .children select:not([name])').parent('li').remove();
                $('.tree .children').each(function () {
                    if (!$(this).children('li:not(.last)').length) {
                        $(this).parent('li').remove();
                    }
                });
                $('.tree').each(function () {
                    var c = 1;
                    $(this).find('li').each(function () {
                        $(this).children('.title').find('[name]').each(function () {
                            $(this).attr('name', $(this).attr('name').replace('[]', '[' + c + ']'));
                        });
                        c++;
                    });
                    $(this).find('[name*="[pid]"]').each(function () {
                        $(this).val($(this).parents('.children').first().siblings('.title').find('[name]').first().attr('name').replace(/.+\[(\d+)\]$/, '$1'));
                    });
                });
            }
        });
        var addCoupon = function (code) {
            $('.coupon .target').prepend($('#tmpl-coupon').html().replace('{$code}', code ? code : ''));
        };
        $('.coupon .btn-add').click(function () {
            addCoupon();
        });
        $('.coupon .btn-generate').click(function () {
            var c = parseInt(prompt($(this).attr('title'), 5));
            if (c > 0) {
                var current = $('.coupon .target .code').serialize();
                $.post(GLOBAL.BASE_URL + GLOBAL.ADMIN_PATH + '/promotion/generateCoupon/', current + '&count=' + c, function (response) {
                    for (var code in response) {
                        addCoupon(response[code]);
                    }
                });
            }
        });
        $('.coupon .target').on('click', '.delete:not([data-method])', function () {
            $(this).parents('tr').first().remove();
        });
        var generateTree = function (pid, tree) {
            var o = this;
            $(tree[pid]).each(function () {
                $(o).children('.last').before(function () {
                    return $($(this).parent('ul').data('tmpl')).html();
                });
                var oli = $(o).find('select:not([name])').parent('li');
                $(o).find('select:not([name])').val(this.identifier).trigger('change');
                $(oli).find('[name*="[value]"]').val(this.value);
                $(oli).find('[name*="[operator]"]').val(this.operator);
                if (typeof tree[this.id] !== 'undefined') {
                    generateTree.call($(oli).find('.children'), this.id, tree);
                }
            });
        };
        $('.tree[data-json]').each(function () {
            var json = $(this).data('json');
            if (json && typeof json['0'] !== 'undefined') {
                generateTree.call($(this).find('.children'), json['0'][0].id, json);
            }
            $(this).removeAttr('data-json');
        });
    });
}));

