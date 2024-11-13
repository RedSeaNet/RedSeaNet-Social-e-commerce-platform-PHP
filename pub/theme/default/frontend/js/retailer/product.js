(function (factory) {
    if (typeof define === "function" && define.amd) {
        define(["jquery", "tabs"], factory);
    } else if (typeof module === "object" && module.exports) {
        module.exports = factory(require(["jquery", "tabs"]));
    } else {
        factory(jQuery);
    }
}(function ($) {
    $(function () {
        "use strict";
        $('.product-edit .btn-next').click(function () {
            var n = $('.product-edit .nav-tabs .active').next().not('.view-more');
            if (n.length) {
                $(n).children('[data-toggle]').tab('show');
            } else {
                $(this).addClass('disabled');
            }
        });
        $('.product-edit .nav-tabs [data-toggle]').on('show.bs.tab', function () {
            var t = $(this).parent().next().not('.view-more');
            if (t.length && $(t).is(':visible')) {
                $('.product-edit .btn-next').removeClass('disabled');
            } else {
                $('.product-edit .btn-next').addClass('disabled');
            }
        });
        if (!$('.product-edit [name=id]').val()) {
            $('.product-edit .nav-tabs .view-more .btn').show().on('click', function () {
                $(this).hide();
                $('.product-edit .nav-tabs').addClass('show-all');
                $('.product-edit .btn-next').removeClass('disabled');
            });
        } else {
            $('.product-edit .nav-tabs').addClass('show-all');
        }
        var ajax = null;
        $('.grid').on('click', '.filters [formaction],.sort-by a,.pager a', function () {
            var p = $(this).parents('.grid');
            if (ajax) {
                ajax.readyState < 4 ? ajax = null : ajax.abort();
            }
            var u = $(p).find('.filters [formaction]').attr('formaction');
            var m = $(p).find('.filters [name]').serialize() + '&id=' + $('[type=hidden][name=id]').val();
            var s = '&' + ($(this).is('.sort-by a') ? $(this).attr('href').match(/(?:a|de)sc=[^\&]+/) : ($(p).find('.sort-by .asc,.sort-by .desc').length ? $(p).find('.sort-by .asc,.sort-by .desc').attr('href').match(/(?:a|de)sc=[^\&]+/) : ''));
            var e = '&page=' + ($(this).is('.pager a') ? $(this).parents('[data-page]').data('page') : ($(p).find('.pager .current').length ? $(p).find('.pager .current').parents('[data-page]').data('page') : 1));
            ajax = $.post(u + '?' + m + s + e, $('tbody [name],tfoot [name]', p).serialize(), function (response) {
                var fg = document.createDocumentFragment();
                $(fg).html(response);
                $(p).html($(fg).find('.grid').html());
            });
            return false;
        }).on('click', '.filters [href].btn', function () {
            var p = $(this).parents('.grid');
            if (ajax) {
                ajax.readyState < 4 ? ajax = null : ajax.abort();
            }
            ajax = $.post($(this).attr('href') + '&id=' + $('[type=hidden][name=id]').val(), $('tbody [name],tfoot [name]', p).serialize(), function (response) {
                var fg = document.createDocumentFragment();
                $(fg).html(response);
                $(p).html($(fg).find('.grid').html());
            });
            return false;
        }).on('click', '[type=checkbox]', function () {
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
        $('#custom-options').on('click', '.add-option', function () {
            var id = -1000;
            $(this).prevAll('.option').each(function () {
                if ($(this).data('id') > id) {
                    id = $(this).data('id');
                }
            });
            $(this).before($('#custom-options #tmpl-option').html().replace(/\{\$id\}/g, id + 1));
            $(this).prev('.option').find('.sortable').sortable({
                item: 'tr'
            });
            $('input[type=hidden][name=options][value=null]').remove();
        }).on('click', '.delete-option', function () {
            if ($(this).parents('.option').first().siblings('.option').length === 0) {
                $('table.option').first().before('<input type="hidden" name="options" value="null" />');
            }
            $(this).parents('.option').remove();
        }).on('click', '.add-row', function () {
            $(this).parents('.table').first().find('tbody')
                    .append($('#custom-options #tmpl-option-value').html().replace(/\{\$id\}/g, $(this).data('id')).replace(/\{\$label\}/g, '').replace(/\{\$sku\}/g, '').replace(/\{\$eavattributeoptionid\}/g, ''));
        }).on('click', '.delete-row', function () {
            $(this).parents('tr').first().remove();
        }).on('change', 'select[name^="options[input]"]', function () {
            var p = $(this).parents('tr');
            let optionList = {};
            if ($(this).find("option:selected").attr('data-option-list')) {
                optionList = JSON.parse($(this).find("option:selected").attr('data-option-list'));
            }
            if ($.inArray($(this).val(), ['select', 'radio', 'checkbox', 'multiselect']) === -1) {
                $(p).siblings('.value').hide();
                $(p).siblings('.optionvaluelist').hide();
                $(p).siblings('.non-value').show();
                if ($(p).find("select[data-type=required]").val() == 1) {
                    $(p).siblings('.value').find("input[data-type=sku]").addClass('required');
                    $(p).siblings('.non-value').find("input[data-type=sku]").removeClass('required').removeClass('invaild');
                } else {
                    $(p).siblings('.value').find("input[data-type=sku]").removeClass('required').removeClass('invaild');
                    $(p).siblings('.non-value').find("input[data-type=sku]").addClass('required');
                }
            } else {
                $(p).siblings('.value').show();
                let optionListHtml = '';
                if (optionList) {
                    for (let key in optionList) {
                        if (optionList.hasOwnProperty(key)) {
                            optionListHtml += '<label for="optionvaluelist' + key + '"><input data-option-code="' + $(this).find("option:selected").attr('data-code') + '" class="optionvaluelist" name="options[optionvaluelist][]" data-id="' + key + '" id="optionvaluelist' + key + '" type="checkbox" value="' + optionList[key].label + '" data-label="' + optionList[key].label + '" data-code="' + optionList[key].code + '" />&nbsp;' + optionList[key].label + '&nbsp;&nbsp;</label>'
                        }
                    }
                }
                $(p).siblings('.optionvaluelist').find(' .content').html(optionListHtml);
                $(p).siblings('.optionvaluelist').show();
                $(p).find("input.optionssku").val($(this).find("option:selected").attr('data-code'));
                $(p).siblings('.non-value').hide();
                if ($(p).find("select[data-type=required]").val() == 1) {
                    $(p).siblings('.value').find("input[data-type=sku]").addClass('required');
                    $(p).siblings('.non-value').find("input[data-type=sku]").removeClass('required').removeClass('invaild');
                } else {
                    $(p).siblings('.value').find("input[data-type=sku]").removeClass('required').removeClass('invaild');
                    $(p).siblings('.non-value').find("input[data-type=sku]").addClass('required');
                }
                if ($(this).find("option:selected").attr('data-label')) {
                    $(p).find('.optionslabel').val($(this).find("option:selected").attr('data-label'));
                }
                if ($(this).find("option:selected").attr('data-id')) {
                    $(p).find('.eavattributeid').val($(this).find("option:selected").attr('data-id'));
                }
            }
        }).on('change', 'select[name^="options[is_required]"]', function () {
            var p = $(this).parents('tr');
            if ($.inArray($(this).val(), ['select', 'radio', 'checkbox', 'multiselect']) === -1) {
                if ($(p).find("select[data-type=required]").val() == 1) {
                    $(p).siblings('.value').find("input[data-type=sku]").addClass('required');
                    $(p).siblings('.non-value').find("input[data-type=sku]").removeClass('required').removeClass('invaild');
                } else {
                    $(p).siblings('.value').find("input[data-type=sku]").removeClass('required').removeClass('invaild');
                    $(p).siblings('.non-value').find("input[data-type=sku]").addClass('required');
                }
            } else {
                if ($(p).find("select[data-type=required]").val() == 1) {
                    $(p).siblings('.value').find("input[data-type=sku]").addClass('required');
                    $(p).siblings('.non-value').find("input[data-type=sku]").removeClass('required').removeClass('invaild');
                } else {
                    $(p).siblings('.value').find("input[data-type=sku]").removeClass('required').removeClass('invaild');
                    $(p).siblings('.non-value').find("input[data-type=sku]").addClass('required');
                }
            }
        }).on('click', 'input.optionvaluelist', function () {
            var p = $(this).parents('tr');
            if ($(this).is(":checked")) {
                if (!($(p).siblings('.value').find('input.optionsvaluesku[value=' + $(this).data('code') + ']') && $(p).siblings('.value').find('input.optionsvaluesku[value=' + $(this).data('code') + ']').length > 0)) {
                    let _tmpValueHtml = $('#custom-options #tmpl-option-value').html().replace(/\{\$id\}/g, $(p).siblings('.value').find(".add-row").data('id')).replace(/\{\$label\}/g, $(this).data('label')).replace(/\{\$sku\}/g, ($(this).data('code') ? $(this).data('code') : $(this).data('label'))).replace(/\{\$eavattributeoptionid\}/g, $(this).data('id'));
                    $(p).siblings('.value').find('tbody').append(_tmpValueHtml);
                }
            } else {

            }
        });
        $('.sortable').sortable({
            item: 'tr'
        });
        $('[href="#tab-inventory"]').on('show.bs.tab', function () {
            var result = [];
            var pushStack = function (n, l, t) {
                if ($.inArray(n, ['select', 'radio', 'checkbox', 'multiselect']) === -1) {
                    var sku = $(this).find('[name^="options[sku]"]').val();
                    var title = $(this).find('[name^="options[label]"]').val();
                    if (sku) {
                        if (l) {
                            for (var i = 0; i < l; i++) {
                                t.push({sku: result[i].sku + '-' + sku, title: result[i].title + '-' + title});
                            }
                        } else {
                            t.push({sku: $('#sku').val() + '-' + sku, title: title});
                        }
                    }
                } else {
                    $(this).find('.value tr').each(function () {
                        var sku = $(this).find('[name$="[sku][]"]').val();
                        var title = $(this).find('[name$="[label][]"]').val();
                        if (sku) {
                            if (l) {
                                for (var i = 0; i < l; i++) {
                                    t.push({sku: result[i].sku + '-' + sku, title: result[i].title + '-' + title});
                                }
                            } else {
                                t.push({sku: $('#sku').val() + '-' + sku, title: title});
                            }
                        }
                    });
                }
                result = t;
            };
            var hasRequired = false;
            $('#custom-options .option').each(function () {
                if (parseInt($(this).find('[name^="options[is_required]"]').val())) {
                    var input = $(this).find('[name^="options[input]"]').val();
                    var l = result.length;
                    hasRequired = true;
                    pushStack.call(this, input, l, []);
                }
            });
            $('#custom-options .option').each(function () {
                if (!parseInt($(this).find('[name^="options[is_required]"]').val())) {
                    var input = $(this).find('[name^="options[input]"]').val();
                    var l = result.length;
                    pushStack.call(this, input, l, result);
                }
            });
            if (result.length) {
                if (!hasRequired) {
                    result.unshift({sku: $('#sku').val(), title: ''});
                }
                $('#tab-inventory .branch').each(function () {
                    var fg = document.createDocumentFragment();
                    var tmpl = $(this).next('.tmpl-inventory-branch');
                    var inventory = $(tmpl).data('inventory');
                    $(result).each(function () {
                        $(fg).append($(tmpl).html().replace(/\{\$title\}/g, this.title)
                                .replace(/\{\$sku\}/g, this.sku)
                                .replace(/\{\$qty\}/g, inventory[this.sku] && inventory[this.sku].qty ? inventory[this.sku].qty : 0)
                                .replace(/\{\$barcode\}/g, inventory[this.sku] && inventory[this.sku].barcode ? inventory[this.sku].barcode : ''));
                    });
                    $(this).show().find('tbody').html(fg);
                });
            }
        });
        $('[href="#tab-images"]').on('show.bs.tab', function () {
            let optionsvalueskuList = $('#custom-options').find("input.optionsvaluesku");
            let imagesGroupString = '';
            if (optionsvalueskuList.length > 0) {
                for (let l = 0; l < optionsvalueskuList.length; l++) {
                    if (optionsvalueskuList[l] && $(optionsvalueskuList[l]).val() != '') {
                        imagesGroupString = imagesGroupString + '<option val="' + $(optionsvalueskuList[l]).val() + '">' + $(optionsvalueskuList[l]).val() + '</option>'
                    }
                }
            }
            $('div#tab-images').find("select.imagesgroup").find("option:gt(0)").remove();
            $('div#tab-images').find("select.imagesgroup").append(imagesGroupString);
            $('div#tab-images').find("select.imagesgroup").each(function () {
                if ($(this).data('default')) {
                    $(this).val($(this).data('default'));
                }
            });
        });
        $('div#tab-images select.imagesgroup').on('change', function () {
            $(this).data('default', $(this).val());
        });
        $('.widget-upload').on('resource.selected', '.btn[data-toggle=modal]', function () {
            $(this).parents('.inline-box').find('[type=radio]').val($(this).siblings('input').val());
        });
        $('.edit form').on('submit', function () {
            if ($(this).valid()) {
                $(this).find('.grid .filters input,.grid .filters select,.grid .pager input').each(function () {
                    this.disabled = true;
                });
            }
        });
        $('.table.additional').on('click', '.add', function () {
            $('.table.additional .target').append($('#tmpl-additional').html());
        }).on('click', '.delete', function () {
            $(this).parents('tr').first().remove();
        });
        $('#tab-category .dropdown-toggle').on('click', function () {
            $(this).toggleClass('active');
            $(this).siblings('ul').slideToggle();
        });
        var checkTree = function () {
            var p = $(this).parent();
            if (this.checked) {
                var o = $(p).parent().siblings('[type=checkbox]');
                $(o).prop('checked', true);
                if (!$(p).parent().is('.category')) {
                    checkTree.call(o[0]);
                }
            } else {
                var f = true;
                $(p).siblings().each(function () {
                    if ($(this).children('[type=checkbox]').prop('checked')) {
                        f = false;
                        return false;
                    }
                });
                if (f) {
                    var o = $(p).parent().siblings('[type=checkbox]').first();
                    $(o).prop('checked', false);
                    if (!$(p).parent().is('.category')) {
                        checkTree.call(o[0]);
                    }
                }
            }
        };
        $('#tab-category .category [type=checkbox]').on('click', function () {
            checkTree.call(this);
        });
        var recalcGroupPrice = function () {
            var v = {};
            $('.table.group-price tbody tr').each(function () {
                if ($(this).find('.price').val() !== '') {
                    v[$(this).find('.group').val()] = $(this).find('.price').val();
                }
            });
            $('.table.group-price~input[name]').val(JSON.stringify(v));
        };
        $('.table.group-price').on('change', 'select,input', recalcGroupPrice)
                .on('click', '.add', function () {
                    $(this).parentsUntil('table').last().siblings('tbody').append($(this).parentsUntil('table').last().parent().next('template').html());
                }).on('click', '.delete', function () {
            $(this).parentsUntil('tbody').last().remove();
            recalcGroupPrice();
        });
        var recalcTierPrice = function () {
            var v = {};
            $('.table.tier-price tbody tr').each(function () {
                if ($(this).find('.qty').val() !== '' && $(this).find('.price').val() !== '') {
                    if (typeof v[$(this).find('.group').val()] === 'undefined') {
                        v[$(this).find('.group').val()] = {}
                    }
                    v[$(this).find('.group').val()][$(this).find('.qty').val()] = $(this).find('.price').val();
                }
            });
            $('.table.tier-price~input[name]').val(JSON.stringify(v));
        };
        $('.table.tier-price').on('change', 'select,input', recalcTierPrice)
                .on('click', '.add', function () {
                    $(this).parentsUntil('table').last().siblings('tbody').append($(this).parentsUntil('table').last().parent().next('template').html());
                }).on('click', '.delete', function () {
            $(this).parentsUntil('tbody').last().remove();
            recalcTierPrice();
        });
        var recalcBulkPrice = function () {
            var v = {};
            $('.table.bulk-price tbody tr').each(function () {
                if ($(this).find('.price').val() !== '') {
                    v[$(this).find('.qty').val()] = $(this).find('.price').val();
                }
            });
            $('.table.bulk-price~input[name]').val(JSON.stringify(v));
        };
        $('.table.bulk-price').on('change', 'select,input', recalcBulkPrice)
                .on('click', '.add', function () {
                    $(this).parentsUntil('table').last().siblings('tbody').append($(this).parentsUntil('table').last().parent().next('template').html());
                }).on('click', '.delete', function () {
            $(this).parentsUntil('tbody').last().remove();
            recalcBulkPrice();
        });
        $('.inventorybarcode').on('click', function () {
            if ($(this).data('warehouse') != '') {
                $(".barcode[data-warehouse=" + $(this).data('warehouse') + "]").val($("#inventorybarcode" + $(this).data('warehouse')).val());
            }
        });
        $('.inventoryqty').on('click', function () {
            if ($(this).data('warehouse') != '') {
                $(".qty[data-warehouse=" + $(this).data('warehouse') + "]").val($("#inventoryqty" + $(this).data('warehouse')).val());
            }
        });

    });
}));
