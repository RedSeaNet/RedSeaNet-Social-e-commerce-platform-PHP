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
                        var t = $(o).parents('.input-box,ul').first();
                        $(json.removeLine).each(function () {
                            $(t).find('[data-id=' + this + ']').remove();
                        });
                    } else {
                        if ($(o).is('menu a')) {
                            var t = $('.grid [href="' + $(o).attr('href') + '"][data-params="' + $(o).data('params') + '"]').parentsUntil('ul.product-list').last();
                        } else {
                            var t = $(o).parentsUntil('ul.product-list').last();
                        }
                        if ($(t).is('ul,li')) {
                            $(t).remove();
                        }
                    }
                }
            } catch (e) {

            }
        };
        var init = function () {
            $('.posts-list').on('click', 'a.review,a.like', function () {
                var l = $(this).parents('li').first().children('.reviews,.like');
                if ($(l).is(':empty')) {
                    $(l).html('<span class="fa fa-spin fa-spinner"></span>');
                    $.get($(this).attr('href')).success(function (xhr) {
                        $(l).html(xhr);
                    });
                } else {
                    $(l).toggle();
                }
                return false;
            }).on('afterajax.redseanet', 'a.like,a.dislike', function (e, json) {
                if (typeof json.data !== 'undefined') {
                    $('.number', this).text(json.data);
                }
            }).on('click', '.input-box.captcha .mask', function () {
                var p = $(this).parent('.captcha');
                $(p).removeClass('show');
                $('img.captcha', p).attr('src', function () {
                    return this.src;
                }).removeAttr('src');
            });
            $(document.body).on('submit', 'form.form-review', function () {
                if (!$('[name].invalid:not([name=captcha])', this).length && $('[name=captcha]').is('.invalid')) {
                    $('.input-box.captcha', this).addClass('show');
                    $('.input-box.captcha img.captcha', this).trigger('reload');
                    return false;
                }
            });

        };
        $('#modal-review').on('show.bs.modal', function (e) {
            if (!$(e.relatedTarget).data('post') && !$(e.relatedTarget).data('id')) {
                return false;
            }
            var ref = $('blockquote', this);
            if (ref.length) {
                $(ref).html('');
                if ($(e.relatedTarget).data('ref-user')) {
                    var u = document.createElement('span');
                    u.className = 'user';
                    $(u).text($(e.relatedTarget).data('ref-user')).appendTo(ref);
                }
                if ($(e.relatedTarget).data('ref-content')) {
                    var c = document.createElement('p');
                    c.className = 'content';
                    $(c).text($(e.relatedTarget).data('ref-content')).appendTo(ref);
                }
                $('[name="reference"]', this).val($(e.relatedTarget).data('ref-id') || '');
            }
            $('[name="post_id"]', this).val($(e.relatedTarget).data('post') || '');
            if ($(e.relatedTarget).data('id')) {
                $('[name="id"]', this).val($(e.relatedTarget).data('id'));
                var p = $(e.relatedTarget).parents('.review');
                $('[name="content"]', this).val($('article .content', p).html());
                $('[name="subject"]', this).val($('article .subject', p).text());
            }
            $(this).parseEmotion();
            $(this).find('.face-icon').click(function (event) {
                $(this).sinaEmotion($('.text'));
                event.stopPropagation();
            });
        });

        $('.product-detail .nav a[href="#forum"][data-bs-toggle="tab"]').one('show.bs.tab', function () {
            $('.product-detail .tab-content #forum.tab-pane').on('click', '.pager a', function () {
                var p = $(this).parents('.reviews').first();
                var c = $(p).data('part');
                var h = $(this).attr('href') + '&part=' + c;
                if (GLOBAL.AJAX[h] && GLOBAL.AJAX[h].readyState < 4) {
                    GLOBAL.AJAX[h].abort();
                }
                $(p).addClass('loading');
                GLOBAL.AJAX[h] = $.get(h, function (response) {
                    $(p).after(response);
                    $(p).remove();
                });
                return false;
            }).load($('.product-detail .tab-content #forum.tab-pane [data-url]').data('url') + '?product_id=' + $('[name=product_id]').val(), function () {
                $('.product-detail .tab-content #forum.tab-pane .new-post a.login').attr('href', function () {
                    return $(this).attr('href').replace(/\?success_url\=[^\&]+/, '?success_url=' + btoa(location.href).replace(/[\/\=\+]/g, function (e) {
                        return {'/': '_', '+': '-', '=': ''}[e];
                    }));
                });
                init();
            });
        });
        $('.post-view .details .poll input').click(function () {
            var p = $(this).parent('li').parent();
            var m = parseInt($(p).data('max-choices') || 1);
            if ($('input:checked', p).length >= m) {
                $('input:not(:checked)', p).prop('disabled', true);
            } else {
                $('input:disabled', p).prop('disabled', false);
            }
        });

        $('.forum-post-edit form').on('change', '[name]', function () {
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
        var loadMorePost = function () {
            var $infiniteMinistryContainer = $("#masonry");
            var msnry = $infiniteMinistryContainer.masonry({
                itemSelector: '.item',
                percentPosition: true
            });

            $infiniteMinistryContainer.infiniteScroll({
                path: function () {
                    if (this.loadCount < 40) {
                        let nextIndex = this.loadCount + 2;
                        let loadurl = $infiniteMinistryContainer.data('url');
                        if (loadurl) {
                            return loadurl + `&page=${nextIndex}`;
                        } else {
                            return '';
                        }

                    }
                },
                responseBody: 'text',
                history: false,
                status: '.page-load-status',
                scrollThreshold: 100,
                checkLastPage: true,
                outlayer: msnry,
                loading: {
                    finishedMsg: 'No more pages to load.'
                }
            });
            $infiniteMinistryContainer.imagesLoaded().progress(function () {
                msnry.masonry('layout');
            });

            $infiniteMinistryContainer.on('load.infiniteScroll', function (event, response) {
                if (!response || !Object.keys(response).length) {
                    $infiniteMinistryContainer.data('infiniteScroll').scrollThreshold = false;
                    return;
                }
                let htmlArticle = $(response).find('li');
                if (htmlArticle.length > 0) {
                    console.log(htmlArticle);
                    $("input#forum_article_pager").val(parseInt($("input#forum_article_pager").val()) + 1);
                    var $htmlArticle = $(htmlArticle);
                    //$htmlArticle.css({ opacity: 0 });
                    $infiniteMinistryContainer.append($htmlArticle);
                    $infiniteMinistryContainer.masonry('appended', $htmlArticle, true);

                    $htmlArticle.imagesLoaded(function (msnry) {
                        //$htmlArticle.animate({ opacity: 1 });
                        //$infiniteMinistryContainer.masonry('appended', $htmlArticle, true);
                        //msnry.layout();
                        $infiniteMinistryContainer.masonry({
                            itemSelector: '.item',
                            percentPosition: true
                        });
                    }).progress(function (instance, image) {
                        //var result = image.isLoaded ? 'loaded' : 'broken';
                        //console.log('加载结果 ' + result + ' 图片地址 ' + image.img.src);
                        if (!image.isLoaded) {
                            $(image.img).attr('src', GLOBAL.BASE_URL + 'pub/theme/default/frontend/images/placeholder.png');
                        }
                        //msnry.layout();
                        $infiniteMinistryContainer.masonry({
                            itemSelector: '.item',
                            percentPosition: true
                        });
                    });
                } else {
                    $infiniteMinistryContainer.infiniteScroll('destroy')
                }

            });

            $infiniteMinistryContainer.on('request.infiniteScroll', function (event, path, fetchPromise) {
                console.log(`Loading page: ${path}`);
            });
            $infiniteMinistryContainer.on('last.infiniteScroll', function (event, body, path) {
                console.log(`Last page hit on ${path}`);
            });
        }
        var showMoreNChildren = function ($children, n) {
            //显示某jquery元素下的前n个隐藏的子元素
            var $hiddenChildren = $children.filter(":hidden");
            var cnt = $hiddenChildren.length;
            for (var i = 0; i < n && i < cnt; i++) {
                $hiddenChildren.eq(i).show();
            }
            return cnt - n;//返回还剩余的隐藏子元素的数量
        };

        var showMore = function (selector) {
            if (selector === undefined) {
                selector = ".showMoreNChildren"
            }
            ;
            //对页中现有的class=showMoreNChildren的元素，在之后添加显示更多条，并绑定点击行为
            $('.showMoreNChildren').each(function () {
                var pagesize = $(this).attr("pagesize") || 10;
                var $children = $(this).children('li');
                if ($children.length > pagesize) {
                    for (var i = pagesize; i < $children.length; i++) {
                        $children.eq(i).hide();
                    }
                    $("<p class='showMorehandle'>" + translate('Read More') + "</p>").insertAfter($(this)).click(function () {
                        if (showMoreNChildren($children, pagesize) <= 0) {
                            //如果目标元素已经没有隐藏的子元素了，就隐藏“点击更多的按钮条”
                            $(this).hide();
                        }
                        ;
                    });
                }
            }).load(showMore);
        };

        loadMorePost();
        $(".nav-main ul li a").each(function () {
            if ($(this).attr('href') == location.href) {
                $(this).parent('li').addClass('cur').siblings('li').removeClass('cur');
            }
        });
        // 绑定表情
        //$('.face-icon').sinaEmotion($('.text'));
        $('.face-icon').click(function (event) {
            $(this).sinaEmotion($('.text'));
            event.stopPropagation();
        });
        $('.sina-emotion-content').parseEmotion();
    });

    $(function () {
        $li1 = $(".apply_nav .apply_array");
        $window1 = $(".row .apply_w");
        $left1 = $(".row .img_l");
        $right1 = $(".row .img_r");
        $window1.css("width", $li1.length * 166);
        var lc1 = 0;
        var rc1 = $li1.length - 5;
        $left1.click(function () {
            if (lc1 < 1) {
                alert(translate('It is already the first picture.'));
                return;
            }
            lc1--;
            rc1++;
            $window1.animate({left: '+=166px'}, 1000);
        });
        $right1.click(function () {
            if (rc1 < 1) {
                alert(translate('It is already the last picture.'));
                return;
            }
            lc1++;
            rc1--;
            $window1.animate({left: '-=166px'}, 1000);
        });
    });


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
    }).on('click.redseanet.copy', "a[data-copy]", function () {
        var o = this;
        if ($(o).data("copy") == "forward") {
            var clipBoardContent = "";
            clipBoardContent += $(o).data("title");
            clipBoardContent += ": ";
            clipBoardContent += $(o).data("link");
            var textarea = document.createElement("textarea");
            textarea.textContent = clipBoardContent;
            textarea.style.position = "fixed"; // Prevent scrolling to bottom of page in MS Edge.
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand("copy");
            document.body.removeChild(textarea);
            alert("Copy successfully!");
//            $("div.toast .toast-body").html("Copy successfully!");
//            $("div.toast")
//                    .toast({animation: true, autohide: true, delay: 2000})
//                    .toast("show");
//            $(o).find("i").addClass("sys-red");
        }
        return false;

    }).on('click reload', 'img.captcha', function () {
        if ($(this).is('[data-src]')) {
            $(this).attr('src', $(this).data('src') + '?' + (new Date().getTime())).removeAttr('data-src');
        } else {
            $(this).attr('src', $(this).attr('src').replace(/\?.+$/, '') + '?' + (new Date().getTime()));
        }
    });

}));