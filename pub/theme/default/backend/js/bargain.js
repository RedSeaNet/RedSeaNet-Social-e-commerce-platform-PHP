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

        var productload = function (urlStr = "") {
            var productlistdiv = $("div#bargainproductlist");
            if (productlistdiv.length > 0) {
                if (urlStr == "") {
                    urlStr = productlistdiv.data("url") + "?";
                    urlStr = urlStr + "id=" + ($("input#filtersproductid").val() ? $("input#filtersproductid").val() : '');
                    urlStr = urlStr + "&name=" + ($("input#filtersproductname").val() ? $("input#filtersproductname").val() : '');
                    urlStr = urlStr + "&sku=" + ($("input#filtersproductsku").val() ? $("input#filtersproductsku").val() : '');
                    urlStr = urlStr + "&limit=" + ($("input#filtersproductlimit").val() ? $("input#filtersproductlimit").val() : 20);
                }
                urlStr = encodeURI(urlStr);
                productlistdiv.load(urlStr, function () {
                    $("div#bargainproductlist .pagination .page-item a").on("click", function () {
                        productload($(this).attr("href"));
                        return false;
                    });
                });
            }
            return false;
        };
        $("a#filterssearchbutton").on("click", function () {
            productload();
        });
        $('div#productListModal').on('hide.bs.modal', function (e) {
            var choosenProductId = $('input[type=radio][name=productradio]:checked').val();
            if (choosenProductId != '') {
                //console.log($(this).data("url") + choosenProductId);
                $.get($(this).data("url") + choosenProductId, function (result) {
                    // console.log(result);
                    if (result.id && result.id != '') {
                        $("input[name=product_id]").val(result.id);

                        if (result.images && result.images.length > 0) {
                            $("div#widgetthumbnail .inline-box").find('input').each(function () {
                                if ($(this).is('[type=radio],[type=checkbox]')) {
                                    this.checked = true;
                                    $(this).val(result.images[0].id);
                                } else {
                                    $(this).val(result.images[0].id);
                                }
                            });
                            $("div#widgetthumbnail .inline-box").find('img').attr('src', result.images[0].src);

                            var l = $('div#widgetimage .inline-box');
                            var i = l.length;
                            var o = $(l).last();
                            l.remove();
                            var divarray = [];
                            for (let i = 0; i < result.images.length; i++) {
                                let odiv = $('<' + o[0].tagName + ' class="inline-box" data-index="' + i + '"></' + o[0].tagName + '>');
                                $(odiv).html($(o).html());
                                $(odiv).data("index", i);
                                $(odiv).find('input').each(function () {
                                    if ($(this).is('[type=radio],[type=checkbox]')) {
                                        this.checked = true;
                                        $(this).val(result.images[i].id);
                                    } else {
                                        $(this).val(result.images[i].id);
                                    }
                                });
                                $(odiv).find('img').attr('src', result.images[i].src);
                                divarray[i] = odiv;
                                $('div#widgetimage a.add').before(odiv);
                            }
                        }

                        if (result.names && result.names.length > 0) {
                            for (let n = 0; n < result.names.length; n++) {
                                $("input#name-" + result.names[n].langid).val(result.names[n].name);
                            }
                        }

                        if (result.short_descriptions && result.short_descriptions.length > 0) {
                            for (let d = 0; d < result.short_descriptions.length; d++) {
                                $("input#description-" + result.short_descriptions[d].langid).val(result.short_descriptions[d].short_description);
                            }
                        }
                        if (result.options && result.options.length > 0) {
                            $.get('/admin/bargain/productOption/?id=' + choosenProductId, function (optionhtml) {
                                $("div#productoptiosdiv").html(optionhtml);
                            });
                        } else {
                            $("div#productoptiosdiv").html('');
                        }
                        if (result.descriptions && result.descriptions.length > 0) {
                            for (let c = 0; c < result.descriptions.length; c++) {
                                CKEDITOR.instances["content-" + result.descriptions[c].langid].setData(result.descriptions[c].description);
                            }
                        }
                        $("input#price").val(result.price);
                        $("input#choosen_product_id").val(result.id);
                        $("input#choosen_store_id").val(result.store_id);
                        if (result.is_virtual) {
                            $("input#choosen_free_shipping").val(1);
                        } else {
                            $("input#choosen_free_shipping").val(0);
                        }
                        $("input#choosen_weight").val(result.weight);
                        //                       $("input#choosen_warehouse_id").val(result.warehouse_id);
                    } else {

                    }
                });
            } else {
                alert("Please choose product!");
                return false;
            }

        });

        productload();
    });
}));

