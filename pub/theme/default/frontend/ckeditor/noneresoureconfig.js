CKEDITOR.editorConfig = function( config ) {
	config.extraPlugins ='noneresourceimage';
	config.allowedContent=true;
        config.ignoreEmptyParagraph = false;
        config.removePlugins = 'easyimage,cloudservices';
};
CKEDITOR.dtd.$removeEmpty.span = false;