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
        "use strict";
        if (typeof WebSocket === 'undefined') {
            addMessages([{'level': 'danger', 'message': translate('The explorer does not support livechat. Please upgrade your explorer.')}]);
            return;
        }
        var sender = 0;
        var msg = null;
        var instance = null;
        var flag = false;
        var client = function (url) {
            this.url = url;
            this.retry = 0;
            this.partial = {};
            this.query = [];
            this.connect();
        };
        client.prototype = {
            check: function () {
                return this.socket && this.socket.readyState == 1;
            },
            ping: function (_this) {
                _this = _this ? _this : this;
                var buffer = new ArrayBuffer(2);
                var i8V = new Int8Array(buffer);
                i8V[0] = 0x09;
                i8V[1] = 0;
                if (_this.check()) {
                    _this.socket.send(buffer);
                }
                clearTimeout(_this.pingTimeout);
                _this.pingTimeout = setTimeout(_this.ping, 60000, _this);
            },
            connect: function (_this) {
                _this = _this ? _this : this;
                try {
                    _this.lock = false;
                    if (_this.check()) {
                        return;
                    }
                    $.ajax(GLOBAL.BASE_URL + GLOBAL.ADMIN_PATH + '/livechat/prepare/', {
                        type: flag ? 'head' : 'get',
                        context: _this,
                        success: function (xhr) {
                            xhr = typeof xhr === 'string' ? JSON.parse(xhr) : xhr;
                            if (xhr) {
                                for (var s in xhr) {
                                    for (var i in xhr[s]) {
                                        instance.log(xhr[s][i]);
                                    }
                                }
                            }
                            flag = true;
                            this.socket = new WebSocket(this.url);
                            this.socket.onopen = this.onopen;
                            this.socket.onmessage = this.onmessage;
                            this.socket.onclose = this.onclose;
                        },
                        error: function () {
                            _this.reconnect.call(_this);
                        }
                    });
                } catch (e) {
                    _this.reconnect.call(_this);
                }
            },
            reconnect: function (_this) {
                _this = _this ? _this : this;
                if (!_this.lock || _this.retry <= 5) {
                    _this.lock = true;
                    setTimeout(_this.connect, 2000 * Math.pow(2, _this.retry), _this);
                    _this.retry = _this.retry + 1;
                }
            },
            onopen: function () {
                instance.retry = 0;
                instance.send('{"sender":' + msg.sender + '}');
                if (instance.query.length) {
                    for (var i in instance.query) {
                        instance.send(instance.query[i]);
                    }
                    instance.query = [];
                }
            },
            onmessage: function (e) {
                var data = e.data;
                if (typeof data === 'string') {
                    data = JSON.parse(data);
                }
                if (data.partial !== undefined) {
                    if (instance.partial[data.session + '-' + data.sender] === undefined) {
                        instance.partial[data.session + '-' + data.sender] = {};
                    }
                    instance.partial[data.session + '-' + data.sender][data.partial] = data.msg;
                    if (!data.end) {
                        return;
                    }
                }
                if (data.end) {
                    data.msg = '';
                    var f = true;
                    var m = 1;
                    var p = instance.partial[data.session + '-' + data.sender];
                    while (f) {
                        var j = true;
                        var f = false;
                        for (var i in p) {
                            if (j || parseInt(i) < m) {
                                m = parseInt(i);
                                j = false;
                                f = true;
                            }
                        }
                        if (p[m]) {
                            data.msg += p[m];
                            delete p[m];
                        } else {
                            break;
                        }
                    }
                    instance.log(data);
                    delete instance.partial[data.session + '-' + data.sender];
                    delete msg.end;
                    delete msg.partial;
                } else if (data.new) {
                    $('#livechat').trigger('new.livechat', data.new);
                } else {
                    instance.log(data);
                }
            },
            onclose: function () {
                instance.reconnect.call(instance);
            },
            send: function (m) {
                if (this.check()) {
                    this.socket.send(m);
                    clearTimeout(this.pingTimeout);
                    this.pingTimeout = setTimeout(instance.ping, 60000, instance);
                } else {
                    this.query.push(m);
                }
            },
            log: function (data) {
                var c = data.sender == msg.sender ? 'self' : 'others';
                var t = data.type;
                var m = data.msg;
                if (t === 'image') {
                    m = '<img src="' + m + '" />';
                } else if (t === 'audio') {
                    m = '<audio controls="controls" src="' + m + '" />'
                } else if (t === 'video') {
                    m = '<video controls="controls" src="' + m + '" />';
                }
                if (t === 'text' || t === 'image' || t === 'audio' || t === 'link' || t === 'video') {
                    var l = document.createElement('li');
                    l.className = c;
                    $(l).append($('#avatar-' + (c === 'self' ? 0 : data.session)).clone().removeAttr('id')).append('<span class="msg">' + m.replace(/(?:\n)+/g, '<br />') + '</span>');
                    $('#livechat #' + data.session + ' .chat-list').append(l);
                    $('#livechat #' + data.session + ' .chat-history').scrollTop($('#livechat #' + data.session + ' .chat-list').height());
                }
                $('#livechat').trigger('notify', t === 'text' ? m : '', data.session);
                $('#livechat .nav li:not(.active) [href="#' + data.session + '"][data-badge]').attr('data-badge', function () {
                    return parseInt($(this).attr('data-badge')) + 1;
                });
                badge();
                return false;
            }
        };
        var badge = function () {
            var s = 0;
            $('#livechat .nav [data-badge]').each(function () {
                s += parseInt($(this).attr('data-badge'));
            });
            $('#livechat+.btn-livechat [data-badge]').attr('data-badge');
        };
        var notify = function (e, t, s) {
            if (Notification.permission === 'granted') {
                var n = new Notification(translate('You have received a new message.'), {
                    body: t
                });
                setTimeout(function () {
                    n.close();
                }, 3000);
            }
            $('#livechat .nav li.active [href="#' + s + '"][data-badge]').attr('data-badge', function () {
                return parseInt($(this).attr('data-badge')) + 1;
            });
            badge();
        };
        var init = function () {
            msg = {
                session: '',
                sender: sender,
                type: 'text',
                msg: ''
            }
            instance = new client($('#livechat #chat-form').attr('action'));
            if (Notification.permission !== 'granted') {
                Notification.requestPermission();
            }
        };
        $(window).on({
            focus: function () {
                $('#livechat').off('notify');
                $('#livechat .nav .active [data-toggle=tab]').attr('data-badge', 0);
                badge();
            },
            blur: function () {
                $('#livechat').on('notify', notify);
            }
        });
        var send = function (session) {
            var o = $('#livechat #' + session + ' [name=msg]');
            msg.msg = $(o).val().replace(/[<>'"&]/g, function (c) {
                return {'<': '&lt;', '>': '&gt;', "'": '&apos;', '"': '&quot;', '&': '&amp;'}[c];
            }).replace(/&amp;#x/g, '&#x');
            if (msg.msg === '') {
                return;
            }
            msg.session = session;
            if (/^(?:(?:(?:https?|ftp):)?\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})).?)(?::\d{2,5})?(?:[/?#]\S*)?$/i.test(msg.msg)) {
                msg.type = 'link';
                msg.msg = '<a href="' + msg.msg + '">' + msg.msg +
                        '</a><iframe src="' +
                        GLOBAL.BASE_URL + GLOBAL.ADMIN_PATH + '/livechat/preview/?url=' +
                        btoa(msg.msg).replace(/[\=\+\/]/g, function (c) {
                    return {'=': '', '+': '-', '/': '_'}[c];
                }) + '"></iframe>';
            } else {
                msg.type = 'text';
            }
            $(o).val('');
            if (instance && instance.check()) {
                instance.send(JSON.stringify(msg));
            } else {
                init();
                $(instance).one('opened', function () {
                    instance.send(JSON.stringify(msg));
                });
            }
        };
        var sendBin = function (session, type) {
            var o = $('#livechat #' + session + ' [type=file].send-' + type)[0];
            var f = o.files[0];
            var i = 0;
            var u = function () {
                var d = new FormData;
                var e = Math.min(f.size, i + 2097151);
                d.append('file', f.slice(i, e, f.type), f.name);
                $.ajax(GLOBAL.BASE_URL + GLOBAL.ADMIN_PATH + '/livechat/upload/', {
                    type: 'post',
                    contentType: false,
                    processData: false,
                    data: d,
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('Content-Range', 'bytes ' + i + '-' + Math.min(f.size, e) + '/' + f.size);
                    },
                    success: function (xhr) {
                        var r = typeof xhr === 'object' ? xhr.responseText : xhr;
                        if (r === '') {
                            i += 2097152;
                            u();
                        } else {
                            msg.session = session;
                            msg.type = type;
                            msg.msg = r;
                            if (instance && instance.check()) {
                                instance.send(JSON.stringify(msg));
                            } else {
                                init();
                                $(instance).one('opened', function () {
                                    this.send(JSON.stringify(msg));
                                });
                            }
                        }
                    }
                });
            };
            u();
        };
        var insert = function (t, s) {
            if (document.selection) {
                t.focus();
                var e = document.selection.createRange();
                e.text = s;
            } else if (t.selectionStart || t.selectionStart == '0') {
                var v = $(t).val();
                $(t).val(v.substring(0, t.selectionStart) + s + v.substring(t.selectionEnd, v.length));
                t.focus();
            } else {
                $(t).val($(t).val() + s);
                t.focus();
            }
        }
        init();
        $('#livechat').on('click', '[type=submit]', function () {
            var session = $(this).val();
            send(session);
            return false;
        }).on('keypress', '[name=msg]', function (e) {
            if (e.keyCode === 13) {
                var session = $(this).siblings('[type=submit]').val();
                send(session);
                return false;
            } else if (e.keyCode === 10) {
                insert(this, '\n');
                return false;
            }
        }).on('change', '.send-image', function () {
            var session = $(this).parents('.tab-pane').first().find('[name=session]').val();
            sendBin(session, 'image');
        }).on('change', '.send-audio', function () {
            var session = $(this).parents('.tab-pane').first().find('[name=session]').val();
            sendBin(session, 'audio');
        }).on('change', '.send-video', function () {
            var session = $(this).parents('.tab-pane').first().find('[name=session]').val();
            sendBin(session, 'video');
        }).on('afterajax.redseanet', '.close', function () {
            $($(this).siblings('a').attr('href')).remove();
            $(this).parent('li').remove();
        }).on('show.bs.tab', '.nav [data-toggle=tab]', function () {
            $(this).attr('data-badge', 0);
            badge();
            if ($('.btn-nav').is(':visible')) {
                $('#livechat-nav').collapse('hide');
            }
        }).on('click', '.chat-history li .msg img', function () {
            var oimg = document.createElement('img');
            $(oimg).attr({'src': $(this).attr('src'), 'class': 'loading'}).on('load', function () {
                var p = $(this).parents('.loading');
                if (this.naturalWidth) {
                    $(p).width(this.naturalWidth + 20).height(this.naturalHeight + 20);
                }
                $(p).removeClass('loading');
            });
            var m = $('<div class="modal fade modal-zoombox" tabindex="-1"><div class="modal-dialog loading"><div class="modal-content"><span class="fa fa-spinner fa-spin"></span></div></div></div>');
            $('.modal-content', m).prepend(oimg);
            $('body>.modal-zoombox').remove();
            $('body').append(m);
            $(m).modal('show');
        }).on('click', '.toolbar .emoji li', function () {
            insert($(this).parents('.toolbar').first().siblings('.input-box').find('[name=msg]'), $(this).text());
        }).on('load', '.chat-history img,.chat-history iframe', function () {
            if ($(this).is(':visible')) {
                var o = $(this).parents('.chat-history').first();
                $(o).scrollTop($(o).scrollTop() + $(this).height());
            }
        });
        $('#livechat+.btn-livechat').click(function () {
            $('#livechat').toggleClass('show');
        });
        $('#modal-history').on('show.bs.modal', function (e) {
            $('.modal-content', this).load(GLOBAL.BASE_URL + GLOBAL.ADMIN_PATH + '/livechat/history/?id=' + $(e.relatedTarget).data('id'));
        });
        if (/\?chat.+/.test(location.href)) {
            history.replaceState({}, '', location.href.replace(/\?chat.+/, ''));
        }
    }
    );
}));
