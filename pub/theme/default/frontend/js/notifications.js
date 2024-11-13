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
        if (!GLOBAL.AJAX) {
            GLOBAL.AJAX = {};
        }
        $("ul#mynotificatons").infiniteScroll({
            path: function () {
                if (this.loadCount < 40) {
                    let nextIndex = this.loadCount + 2;
                    let loadurl = $("ul#mynotificatons").data('url');
                    if (loadurl) {
                        return loadurl + `&page=${nextIndex}`;
                    } else {
                        return '';
                    }

                }
            },
            responseBody: 'text',
            history: false,
            status: '.mynotificatons-scroller-status',
            scrollThreshold: 100,
            checkLastPage: true,
            loading: {
                finishedMsg: 'No more pages to load.'
            }
        });
        $("ul#mynotificatons").on('load.infiniteScroll', function (event, response) {
            if (!response || !Object.keys(response).length) {
                $("ul#mynotificatons").data('infiniteScroll').scrollThreshold = false;
                return;
            }
            let htmlArticle = $(response).find('li');
            if (htmlArticle.length > 0) {
                var $htmlArticle = $(htmlArticle);
                $("ul#mynotificatons").append($htmlArticle);

            } else {
                $("ul#mynotificatons").infiniteScroll('destroy')
            }

        });
        $("ul#systemnotificatons").infiniteScroll({
            path: function () {
                if (this.loadCount < 40) {
                    let nextIndex = this.loadCount + 2;
                    let loadurl = $("ul#systemnotificatons").data('url');
                    if (loadurl) {
                        return loadurl + `&page=${nextIndex}`;
                    } else {
                        return '';
                    }

                }
            },
            responseBody: 'text',
            history: false,
            status: '.systemnotificatons-scroller-status',
            scrollThreshold: 100,
            checkLastPage: true,
            loading: {
                finishedMsg: 'No more pages to load.'
            }
        });
        $("ul#systemnotificatons").on('load.infiniteScroll', function (event, response) {
            if (!response || !Object.keys(response).length) {
                $("ul#systemnotificatons").data('infiniteScroll').scrollThreshold = false;
                return;
            }
            let htmlArticle = $(response).find('li');
            if (htmlArticle.length > 0) {
                var $htmlArticle = $(htmlArticle);
                $("ul#systemnotificatons").append($htmlArticle);

            } else {
                $("ul#systemnotificatons").infiniteScroll('destroy')
            }

        });

    });
}));
