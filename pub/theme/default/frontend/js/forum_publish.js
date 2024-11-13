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
        var init = function () {
            $('.options-list').on('click', '.add', function () {
                $(this).parent('li').after($(this).parent().parent().parent().find('template').html());
                return false;
            }).on('click', '.remove', function () {
                $(this).parent('li').remove();
                return false;
            });
            $('button.post_publish_form_btn').on('click', function (e) {
                e.preventDefault();
                let oldButtonHtml = $(this).html();
                $(this).html('<i class="fa fa-spinner" aria-hidden="true"></i>Loading').attr("disabled", true);
                $('form#post_publish_form').validate();
                if (!$('form#post_publish_form').valid()) {
                    $(this).html(oldButtonHtml).attr("disabled", false);
                    return false;
                }

                var filesData = $('form#post_publish_form').find("input:file");
                console.log(filesData);
                if (filesData.length > 0) {
                    var fileOriginInfos = [];
                    for (let f = 0; f < filesData.length; f++) {
                        if ($(filesData[f]).val() != "") {
                            console.log(filesData[f]);
                            console.log(filesData[f].files);
                            console.log(f + '-------');
                            for (let ff = 0; ff < filesData[f].files.length; ff++) {
                                let tmpFile = {key: filesData[f].files[ff].name, type: filesData[f].files[ff].type, dataid: $(filesData[f]).data('id'), dataindex: ff};
                                console.log('tmpFile', tmpFile);
                                fileOriginInfos.push(tmpFile);
                            }
                        }

                    }
                    var imageOriginalNameArray = [];
                    var imagesPreview = $("div#forum-images-comtainer div.preview");
                    console.log(imagesPreview.length);
                    if (imagesPreview.length > 0) {
                        for (let p = 0; p < imagesPreview.length; p++) {
                            console.log($(imagesPreview[p]).data("name"));
                            imageOriginalNameArray.push($(imagesPreview[p]).data("name"));
                        }
                    }
                    if (fileOriginInfos.length > 0) {
                        $('form#post_publish_form').submit();
                    } else {
                        $(this).html(oldButtonHtml).attr("disabled", false);
                        alert('You should add at least one image');
                    }
                } else {
                    $(this).html(oldButtonHtml).attr("disabled", false);
                    alert('You should add at least one image');
                }
                return false;
            });
        }
        init();
        try {
            var totalDataTransfer = new DataTransfer();
            $('form[enctype="multipart/form-data"] .images [hidden][type=file][accept^=image]').attr('multiple', true)
            $('form[enctype="multipart/form-data"]').on('change', '.images [hidden][type=file][accept^=image]', function () {
                if (this.files.length > 0) {
                    $("div#video-section").hide();
                    for (let i = 0; i < this.files.length; i++) {
                        var file = this.files[i];
                        var odiv = $('<div class="preview"></div>');
                        if (typeof FileReader !== 'undefined') {
                            if (file.size > 10500000) {
                                alert(translate('Each image should not be over 10MB.'));
                            } else {
                                if (totalDataTransfer.items.length >= 10) {
                                    alert($(this).data('max-msg'));
                                    break;
                                }
                                let odiv = $('<div class="preview" data-index="' + totalDataTransfer.items.length + '" data-name="' + file.name + '" ></div>');
                                let oimg = "<img class='thumbnail' src='" + URL.createObjectURL(file) + "' />";
                                let ofile = document.createElement('input');
                                $(ofile).prop("type", "file").prop("hidden", "hidden").prop("name", $(this).attr('name')).prop("accept", $(this).attr('accept'));
                                $(ofile).data('index', totalDataTransfer.items.length).data('id', $(this).attr('id') + '-' + totalDataTransfer.items.length);
                                let singerDataTransfer = new DataTransfer();
                                singerDataTransfer.items.add(file);
                                $(ofile).prop("files", singerDataTransfer.files);
                                $(odiv).append(oimg);
                                $(odiv).append(ofile);
                                $(this).before(odiv);
                                totalDataTransfer.items.add(file);

                            }
                        } else {
                            this.select();
                            var src = document.selection.createRange().text;
                            $(odiv).css('filter', 'progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src="' + src + '"');
                        }
                    }
                    $(this).val('');
                    return false;
                    console.log('add image end');
                } else {
                    console.log('this files 0');
                    return false;
                }
            }).on('click', '.images .preview', function () {
                var index = $(this).data('index');
                totalDataTransfer.items.remove(index);
                $(this).parent().find('.preview[data-index]').each(function (i, cont) {
                    if (i > index) {
                        $(cont).attr('data-index', i - 1);
                    }
                });
                $(this).remove();
            });

        } catch (e) {
            console.log('do not support datatransfer!');
            $('form[enctype="multipart/form-data"]').on('change', '.images [hidden][type=file][accept^=image]', function () {
                var odiv = $('<div class="preview"></div>');
                if (typeof FileReader !== 'undefined') {
                    if (this.files[0].size > 10500000) {
                        alert(translate('Each image should not be over 10MB.'));
                    } else {
                        if (dataTransfer.items.length >= 10) {
                            alert($(this).data('max-msg'));
                            return false;
                        }
                        let odiv = $('<div class="preview" data-index="' + dataTransfer.items.length + '" ></div>');
                        let oimg = "<img class='thumbnail' src='" + URL.createObjectURL(file) + "' />";
                        let ofile = '<input type="file" hidden="hidden" name="' + $(this).attr('name') + '" accept="' + $(this).attr('accept') + '" />';
                        $(odiv).append(oimg);
                        $(odiv).append(ofile);
                        $(this).before(odiv);
                    }
                } else {
                    this.select();
                    var src = document.selection.createRange().text;
                    $(odiv).css('filter', 'progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src="' + src + '"');
                }
                $(this).val('');
            }).on('click', '.images .preview', function () {
                var o = $(this).next('[type=file]');
                if ($(this).siblings('.preview').length) {
                    if ($(o).is('[id]')) {
                        $(this).siblings('[type=file]').last().attr('id', $(o).attr('id'));
                    }
                    $(o).remove();
                    $(this).remove();
                } else {
                    $(o).val('');
                    $(this).remove();
                }
            });
        }
        $('form[enctype="multipart/form-data"]').on('change', '.video [type=file][accept^=video]', function () {
            if ($(this).length > 0) {
                $("div#images-section").hide();
                if ($(this)[0].files && $(this)[0].files.length > 0) {
                    var fileurl = URL.createObjectURL($(this)[0].files[0]);
                    var audioElement = new Audio(fileurl);
                    var duration;
                    var _this = $(this);
                    audioElement.addEventListener("loadedmetadata", function (_event) {
                        duration = audioElement.duration;
                        if (duration > 1800) {
                            _this.addClass('invalid');
                            $('label[for="' + _this.attr('name') + '"]').show();
                            $('button.post_publish_form_btn').attr("disabled", true);
                        } else {
                            _this.removeClass('invalid');
                            $('label[for="' + _this.attr('name') + '"]').hide();
                            $('button.post_publish_form_btn').attr("disabled", false);
                        }

                    });
                }
            }
        });
        $('form[enctype="multipart/form-data"]').on('change', 'input[data-id=termsconditions]', function () {
            if ($("input[name=termsconditions]:checked").val() == 1) {
                $("input[data-id=termsconditions]").removeClass("invalid");
                $("label[for=termsconditions]").removeClass("invalid");
            }
        });
        var getRandomInt = function (min, max) {
            min = Math.ceil(min);
            max = Math.floor(max);
            return Math.floor(Math.random() * (max - min)) + min;
        }
        $("div#forum-images-comtainer").sortable({
            cursor: "move",
            items: "div.preview",
            opacity: 0.6,
            revert: true,
            update: function (event, ui) {
                console.log($(this).sortable("toArray"));
            }
        });
        $("input#post_tags").tagsInput({
            autocomplete_url: '/forum/post/getTags/',
            autocomplete: {
                selectFirst: true,
                width: '120px',
                autoFill: true
            },
            height: '100px',
            width: '100%',
            minInputWidth: '100%',
            interactive: true,
            defaultText: translate('Add hashtags'),
            removeWithBackspace: true,
            delimiter: ',',
            minChars: 0,
            maxChars: 255,
            placeholderColor: '#666666'
        });


        $('[data-base]').each(function () {
            var o = this;
            var p = $(o).parents('.input-box').first();
            $(p).hide();
            $(o).find('input,select,textarea,button').each(function () {
                this.disabled = true;
            });
            o.disabled = true;
            var base = $(o).data('base');
            try {
                var target = eval('(' + base + ')');
            } catch (e) {
                var target = base.indexOf(':') === -1 ? eval('({"' + base + '":"1"})') : eval('({' + base + '})');
            }
            var toggle = function (s, t) {
                if (typeof s !== 'object') {
                    s = [s];
                }
                if (typeof t !== 'object') {
                    t = [t];
                }
                var f = false;
                for (var i in s) {
                    if ($.inArray(s[i], t) !== -1) {
                        f = true;
                        break;
                    }
                }
                if (f) {
                    $(p).show();
                    o.disabled = false;
                    $(o).find('input,select,textarea,button').each(function () {
                        this.disabled = false;
                    });
                } else {
                    $(p).hide();
                    o.disabled = true;
                    $(o).find('input,select,textarea,button').each(function () {
                        this.disabled = true;
                    });
                }
            };
            for (var i in target) {
                toggle($(i).is('[type=radio],[type=checkbox]') ? $(i).filter(':checked').val() : $(i).val(), target[i]);
                if ($(i).is('[type=radio],[type=checkbox]')) {
                    $(i).click(function () {
                        toggle(this.checked ? this.value : null, target[i]);
                    });
                } else {
                    $(i).change(function () {
                        toggle($(this).val(), target[i]);
                    });
                }
            }
        });
    });
}));