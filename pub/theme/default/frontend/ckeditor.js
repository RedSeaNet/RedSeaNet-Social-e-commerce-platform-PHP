(function (factory) {
    if (typeof define === "function" && define.amd) {
        define(["jquery"], factory);
    } else if (typeof module === "object" && module.exports) {
        module.exports = factory(require("jquery"));
    } else {
        window.ckEditorInit = factory(jQuery);
    }
}(function ($) {
    var init = function () {
        if ($('html').attr('lang') != 'null' && $('html').attr('lang') != '') {
            var cklanguage = $('html').attr('lang');
        } else {
            var cklanguage = 'zh';
        }
        window.CKEDITOR_BASEPATH = GLOBAL.PUBURL + 'frontend/ckeditor/';
        $('textarea.htmleditor:not(.loaded)').each(function () {
            $(this).addClass('loaded');
            if ($(this).hasClass('fullbar')) {
                if ($(this).hasClass('noneresourceimage')) {
                    $(this).ckeditor({
                        height: '300',
                        width: 'auto',
                        language: cklanguage,
                        toolbarGroups: [{name: 'document', groups: ['mode', 'document', 'doctools']},
                            {name: 'clipboard', groups: ['clipboard', 'undo']},
                            {name: 'editing', groups: ['find', 'selection', 'spellchecker']},
                            {name: 'forms'},
                            '/',
                            {name: 'basicstyles', groups: ['basicstyles', 'cleanup']},
                            {name: 'paragraph', groups: ['list', 'indent', 'blocks', 'align']},
                            {name: 'links'},
                            {name: 'insert'},
                            '/',
                            {name: 'styles'},
                            {name: 'colors'},
                            {name: 'tools'},
                            {name: 'others'},
                            {name: 'about'}],
                        removeButtons: 'Image',
                        disableNativeSpellChecker: false,
                        scayt_autoStartup: false,
                        customConfig: './noneresoureconfig.js'
                    });
                } else {
                    $(this).ckeditor({
                        height: '300',
                        width: 'auto',
                        language: cklanguage,
                        toolbarGroups: [{name: 'document', groups: ['mode', 'document', 'doctools']},
                            {name: 'clipboard', groups: ['clipboard', 'undo']},
                            {name: 'editing', groups: ['find', 'selection', 'spellchecker']},
                            {name: 'forms'},
                            '/',
                            {name: 'basicstyles', groups: ['basicstyles', 'cleanup']},
                            {name: 'paragraph', groups: ['list', 'indent', 'blocks', 'align']},
                            {name: 'links'},
                            {name: 'insert'},
                            '/',
                            {name: 'styles'},
                            {name: 'colors'},
                            {name: 'tools'},
                            {name: 'others'},
                            {name: 'about'}],
                        removeButtons: 'Image',
                        disableNativeSpellChecker: false,
                        scayt_autoStartup: false,
                        customConfig: './config.js'
                    });
                }
            } else {
                if ($(this).hasClass('noneresourceimage')) {
                    $(this).ckeditor({
                        height: '300',
                        width: 'auto',
                        language: cklanguage,
                        toolbarGroups: [
                            {"name": "basicstyles", "groups": ["basicstyles"]},
                            {"name": "links", "groups": ["links"]},
                            {"name": "paragraph", "groups": ["list", "indent", "blocks", "align"]},
                            {"name": "document", "groups": ["mode"]},
                            {"name": "insert", "groups": ["insert"]},
                            {"name": "styles", "groups": ["styles"]},
                            {name: 'colors'},
                            {name: 'others'}
                        ],
                        removeButtons: 'Underline,Strike,Subscript,Superscript,Anchor,Styles,Specialchar,about,Image',
                        disableNativeSpellChecker: false,
                        scayt_autoStartup: false,
                        customConfig: './noneresoureconfig.js'
                    });
                } else {
                    $(this).ckeditor({
                        height: '300',
                        width: 'auto',
                        language: cklanguage,
                        toolbarGroups: [
                            {"name": "basicstyles", "groups": ["basicstyles"]},
                            {"name": "links", "groups": ["links"]},
                            {"name": "paragraph", "groups": ["list", "indent", "blocks", "align"]},
                            {"name": "document", "groups": ["mode"]},
                            {"name": "insert", "groups": ["insert"]},
                            {"name": "styles", "groups": ["styles"]},
                            {name: 'colors'},
                            {name: 'others'}
                        ],
                        removeButtons: 'Underline,Strike,Subscript,Superscript,Anchor,Styles,Specialchar,about,Image',
                        disableNativeSpellChecker: false,
                        scayt_autoStartup: false,
                        customConfig: './config.js'
                    });
                }
            }
        });
    };
    $(window).on('load', init);
    return init;
}));
