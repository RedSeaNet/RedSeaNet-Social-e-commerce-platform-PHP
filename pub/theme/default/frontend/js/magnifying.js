(function ($) {
    $.fn.imagezoom = function (options) {
        var settings = {
            xzoom: 450,
            yzoom: 450,
            offset: 30,
            position: "BTR"
        };
        if (options) {
            $.extend(settings, options);
        }
        var noalt = '';
        var self = this;
        $(this).on({
            mouseenter: function (ev) {
                if ($(window).width() <= 768 || $('html').is('touchevent')) {
                    return false;
                }
                var imageLeft = $(this).offset().left;
                var imageTop = $(this).offset().top;
                var imageWidth = $(this).get(0).offsetWidth;
                var imageHeight = $(this).get(0).offsetHeight;
                var boxLeft = $(this).parent().offset().left;
                var boxTop = $(this).parent().offset().top;
                var boxWidth = $(this).parent().width();
                var boxHeight = $(this).parent().height();
                noalt = $(this).attr("alt");
                var bigimage = $(this).attr("data-bttrlazyloading-xs-src");
                $(this).attr("alt", '');
                if ($("div.zoomDiv").get().length == 0) {
                    $(document.body).append("<div class='zoomDiv'><img class='bigimg' src='" + bigimage + "'/></div><div class='zoomMask'>&nbsp;</div>");
                }
                if (settings.position == "BTR") {
                    if (boxLeft + boxWidth + settings.offset + settings.xzoom > screen.width) {
                        leftpos = boxLeft - settings.offset - settings.xzoom;
                    } else {
                        leftpos = boxLeft + boxWidth + settings.offset;
                    }
                } else {
                    leftpos = imageLeft - settings.xzoom - settings.offset;
                    if (leftpos < 0) {
                        leftpos = imageLeft + imageWidth + settings.offset;
                    }
                }
                $("div.zoomDiv").css({
                    top: boxTop,
                    left: leftpos
                });
                $("div.zoomDiv").width(settings.xzoom);
                $("div.zoomDiv").height(settings.yzoom);
                $("div.zoomDiv").show();
                $(this).css('cursor', 'crosshair');
                $(document.body).on('mousemove.magnifying', function (e) {
                    mouse = new MouseEvent(e);
                    if (mouse.x < imageLeft || mouse.x > imageLeft + imageWidth || mouse.y < imageTop || mouse.y > imageTop + imageHeight) {
                        mouseOutImage();
                        return;
                    }
                    var bigwidth = $(".bigimg").get(0).offsetWidth;
                    var bigheight = $(".bigimg").get(0).offsetHeight;
                    var scaley = 'x';
                    var scalex = 'y';
                    if (isNaN(scalex) | isNaN(scaley)) {
                        var scalex = (bigwidth / imageWidth);
                        var scaley = (bigheight / imageHeight);
                        $("div.zoomMask").width((settings.xzoom) / scalex);
                        $("div.zoomMask").height((settings.yzoom) / scaley);
                        $("div.zoomMask").css('visibility', 'visible');
                    }
                    xpos = mouse.x - $("div.zoomMask").width() / 2;
                    ypos = mouse.y - $("div.zoomMask").height() / 2;
                    xposs = mouse.x - $("div.zoomMask").width() / 2 - imageLeft;
                    yposs = mouse.y - $("div.zoomMask").height() / 2 - imageTop;
                    xpos = (mouse.x - $("div.zoomMask").width() / 2 < imageLeft) ? imageLeft : (mouse.x + $("div.zoomMask").width() / 2 > imageWidth + imageLeft) ? (imageWidth + imageLeft - $("div.zoomMask").width()) : xpos;
                    ypos = (mouse.y - $("div.zoomMask").height() / 2 < imageTop) ? imageTop : (mouse.y + $("div.zoomMask").height() / 2 > imageHeight + imageTop) ? (imageHeight + imageTop - $("div.zoomMask").height()) : ypos;
                    $("div.zoomMask").css({
                        top: ypos,
                        left: xpos
                    });
                    $("div.zoomDiv").get(0).scrollLeft = xposs * scalex;
                    $("div.zoomDiv").get(0).scrollTop = yposs * scaley;
                });
            },
            mouseleave: function () {
                $(document.body).off('mousemove.manifying');
            }
        });
        function mouseOutImage() {
            $(self).attr("alt", noalt);
            $(document.body).unbind("mousemove");
            $("div.zoomMask").remove();
            $("div.zoomDiv").remove();
        }
        function MouseEvent(e) {
            this.x = e.pageX;
            this.y = e.pageY;
        }
    }
})(jQuery);