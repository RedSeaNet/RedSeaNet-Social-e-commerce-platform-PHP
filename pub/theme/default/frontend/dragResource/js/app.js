/* global path */


//'use strict';
//Make sure jQuery has been loaded before app.js
if (typeof jQuery === "undefined") {
    throw new Error("HtmlEditor requires jQuery");
}


$(function () {
    //Set up the object
    _init();

});

/* ----------------------------------
 * ----------------------------------
 * All HTMLeditor functions are implemented below.
 */


function _init() {
    $(window).resize(function () {
        $("body").css("min-height", $(window).height() - 90);
        $(".htmlpage").css("min-height", $(window).height() - 160)
    });


    $("body").css("min-height", $(window).height() - 90);
    $(".htmlpage").css("min-height", $(window).height() - 160);
    $(".htmlpage, .htmlpage .column").sortable({connectWith: ".column", opacity: .35, handle: ".drag"});
    $(".sidebar-nav .lyrow").draggable({
        connectToSortable: ".htmlpage",
        helper: "clone",
        handle: ".drag",
        drag: function (e, t) {
            t.helper.width(400);
        },
        stop: function (e, t) {
            $(".htmlpage .column").sortable({opacity: .35, connectWith: ".column"});
        }
    });

    var data_id = 0;
    $(".sidebar-nav .box").draggable({
        connectToSortable: ".column",
        helper: "clone",
        handle: ".preview",
        start: function (e, t) {
            var obj = t.helper;
            var dataType = $(obj).attr('data-type');
            var showType = $(obj).attr('show-type');

            if (showType == "1")
            {
                var htmls = template(dataType);

                $(".sidebar-nav .box.box-element[data-type='" + dataType + "']").find(".view").html(htmls);
                data_id = $(".sidebar-nav .box.box-element[data-type='" + dataType + "']").find(".view").find(".content.function-tag").dataId();
                $(obj).find(".view").html('模块');
            }

        },
        drag: function (e, t) {
            t.helper.width(400);

        },
        stop: function (e, t) {
            if (template_id == 0)
            {
                saveData();
                return;
            }

            if ($(".htmlpage .lyrow").length <= 0)
            {
                //alert("功能模块必须拖入表格内容区\n请先拖入表格内容区");
                layer.msg('功能模块必须拖入表格内容区<br>请先拖入表格内容区', {shade: [0.3, '#fff'], time: 2200});
                return;
            }
            var obj = t.helper;
            var dataType = $(obj).attr('data-type');
            var showType = $(obj).attr('show-type');

            var final_obj = $(".htmlpage .box.box-element[data-type='" + dataType + "']").find(".view").find(".content.function-tag[data-id='" + data_id + "']");
            final_obj.closest(".box.box-element[data-type='" + dataType + "']").attr('id', data_id);
            if (final_obj.length > 0)
                call_ajax_data(data_id, dataType);
        }
    });





    $(document).on('click', 'a.clone', function (e) {
        e.preventDefault();
        var _s = $(this);

        var _row = _s.parent().clone();
        _row.hide();
        _row.insertAfter(_s.parent());
        _row.slideDown();

    });

    $(document).on('click', 'a.stable', function (e) {
        $obj = $(this);
        var data_tag = $obj.attr("data-tag");
        var data_name = $obj.attr("data-name");
        var part_id = $obj.assignId();
        layer.open({
            id: 'iframe_layer',
            type: 2,
            area: ['720px', '560px'],
            title: data_name + " 属性",
            fix: true, //不固定
            maxmin: true,
            content: site_path + 'retailer/store/func?functions=' + data_tag + '&part_id=' + part_id + "&current_template_id=" + current_template_id,
            btn: ['保存', '取消'],
            yes: function () {

                var id = $('#iframe_layer').find('iframe').attr("name");
                $("#focusBtn", window.frames[id].document).trigger("click");


            },
            btn2: function () {
                layer.closeAll();
            }
        });

    });



    $(document).on('click', 'a.settings', function (e) {
        var _s = $(this);
        var part_id = _s.parent().parent().assignId();
        $obj = $(this).closest('.box.box-element.ui-draggable').find('.view').find(".content.function-tag");
        var data_tag = $obj.attr("data-tag");
        var areas = ['700px', '530px'];
        if (data_tag == "paragraph")
            areas = ['1030px', '650px'];

        var data_name = $obj.attr("data-name");
        layer.open({
            id: 'iframe_layer',
            type: 2,
            area: areas,
            title: data_name + " 属性",
            fix: true, //不固定
            maxmin: true,
            content: site_path + 'retailer/store/func?functions=' + data_tag + '&part_id=' + part_id + "&current_template_id=" + current_template_id,
            btn: ['保存', '取消'],
            yes: function () {

                var id = $('#iframe_layer').find('iframe').attr("name");
                $("#focusBtn", window.frames[id].document).trigger("click");


            },
            btn2: function () {
                layer.closeAll();
            }
        });



        // --- original method 
//      e.preventDefault();
//      var  _s = $(this);
//
//      var  part_id = _s.parent().parent().assignId();
//      var  part = _s.parent().parent();
//      var  column = _s.parent().parent().parent('.column');
//      var  row = _s.parent().parent().parent().parent('.row');
//
//      prepareEditor(part, row, column);
    });


    $("#customize_button").click(function () {
        if (template_id == 0)
        {
            layer.msg('新建模板请先装修首页', {shade: [0.3, '#fff'], time: 2200});
            return;
        }

        var data_tag = "customize";
        layer.open({
            id: 'iframe_layer',
            type: 2,
            area: ['720px', '560px'],
            title: "自定义页面设置",
            fix: true, //不固定
            maxmin: true,
            content: site_path + 'retailer/store/func?functions=' + data_tag + "&template_id=" + template_id,
            btn: ['关 闭'],
            btn2: function () {
                layer.closeAll();
            }
        });
    });

    $("#page_type_select").change(function () {
        if (template_id == 0 && $(this).attr('page_type') != "0")
        {
            layer.msg('新建模板请先装修首页', {shade: [0.3, '#fff'], time: 2200});
            $(this).find('option[page_type=0]').attr('selected', 'selected');
            return;
        }

        if ($(this).val() == "-1")
            return;
        var pageType = $(this).find('option:selected').attr('page_type');
        var urls = site_path;
        if (pageType == "0")
        {
            urls += "retailer/store/decoration?id=" + template_id + "&page_type=" + pageType;
        }

        if (pageType == "2")
        {
            urls += "retailer/store/decorationProductDetail?template_id=" + template_id + "&page_type=" + pageType + "&template_name=" + template_name;
        }

        if (pageType == "1")
        {
            urls += "retailer/store/decorationCustomize?id=" + $(this).val() + "&template_name=" + template_name + "&page_type=" + pageType + "&template_id=" + template_id;
        }

        window.location = urls;

    });

    $('a.btnpropa').on('click', function () {
        var rel = $(this).attr('rel');
        $('#buttonContainer a.btn').removeClass('btn-default')
                .removeClass('btn-success')
                .removeClass('btn-info')
                .removeClass('btn-danger')
                .removeClass('btn-info')
                .removeClass('btn-primary')
                .removeClass('btn-link')
                .addClass(rel);
    });

    $('a.btnpropb').on('click', function () {
        var rel = $(this).attr('rel');
        $('#buttonContainer a.btn').removeClass('btn-lg')
                .removeClass('btn-md')
                .removeClass('btn-sm')
                .removeClass('btn-xs')
                .addClass(rel);
    });

    $('a.btnprop').on('click', function () {
        var rel = $(this).attr('rel');
        $('#buttonContainer a.btn').toggleClass(rel);
    });

    $('#preferences').on('hidden.bs.modal', function () {
        $('#ht').hide();
        $('#image').hide();
        $('#text').hide();
        $('#code').hide();
        $('#buttons').hide();
    });

    $("#save").click(function (e) {
        downloadLayoutSrc();
        saveData();
    });

    $("#clear").click(function (e) {
        e.preventDefault();
        clearDemo()
    });

    $("#devpreview").click(function () {
        $("body").removeClass("edit sourcepreview");
        $("body").addClass("devpreview");
        removeMenuClasses();
        $(this).addClass("active");
        return false
    });


    $("#edit").click(function () {
        $('#add').hide();
        $("body").removeClass("devpreview sourcepreview");
        $("body").removeClass("tablet mobile");
        $("body").addClass("edit");
        removeMenuClasses();
        $(this).addClass("active");
        $('.htmlpage .column').css('padding', '39px 19px 24px');
        $(".htmlpage .box .view").css('padding', '7px');
        component_reset();
        if (page_type == 0)
        {
            $(".stable_top").addClass("notice");
            $(".stable_top").find("button.sets").show();
        }
        return false
    });


    $('#gallery').click(function () {
        $('#thepref').slideUp();
        $('#mediagallery').slideDown();
    });


    $("#sourcepreview").click(function () {
        $(".edit.container").removeClass('edit')
        //$('#pc').addClass('active');
        //$('#add').show();
        $("body").removeClass("edit");
        $("body").addClass("devpreview sourcepreview");
        removeMenuClasses();
        $(this).addClass("active");
        $(".htmlpage .box .view").css('padding', '0px');
        $('.htmlpage .column').css('padding', '11px');
        $(".stable_top").removeClass("notice");
        $(".stable_top").find("button.sets").hide();
        component_reset();
        if ($(this).data("view-url")) {
            if (confirm('预览前先要保存， 是否已经保存修改?') === true) {
                window.open($(this).data("view-url"));
            } else {
                return false;
            }
        }
        return false
    });



    $('#pc').click(function () {
        $("body").removeClass("tablet mobile");
        $('#app button').removeClass('active');
        $(this).addClass('active');
    });


    $('#mobile').click(function () {
        $("body").removeClass("tablet");
        $('#app button').removeClass('active');
        $(this).addClass('active');
        $("body").addClass("mobile");
    });


    $('#tablet').click(function () {
        $("body").removeClass("mobile");
        $('#app button').removeClass('active');
        $(this).addClass('active');
        $("body").addClass("tablet");
    });

    $(".nav-header").click(function () {
        $(".sidebar-nav .boxes, .sidebar-nav .rows").hide();
        $(this).next().slideDown()
    });

    removeElm();
    gridSystemGenerator();

}




