(function () {
    "use strict";
    window.translate = function () {
        var args = arguments;
        if (args.length > 1) {
            translate.prototype.data[args[0]] = args[1];
        } else if (typeof args[0] === 'object') {
            translate.prototype.data = args[0];
        } else {
            var text = args[0];
            if (translate.prototype.data && translate.prototype.data[text]) {
                return translate.prototype.data[text];
            } else if (window.localStorage && localStorage.translate && localStorage.translate[text]) {
                return localStorage.translate[text];
            }
            return text;
        }
    };
})();