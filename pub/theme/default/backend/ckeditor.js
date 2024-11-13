window.onload = function ()
{
    if ($('html').attr('lang') != 'null' && $('html').attr('lang') != '') {
        var cklanguage = $('html').attr('lang');
    } else {
        var cklanguage = 'zh';
    }
    window.CKEDITOR_BASEPATH = GLOBAL.PUBURL + 'backend/ckeditor/';
    // custombar or fullbar toolbar 
    $('textarea.htmleditor').each(function () {
        if ($(this).hasClass('fullbar')) {
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
                removeButtons:'Image',
                disableNativeSpellChecker: false,
                scayt_autoStartup: false
            });
        } else {
            $(this).ckeditor({
                height: '300',
                width: 'auto',
                language: cklanguage,
                toolbarGroups: [
                    {"name": "basicstyles", "groups": ["basicstyles"]},
                    {"name": "links", "groups": ["links"]},
                    {"name": "paragraph", "groups": ["list", "blocks"]},
                    {"name": "document", "groups": ["mode"]},
                    {"name": "insert", "groups": ["insert"]},
                    {"name": "styles", "groups": ["styles"]},
                    {name: 'others'}
                ],
                removeButtons: 'Underline,Strike,Subscript,Superscript,Anchor,Styles,Specialchar,about,Image',
                disableNativeSpellChecker: false,
                scayt_autoStartup: false
            });
        }
    });
};