function handleSaveLayout() {
    var e = $(".htmlpage").html();
    if (e != window.htmlpageHtml) {
        saveLayout();
        window.htmlpageHtml = e
    }
}

function gridSystemGenerator() {
    $(".lyrow .preview input").bind("keyup", function () {
        var e = 0;
        var t = "";
        var n = false;
        var r = $(this).val().split(" ", 12);
        $.each(r, function (r, i) {
            if (!n) {
                if (parseInt(i) <= 0)
                    n = true;
                e = e + parseInt(i);
                t += '<div class="col-md-' + i + ' column"></div>'
            }
        });
        if (e == 12 && !n) {
            $(this).parent().next().children().html(t);
            $(this).parent().find('.drag').show()
        } else {
            $(this).parent().find('.drag').hide()
        }
    })
}

function removeElm() {
    $(".htmlpage").delegate(".remove", "click", function (e) {
        var b = $(this).parent().css('border');
        //$(this).parent().css('border', '2px solid red');
        var obj = $(this);
        layer.confirm('确定要删除吗？', {
            title: '提示',
            btn: ['确定', '取消'] //按钮
        }, function () {
            e.preventDefault();
            obj.parent().remove();
            layer.closeAll();
            if (!$(".htmlpage .lyrow").length > 0) {
                clearDemo();
            }
        }, function () {
            obj.parent().css('border', b);
        }
        );



//      if (confirm("确定要删除吗?")) {
//          e.preventDefault();
//          $(this).parent().remove();
//
//          if (!$(".htmlpage .lyrow").length > 0) {
//              clearDemo();
//          }
//      } else {
//          $(this).parent().css('border', b);
//      }
    })
}

