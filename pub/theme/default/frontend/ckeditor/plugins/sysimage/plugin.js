(function () {
    CKEDITOR.plugins.add('sysimage', {
        icons: 'sysimage',
        init: function (editor) {
            editor.addCommand('sysimage', CKEDITOR.plugins.sysimage.commands.sysimage);
            editor.ui.addButton('SysImage', {
                label: 'Image',
                icon: this.path + 'images/sysimage.png',
                command: 'sysimage'
            });
            CKEDITOR.dialog.add("sysimage", this.path + "dialogs/sysimage.js")
        }
    });
    CKEDITOR.plugins.sysimage = {
        commands: {
            sysimage: {
                exec: function (editor) {
                    $('#modal-insert').modal('show', editor);
                    $(editor).off('resource.selected').one('resource.selected', function (e, a) {
                        this.insertHtml("<img src='" + $('img', a).attr('src').replace(/resized\/\d+x\d*\//, '') + "'>");
                    });
                }
            }
        }
    };
})();



