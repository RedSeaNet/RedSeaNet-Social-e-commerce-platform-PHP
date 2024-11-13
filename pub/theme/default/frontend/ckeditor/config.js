/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

CKEDITOR.editorConfig = function( config ) {
	config.extraPlugins ='sysimage';
	config.allowedContent=true;
        config.ignoreEmptyParagraph = false;
        config.removePlugins = 'easyimage,cloudservices';
};
CKEDITOR.dtd.$removeEmpty.span = false;