function clearDemo() {
    $(".htmlpage").empty()
}

function removeMenuClasses() {
    $("#menu-htmleditor li button").removeClass("active")
}

function changeStructure(e, t) {
    $("#download-layout ." + e).removeClass(e).addClass(t)
}

function cleanHtml(e) {
    $(e).parent().append($(e).children().html());
}

function cleanRow(row) {
    row.children('.remove , .drag, .preview').remove();
    row.find('div.ui-sortable').removeClass('ui-sortable');

    row.children('.view').find('br').remove();

    row.children('.view').children('.row').children('.column').each(function () {
        var col = $(this);

        col.removeClass('column');
        col.children('.lyrow').each(function () {
            cleanRow($(this));
        });
        col.children('.box-element').each(function () {
            var element = $(this);
            element.children('.remove , .drag, .configuration, .preview').remove();
            element.parent().append(element.children('.view').html());
            element.remove();
        });
    });
    row.parent().append(row.children('.view').html());
    row.remove();
}

function downloadLayoutSrc() {
    if ($("#edit").hasClass("active"))
    {
        $('.htmlpage .column').css('padding', '11px');
        var htmls = $(".htmlpage").html();
        $('.htmlpage .column').css('padding', '39px 19px 24px');
    } else {
        var htmls = $(".htmlpage").html();
    }

    $("#download-layout").children().html(htmls);

    $("#download-layout").children('.container').each(function (i) {
        var container = $(this);
        container.children('.lyrow').each(function (i) {
            var row = $(this);
            cleanRow(row);
        });
    });
    //$('textarea#model').val($(".htmlpage").html());
    //$('textarea#src').val(style_html($("#download-layout").html()));
    //$('#download').modal('show');
}
$('#srcSave').click(function () {
    ///   $.post(path + '/save.php', {html: style_html($("#download-layout").html())}, function (data) {       }, 'html');
});
function getIndent(level) {
    var result = '',
            i = level * 4;
    if (level < 0) {
        throw "Level is below 0";
    }
    while (i--) {
        result += ' ';
    }
    return result;
}

