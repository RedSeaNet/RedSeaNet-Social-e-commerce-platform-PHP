(function (factory) {
    if (typeof define === "function" && define.amd) {
        define(["jquery", "highcharts"], factory);
    } else if (typeof module === "object" && module.exports) {
        module.exports = factory(require(["jquery", "highcharts"]));
    } else {
        factory(jQuery);
    }
}(function ($) {
    var loadData = function (canvas, params) {
        var count = $('.dashboard [data-url]').length;
        $('.dashboard [data-url]').each(function () {
            var o = this;
            var u = $(o).data('url');
            $.get(u + (u.indexOf('?') === -1 ? '?' : '&') + params, function (xhr) {
                var r = typeof xhr === 'string' ? eval('(' + xhr + ')') : xhr;
                for (var i in r) {
                    $(o).find('.data.' + i).text(r[i]);
                }
                var data = [];
                var cat = [];
                for (var i in r['filted']) {
                    data.push(r['filted'][i]);
                }
                for (var i in r['keys']) {
                    cat.push(r['keys'][i]);
                }
                if (count-- == $('.dashboard [data-url]').length) {
                    var axis = $('#canvas').highcharts().xAxis;
                    for (var i in axis) {
                        axis[i].remove(false);
                    }
                    $(canvas).highcharts().addAxis({categories: cat}, true, false);
                }
                $(canvas).highcharts().addSeries({
                    name: $(o).data('title'),
                    data: data
                }, false);
                if (r['compared']) {
                    var data = [];
                    for (var i in r['compared']) {
                        data.push(r['compared'][i]);
                    }
                    $(canvas).highcharts().addSeries({
                        name: $(o).data('title'),
                        data: data
                    }, false);
                }
                if (!count) {
                    $(canvas).highcharts().redraw();
                }
            });
        });
    };
    var drawChart = function (canvas) {
        "use strict";
        var colors = [];
        $('.dashboard [data-url]').each(function () {
            colors.push($(this).data('color'));
        });
        $(canvas).highcharts({
            credits: false,
            colors: colors,
            title: {text: ''},
            subtitle: {text: ''},
            yAxis: {title: ''},
            legend: {align: 'right'},
            series: []
        });
        loadData(canvas, 'filter=d');
    };
    $(function () {
        Highcharts.setOptions({
            lang: {
                printChart: translate("Print chart"),
                downloadPNG: translate("Download PNG image"),
                downloadJPEG: translate("Download JPEG image"),
                downloadPDF: translate("Download PDF document"),
                downloadSVG: translate("Download SVG vector image"),
                contextButtonTitle: translate("Chart context menu"),
                noData: translate("No data to display")
            }
        });
        drawChart('#canvas');
        $('.dashboard #filter').change(function () {
            if (this.value !== 'c') {
                var series = $('#canvas').highcharts().series;
                for (var i in series) {
                    series[i].remove(false);
                }
                $('.dashboard #filter~*').hide();
                loadData('#canvas', 'filter=' + this.value);
            } else {
                $('.dashboard #filter~*').show();
            }
        });
        $('.dashboard #compare').click(function () {
            var series = $('#canvas').highcharts().series;
            for (var i in series) {
                series[i].remove(false);
            }
            loadData('#canvas', 'filter=c&' + $('.dashboard .filter .date').serialize());
        });
    });
}));