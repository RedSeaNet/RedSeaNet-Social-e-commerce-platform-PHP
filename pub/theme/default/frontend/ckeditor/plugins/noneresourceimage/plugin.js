(function () {
    CKEDITOR.plugins.add('noneresourceimage', {
        icons: 'noneresourceimage',
        init: function (editor) {
            editor.addCommand('noneresourceimage', CKEDITOR.plugins.noneresourceimage.commands.noneresourceimage);
            editor.ui.addButton('Noneresourceimage', {
                label: 'Image',
                icon: this.path + 'images/sysimage.png',
                command: 'noneresourceimage'
            });
            CKEDITOR.dialog.add("noneresourceimage", this.path + "dialogs/noneresourceimage.js")
        }
    });
    CKEDITOR.plugins.noneresourceimage = {
        commands: {
            noneresourceimage: {
                exec: function (editor) {
                    $('body>form.upload').remove();
                    var f = document.createElement('form');
                    f.className = 'upload';
                    f.action = $(editor.element.$).parents('[data-upload]').data('upload');
                    f.method = 'post';
                    f.enctype = 'multipart/form-data';
                    f.hidden = 'hidden';
                    var o = document.createElement('input');
                    $(o).attr({type: 'file', accept: 'image/jpeg,image/png,image/gif', hidden: 'hidden', name: 'img'}).appendTo(f);
                    $(f).appendTo(document.body);
                    $(o)
                            .one('change', function () {
                                var oimg = document.createElement('img');
                                if (typeof FileReader !== 'undefined') {
                                    if (this.files[0].size > 2097152) {
                                        alert(translate('Each image should not be over 2MB.'));
                                    } else {
                                        var reader = new FileReader();
                                        reader.onload = function (e) {
                                            oimg.src = e.target.result;
                                            editor.insertHtml(oimg.outerHTML);
                                        };
                                        reader.readAsDataURL(o.files[0]);
                                    }
                                } else {
                                    o.select();
                                    var src = document.selection.createRange().text;
                                    $(oimg).css('filter', 'progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src="' + src + '"');
                                    editor.insertHtml(oimg.outerHTML);
                                }
                            }).trigger('click');
                }
            }
        }
    };
})();



