(function (factory) {
    if (typeof define === "function" && define.amd) {
        define(["jquery"], factory);
    } else if (typeof module === "object" && module.exports) {
        module.exports = factory(require(["jquery"]));
    } else {
        factory(jQuery);
    }
}(function ($) {
    $(function () {
        "use strict";
        var postsload = function (urlStr = "") {
            var postlistdiv = $("div#productpostlist");
            if (postlistdiv.length > 0) {
                if (urlStr == "") {
                    urlStr = postlistdiv.data("url") + "?";
                    urlStr = urlStr + "id=" + ($("input#filterspostid").val() ? $("input#filterspostid").val() : '');
                    urlStr = urlStr + "&title=" + ($("input#filtersposttitle").val() ? $("input#filtersposttitle").val() : '');
                    urlStr = urlStr + "&username=" + ($("input#filterspostcustomer").val() ? $("input#filterspostcustomer").val() : '');
                    urlStr = urlStr + "&limit=" + ($("input#filterspostlimit").val() ? $("input#filterspostlimit").val() : 20);
                }
                urlStr = encodeURI(urlStr);
                postlistdiv.load(urlStr, function () {
                    $.each($('div#relatedposts a.choosenpostsdeletea'), function (i, val) {
                        $("div#postListModal input#postcheckboxlist" + $(val).data("id")).attr('checked', true);
                    });
                    $("div#productpostlist .pagination .page-item a").on("click", function () {
                        postsload($(this).attr("href"));
                        return false;
                    });
                });
            }
            return false;
        };
        $("a#filterssearchbutton").on("click", function () {
            postsload();
        });
        $('div#postListModal').on('hide.bs.modal', function (e) {
            var choosenPosts = $('input[type=checkbox][name=postcheckbox]:checked');
            if (choosenPosts && choosenPosts.length > 0) {
                let htmlString = '';
                for (let p = 0; p < choosenPosts.length; p++) {
                    let deleteA = '<a data-id="' + $(choosenPosts[p]).val() + '" class="choosenpostsdeletea" style="color:red" href="#"><span class="fa fa-trash" aria-hidden="true"></span></a>';
                    if ($('div#relatedposts div#choosenpostiddiv' + $(choosenPosts[p]).val()).length == 0) {
                        htmlString = htmlString + '<div id="choosenpostiddiv' + $(choosenPosts[p]).val() + '">(' + $(choosenPosts[p]).val() + ')<input class="choosenpostsdeletea" type="hidden" name="choosenposts[]" data-id="' + $(choosenPosts[p]).val() + '" value="' + $(choosenPosts[p]).val() + '" />' + $(choosenPosts[p]).data('title') + '&nbsp;&nbsp;&nbsp;&nbsp;' + deleteA + '</div>';
                    }
                }
                $(htmlString).appendTo($('div#relatedposts'));
                $('div#relatedposts a.choosenpostsdeletea').one('click', function () {
                    $("div#choosenpostiddiv" + $(this).data("id")).remove();
                });
            } else {
                alert("Please choose posts!");
                return false;
            }

        });
        postsload();
        $('div#relatedposts a.choosenpostsdeletea').one('click', function () {
            $("div#choosenpostiddiv" + $(this).data("id")).remove();
        });
    });
}));