function style_html(html) {
    html = $.trim(html);
    var result = '',
            indentLevel = 0,
            tokens = html.split(/</);
    for (var i = 0, l = tokens.length; i < l; i++) {
        var parts = tokens[i].split(/>/);
        if (parts.length === 2) {
            if (tokens[i][0] === '/') {
                indentLevel--;
            }
            result += getIndent(indentLevel);
            if (tokens[i][0] !== '/') {
                indentLevel++;
            }

            if (i > 0) {
                result += '<';
            }

            result += parts[0].trim() + ">\n";
            if (parts[1].trim() !== '') {
                result += getIndent(indentLevel) + parts[1].trim().replace(/\s+/g, ' ') + "\n";
            }

            if (parts[0].match(/^(img|hr|br)/)) {
                indentLevel--;
            }
        } else {
            result += getIndent(indentLevel) + parts[0] + "\n";
        }
    }
    return result;
}

function s4() {
    return Math.floor((1 + Math.random()) * 0x10000)
            .toString(16)
            .substring(1);
}

function changeTag() {
    $("#save_html .function-tag").each(function () {
        if ($(this).attr('data-tag') != "paragraph")
            $(this).html("{{" + $(this).attr('data-tag') + ':' + $(this).attr('data-param') + '}}');
    });
    //console.log($("#save_html").html());
    return $("#save_html").html();
}

(function ($) {
    $.fn.assignId = function () {
        var _self = $(this);
        var id = _self.attr('id');
        if (typeof id === typeof undefined || id === false) {
            id = s4() + '-' + s4() + '-' + s4() + '-' + s4();
            _self.attr('id', id);
        }
        return id;
    };

    $.fn.dataId = function () {
        var _self = $(this);
        var id = _self.attr('id');
        if (typeof id === typeof undefined || id === false) {
            id = s4() + '-' + s4() + '-' + s4() + '-' + s4();
            _self.attr('data-id', id);
        }
        return id;
    };

})(jQuery);
