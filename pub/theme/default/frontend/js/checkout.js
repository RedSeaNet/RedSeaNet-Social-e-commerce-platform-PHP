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
        if (!GLOBAL.AJAX) {
            GLOBAL.AJAX = {};
        }
        var csrf = $('[name=csrf]').first().val();
        var beforeLoad = null;
        var load = function () {
            if (!$('.section.review .total img').length) {
                $('.section.review .btn.btn-checkout').hide();
                if (beforeLoad === null) {
                    beforeLoad = $('.section.review .total').html();
                }
                $('.section.review .total').html('<img src="data:image/gif;base64, R0lGODlhZAANAN0AAP////z+/Nzu9OTy/KzW7MTi9JTK5Oz2/PT6/Mzi9LTa7Gy23HS23NTq9Fyq3KTS7Mzm9GSy3Lze9Hy65Gyy3LTW7Ozy/KzS7HS65FSq3IzC5OTu/Dye1Pz6/KTO7JzK7Lza7CSOzDya1JzO7PT2/NTm9Eym1ITC5BSKzESe1FSm3CySzFyu3ESi1IzG5JTG5Nzq9ByKzAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH/C05FVFNDQVBFMi4wAwEAAAAh+QQJCQAAACwAAAAAEQANAAAGo0ABgbAJAAAHAURhmGgeh0DgQFiUAINBI/GpGDIxxuHYUWQGkItrwcE8QItQ4QggTQgCDEjiKBCeK1dHAQIMMCwLDikaDBUfEQh0SCcHGBcRFSMZBRQJRnQIHgkRDiITDhIFBpGSZQkeBgQGIwUXA5KDqgwLFC8NAre4AAEKCgICDRIPFp+SAQ0hIrcIFC3BuAEJKBwWdSom3cIACAQQRoRFkkEAIfkECQkAAAAsAAAAAB8ADQCl/////P785PL8xOL01Or07Pb8vN7stNrsrNbs9Pr8lMbkzOb0pNLsfLrkhL7k3O70lMrkxN709Pb8jMLkbLbcnM7sdLbcnMrszOL0jMbk5O78rNLsXK7cZK7cXKrc3Or0fL7kTKLUhMLktNbs7PL8bLLcpM7sPJrURJ7URKLUvN70JI7MvNrsVKbUFIbM1Ob0VKrcNJrUdLrkHI7MTKbUZLLcAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABv9AwcBACgAACQEBg7gwnofCEUAiDA6ihkIlBUgGigegINAQDhEEyDNRNASAh/KwQUxOK4gREBiACh8GGxkUFwYYCjALGQ0FByMOHiEOCConMRJTCRURAhAYCw4EEQwGHB8CFEMOYCADJgwWKZlTnhoiECIWDBBXEwkBGxUDIQ0pHhAdCzUKe0cSDAWGE1cNBBkERgMIAh2mBjIWCx1iU0gsBBMOJRUOCwQjewsRBzQeKA4cBCYGzkd9gRBsOzAKzhEBESJUqNDExDZa5gK8QABBwQQEGgQYPFJggAMKJQop6WKOD4YBGh8sMEDyCIkDBh48ODOipTkBHEpISZDBQssyAAxmdMj0oAWFBCX/EQhBQUqABo0iKnBhAekCFA2QJkUy4IORABn9xdkgwEgCAgX8BQEAIfkECQkAAAAsAAAAAC0ADQCl////7Pb89Pr8/P78zOb01Or03O70tNrsrNbs5PL8xOL0lMrkxN703Or09Pb8vN7snMrspM7snM7spNLsvNrsdLbcjMbkrNLs5O781Ob0lMbkvN70tNbshL7k/Pr8fL7kfLrkdLrkzOL0bLbcjMLkZLLcRJ7UXK7c7PL8HIrMhMLkNJrUXKrcbLLcTKLULJbURKLUZK7cPJrUTKbUVKbcLJLMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABv/AQKEQGAAAnkDCoHgwHhRF4AgIGAqiCGRymTAE1CsD0floCGCAoHFIqAMBDKFAmFgQCMg0YW1uJiMnEB8LAgIGchMIFx81LwxGAAMNCA4JBQoHEhQZBgckBhcQAgoMER0gEQwELSUGFXoRCB8uJyQHBykjaWobRBQGoZcKGQt8FnQRBAgSBQcPGiEOBCUYEhAMIxcKtyYdHlRVFAEIB8wMBw0EHAID6BkhEiEdHBYNd2ogyzAVK7AlBEwoCKdGgQAKBTjQQeApgZEMGwKoINCBwAINBUi48bCggAEWCEpQOLHgQIUp4TwQSIAHwgYEGAwQAAdApgIQHSpEUIHhgQK1IwMUXjAxg0WFECiQRaIyKUGGOQQIYCBCRUgGXA8OUCBg8MiAqA8WRJiwAIGTpeESiMDFQQQcB+EcFIiwwIKGA1Z41TwAYkSLEBwCiNDLtAERODLhhgugYOUSBV3DURIRLIMXtIs1SIA74EAEvQMe0LAANwEIDXoFfEihYcCADTIWYPaKQYUEMAK6gEbgohCABhVux42RYoEkBCsgEAy3xuEROJgTbEBh5FCRcF8PoDgiFyWVIAAh+QQJCQAAACwAAAAAOwANAKX////0+vzU6vTk8vzc7vTs9vz8/vzM5vTE4vSczuzc6vS83uzs8vz8+vy02uys1uz09vy83vSkzuyk0uzk7vzM4vS01uzU5vSEwuTE3vSUxuSUyuSMxuScyuy82uyEvuSMwuRsstx0uuR8vuR0ttxsttw0ltRUptQcisxMotQ8mtRkstys0uxcrtxUptxEntRcqtx8uuQkjsxkrtwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAG/8BAYRAAGA0ByJBAEBwURaOQQsgsrJ5DFMAgXCKShIQ1ORikhAN4hJEQGsZGAQpoBIQDxkCAuCAiRQUQA2l8CRoOCRYBDQMFAhEICx0tIRIlEQYFFBQVigkkKC8ERgCaAncDVAcEDHMIBZIBCpAPExkCBAkdA4gFCxEdIiATCAgnHAopGQNhIiolHQ8bKAlnca0BBAWPgwMDBxAQDlQRAxULFAcXDhLaHAwOFgcaCwITFSQJARMfBR8TFrTI4GDEAhMPrqEJIIDWASp6BBgw4KTZggQPKjwY4KACgAAJFCAQsWHFBg8gBIgQwKUEAQwwQpjAAAKDhxQMSh15Q2CAgv+IcwoYoSCgwAMCRxc4GGBBqAELPT8s0FDhwwN6RQJgYNAPAwcHJx5w6KAwzgAIApw0ETTgGiEFEx5IyPCAwQUCZwwgIBCBRIwRGzYYJVVHggAMJ16EmJFgwIcBOo8UCKBniKPJpQZ5OeDkrgA4RhoeeLDAg4UM664FiCDAgoYHCRKYlhjZyJKGFO5s+UhBqYOOAQaANjIAwSEOGxAw3EJxAokSIUYg2FNWJzcIdwow2P1RwTYIDNJyLy7gG18zOhtozKXgQAcF1UsFqKDFlHidFL8GmuBhd4MEJ7Aw0QEhWBDZAC6ggAASCZiAQG2lxHIAHBRdUFYDCIDgABwW7RYxAAguOFAHAiuIqBMBKshwwEcYmLAihKZspxp3AHRzBBGRGSASBAtFFoAVRRgQHndBAAAh+QQJCQAAACwNAAAAOwANAKX////0+vzs9vzk8vzc7vTU6vT8/vzM5vS02uzE4vS83vT8+vzc6vTs8vzk7vykzuyk0uys1uz09vzU5vSczuy82uxsttyUxuSMxuSUyuScyuzE3vR0ttys0uy01ux8vuRcqtzM4vRkrtyMwuRkstx8uuRUqtx0uuQ8mtRUptRsstyEwuRMptQkksw8ntRcrtxEotQUiswkjsxEntQciswsksyEvuQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAG/0BDoCFYAI6BgERAaBYOjsARIBkQChuFVkEwAgIDxgFBeUAgEYfhyBxnSiOIYP21GpMBwUDgOCQKCgcLBgJgBYcKFxkVGAdCDQMFCAkVFyklFBYTAAORIRoeGSY0HAJTBg4EhA1XBQ4CAgcHEggHAQeADxEJDAUjCAMlBw0ICBkcF7sRKQgeJgMHHRgWLicQFRYyCVNfBbAEVQR6DQwTYB6xIQ4IEwMJBBQbAQoUEg8KEx9PFAciCgEfIhDgUEEBiAQRMDyosYkbJAkFCOCKFKDAAAAGFIR5gEBDrQgDHlwUcGEABBsYSJh5cODEHAYcGLywAGLGCA4dSUjhBquiAP9vDMAVkGIgBKwIkghEOMAAgZQFcuhB6JCgRIIOCIwIWCHgRAcSHiiY2GAhBJ0peQRMKDCBAAMwpjAyiGQGa4UACRocMQDswQcLF1ZUCBk3wIMEIkCg+ABCQYIMO7kpEQAJ0p4iUzoR+CMropq9BzZ32FAsxDs6AWpByBCKAsKL3PbyuUJgwIIAXtgQUFCswlvbp3ZrwKDQm6pTCS5wsEDigufYUxb0nNwgMhtXsCIdP4WFACRJn6MjqNCEgQIIDc7Gdsdgza3tRxYoGLENAAENB2I3+JCiAIAFCIjg3ykMtICCA1+oAANs0HGywXEShKZeAB40Ihd+66kAAgNfQCAtAgHcLJBADCjoJYAJLOjVYB07CZHbXhQhUUhsC8iyEx/WfRHBBGsYwEB4RwQBACH5BAkJAAAALBsAAAA8AA0Apf///+z2/PT6/Pz+/OTy/Nzu9NTq9Mzm9LTa7MTi9Ozy/Pz6/KzW7PT2/Lze7NTm9MTe9Lze9JTG5Nzq9IzG5OTu/JzO7JTK5Mzi9JzK7KTO7KTS7IS+5Gyy3HS65KzS7DSW1Lza7IzC5ITC5HS23Gy23Hy+5GSu3LTW7FSm1FSq3Eym1Fyq3ESe1ByKzFyu3BSGzHy65CSOzEyi1Dye1AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAb/wEFAERgAjgLBsMB8HAhGgJBQOEQgjohDERUQDBhGZoNAbDCC46DwiGw4poxhoRYUoAPBokFYYhIGCAV5AQ0VBxOBIhYhFAUAAQQVgRAMHCkSCSQMAg13GBYoGx4yIAZRAAsTBAsECgUGBgUBAQkTARoFDQcHIRYIB2AUBwkcAQUOHxQlGQ4JFyoPBC8MChujLR0SCBowFHRHAAIGXn2rdgEHg20CEA8FDEwRBRfkFhAEFxgHHAYJHw5eFFgQokMACRkgsHDgYAQCEBZQSdGVrsqdCQ0eBBCHoEEEBgguQBhZQEOaChcqjJAwooSyBChEpAnQoUCGFSVAmJDgIQGN/wrhwhFIYkCBgQAG+gyUAmFchAS2Nvg7YESAhgDNRCRAYOIBhVOpRtQicSGGAxUaNHBIExSAAgEKghmYMMELWwEaP2ZAgOJAu7shDFDgcMIChwcGGERZkOvCjBYnOoggIOJRW3ENIhHYTKSImjtgDPCCxeXIggQFPiYAaQDCRjURAl34YMECSKqXxR2jO2uBAIlGQ5RxQIBP0AGJJUigwKDC5rYFPngo0cGDgwAPJAZVEiCJl9fhBnwpHumA5/AJMGyucr0tcgbv2Gg4AO7ymgRAIT1p22ADBaADHCBBfkKxcMJGAkhAAnhH0OTCBwMs8JBiuUnhDwFHxIVhUFhJgDbhaRdsqIYBKywojgkeMAgAASm4gIA4FoDwYoUANPBWHfV9NggSpW0HAVji9RjOaR01SE5bQQAAIfkECQkAAAAsKQAAADsADQCl////9Pr87Pb8/P783O705PL81Or0zOb0tNrs/Pr8xOL0rNbs7PL89Pb8lMrkzOL05O781Ob0xN70vN70jMbkpNLsrNLsvNrsvN7snM7s3Or0nMrslMbkdLbcfL7ktNbsbLbcpM7sfLrkZK7cZLLcXK7cRJ7UHIrMhL7kjMLkNJrUXKrcTKLUbLLcdLrkhMLkVKrcVKbURKLULJbUPJrUTKbULJLMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABv/AhKAQABgHgcCQQDBEDIKBUVggKDCKx9WQOFYPmMwmU5EUjU3JAuXhHM4DgSaaSDYKAob1cLgUAEoCEAcGBxYUCBEOE3UFDAYXChcOIx4iDkkEgxULFh42MxJSRgFQAQxfGo4FEgUGC3cGCggZF08IFAQMLxMCGBMOIBwLVywLLRkMnCIsJSkICCcgZ0YFEAMEeAYNAgQCBwwBGFAXTBauDwYcUQ8cARaJKHwhDyQI9RAbGwotFgrPJlB0MQIggYEATTQc0OQtQhEBFwQsQLAgw6xCH4oEoFAAgQgOJUIs2HAARDgPBxbI6KCiQwYSB0woIOglgR4I3gygKiAlgJn/C680PFhAAAHPghUEREDk4ICHCxhCDEjgwICGEhZIYFjhAEEHATQBDCDSZKGBAgkInElwoMCCkRMW4DwwcIDRDy46pKCQtMKfAR8MWDBRY0UHFwwoGBhFUwC3VXjCEcRWIAIhPhC2TY5AYOgEaBLSSRnAB1iICg4WTBAVllQVDQYINKgTFsIDaB8eCNgdtoACBxwokGxAYCCAoiJAgOiAQMADamFPJQlwB7rY2Lu7HWgQFpuEbAU0ICDA+PiCB0wilClP06ACsGMPgG3MYQN8BCGsN6AQY8KAAQqMMBNBDXhwAgVTTUCDA+wRFMABChSRwCDQDQDBCxloZEEF1gkgLwIMMyWAgIA0NTDCCQ6I9YEKGbQ2mWOjIMFeAKqMsht77p2BUIUHIMAAKRHMR1AQACH5BAkJAAAALDcAAAAtAA0Apf////T6/OTy/Nzu9Oz2/NTq9Pz+/Mzm9MTi9LTa7Nzq9Pz6/PT2/Ozy/OTu/Lze9JTK5KzW7NTm9JTG5Mzi9JzO7MTe9LTW7KTS7Lze7JzK7IzG5FSm3GSy3ITC5HS65IzC5KTO7Gyy3KzS7Lza7Hy65HS23IS+5GSu3AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAb/wABBEAAYDQHGcOAYDCSCxXEpeZASiUPR2Hg+QpWIYrsgjAGLgFDQEBQQhUGigCQwBAdFIQGBaCJpbQMPCBkaKB0HBlMFagJNBwMCZggMAREFDAUHGRUJCAoSHhYbCQGfEB8eGAgIHBNbaAMNAQMEBJkEDngBC3IBCAcOEU4WBRp5GwIJFwcTxhgUIhixALUBBXrCtm6LpwwHIwnPCA8DFZYVCggfEB0TCSADHwVG9gYDvgIKbQVmBEYWIKiVgIK5CAckDDRwQdKJDBMoeLiAAJa9gAI0bRrgb8giAAYOEKCAIUSETwwQADSAYJCJEiX6ECB2cUqANkMy0rq3awCFj015JH1UoOBAhAwPLlhI+LHmkmwO1FQDgCsDFhIDAkSxJwBBhQkbIAxsVNPeLUtCdtZsUGDSkAMCmgLo2nbfA0VljQSgoAVkAbIXT4Got0ABhHr2FlTgMMJASBEX8hohEEyKgQIS5AJggGGDApAHNAy4SAAEhwRoEHRALdkArY9qyhposkWrlHvrGOjNWjMIACH5BAkJAAAALEUAAAAfAA0ApP///+Ty/Oz2/PT6/Pz+/Nzu9NTq9Mzm9Pz6/Ozy/LTa7Nzq9Mzi9MTi9Lze9NTm9KzW7PT2/OTu/KTO7IzG5JTK5MTe9JzK7Lza7JzO7LTW7KTS7AAAAAAAAAAAAAAAAAX0ICIkAgEAxDCMxXIcBXICrGE5TWCiw6KnogCrYVEcCKJBwMB0VCqFGaAnQAQShYNPEGgUjIODwTGBNAwLikI2ExisQoNAUBAwAgFI5MD4ProLGRY7JwQGSg8FfRIFewgEDksTThgHEAETAVIoEisFAXUGeAUEBAxzEA8KBRB9CgObBAFWD1oGElawhgkGGRsQChgDDQmbJwIrEnhCAhEzowwPtQYFpMZTWAXUESqEEWMK4QtKbMYryCoBsFJuEnNL1teGxCgJp1IDDhQMJwUXB9dOIHjQQACKAAcMzhigYR8KA/8CopizA0EEQgfHnRiQYN2MEAAh+QQBCQAAACxSAAAAEgANAKP////s9vz0+vzk8vzc7vT8/vzs8vzU6vTM5vT09vzc6vT8+vzE4vTk7vzU5vTM4vQEcrAEEwq4QkxCRrJAIXSFuCTD9jDBdS3KsAwGcRxEEDAEiB2CQSo2CiAavhAhYSQgOooEIuFyDTIHwyFwSBEW1YtBYEAcHARFUBDGMIVCSqUN0KQVuYUgWdUEMkEtdEoMDRdGA4MhBwyJAGWOgwljFyZhEQAh+QQICQABACwAAAAAZAANAKD///8AAAACI4SPqcvtD6OctNqLs968+w+G4kiW5omm6sq27gvH8kzXdlcAADs=" alt="" />');
            }
        };
        var onError = function () {
            $('.section.review .total').html(beforeLoad);
        };
        var onComplete = function () {
            beforeLoad = null;
        };
        var loadShipping = function () {
            var store = [];
            $('.section.review input[name^=storeid]').each(function () {
                store.push($(this).val());
            });
            if (store.length) {
                load();
            } else {
                onError();
            }
            while (store.length) {
                var s = store.pop();
                var url = GLOBAL.BASE_URL + 'checkout/order/shipping/?store=';
                if (GLOBAL.AJAX[url + s]) {
                    GLOBAL.AJAX[url + s].readyState < 4 ? GLOBAL.AJAX[url + s] = null : GLOBAL.AJAX[url + s].abort();
                }
                GLOBAL.AJAX[url + s] = $.ajax(url + s, {
                    type: 'get',
                    success: function (xhr) {
                        GLOBAL.AJAX[url + xhr.store] = null;
                        var t = $('.section.review div#shipping_method_div_' + xhr.store);
                        $(t).html(xhr.html);
                        if (store.length == 0) {
                            $('.section.review [name^=shipping_method]').first().on('change').trigger('change');
                            $('.checkout-steps [data-load-after="shipping"]').trigger('afterload.redseanet');
                        }
                    }
                });
            }
        };
        var loadCoupon = function () {
            var store = [];
            $('.section.review .coupon[data-store]').each(function () {
                store.push($(this).data('store'));
            });
            if (store.length) {
                load();
            } else {
                onError();
            }
            while (store.length) {
                var s = store.pop();
                var url = GLOBAL.BASE_URL + 'checkout/order/coupon/?store=';
                if (GLOBAL.AJAX[url + s]) {
                    GLOBAL.AJAX[url + s].readyState < 4 ? GLOBAL.AJAX[url + s] = null : GLOBAL.AJAX[url + s].abort();
                }
                GLOBAL.AJAX[url + s] = $.ajax(url + s, {
                    type: 'get',
                    success: function (xhr) {
                        GLOBAL.AJAX[url + xhr.store] = null;
                        var t = $('.section.review .coupon[data-store=' + xhr.store + ']');
                        $(t).html(xhr.html);
                        var c = $(t).find(':checked');
                        if (c.length && $(c).val()) {
                            $('.label', t).text($(c).siblings('label').text());
                        }
                        if (!store.length) {
                            loadPayment();
                            loadReview();
                            $('.checkout-steps [data-load-after="coupon"]').trigger('afterload.redseanet');
                        }
                    }
                });
            }
        };
        var loadPayment = function () {
            var url = GLOBAL.BASE_URL + 'checkout/order/payment/';
            if (GLOBAL.AJAX[url]) {
                GLOBAL.AJAX[url].readyState < 4 ? GLOBAL.AJAX[url] = null : GLOBAL.AJAX[url].abort();
            }
            load();
            GLOBAL.AJAX[url] = $.ajax(url, {
                type: 'get',
                success: function (xhr) {
                    GLOBAL.AJAX[url] = null;
                    $('.section.payment').html($(xhr).html());
                    $('.checkout-steps [data-load-after="payment"]').trigger('afterload.redseanet');
                }
            });
        };
        var loadReview = function () {
            var url = GLOBAL.BASE_URL + 'checkout/order/review/';
            if (GLOBAL.AJAX[url]) {
                GLOBAL.AJAX[url].readyState < 4 ? GLOBAL.AJAX[url] = null : GLOBAL.AJAX[url].abort();
            }
            load();
            GLOBAL.AJAX[url] = $.ajax(url, {
                type: 'get',
                success: function (xhr) {
                    GLOBAL.AJAX[url] = null;
                    $('.section.review .total').html(xhr);
                    $('.section.review .btn.btn-checkout').show();
                    $('.checkout-steps [data-load-after="review"]').trigger('afterload.redseanet');
                    onComplete();
                }
            });
        };
        var loadStep = function (step) {
            eval('load' + step.substring(0, 1).toUpperCase() + step.substring(1).toLowerCase() + '();');
        };
        var creditCartTypes = {
            'SO': ['^(6334[5-9]([0-9]{11}|[0-9]{13,14}))|(6767([0-9]{12}|[0-9]{14,15}))$', '^([0-9]{3}|[0-9]{4})?$'],
            'VI': ['^4[0-9]{12}([0-9]{3})?$', '^[0-9]{3}$'],
            'MC': ['^5[1-5][0-9]{14}$', '^[0-9]{3}$'],
            'AE': ['^3[47][0-9]{13}$', '^[0-9]{4}$'],
            'DI': ['^(30[0-5][0-9]{13}|3095[0-9]{12}|35(2[8-9][0-9]{12}|[3-8][0-9]{13})|36[0-9]{12}|3[8-9][0-9]{14}|6011(0[0-9]{11}|[2-4][0-9]{11}|74[0-9]{10}|7[7-9][0-9]{10}|8[6-9][0-9]{10}|9[0-9]{11})|62(2(12[6-9][0-9]{10}|1[3-9][0-9]{11}|[2-8][0-9]{12}|9[0-1][0-9]{11}|92[0-5][0-9]{10})|[4-6][0-9]{13}|8[2-8][0-9]{12})|6(4[4-9][0-9]{13}|5[0-9]{14}))$', '^[0-9]{3}$'],
            'JCB': ['^(30[0-5][0-9]{13}|3095[0-9]{12}|35(2[8-9][0-9]{12}|[3-8][0-9]{13})|36[0-9]{12}|3[8-9][0-9]{14}|6011(0[0-9]{11}|[2-4][0-9]{11}|74[0-9]{10}|7[7-9][0-9]{10}|8[6-9][0-9]{10}|9[0-9]{11})|62(2(12[6-9][0-9]{10}|1[3-9][0-9]{11}|[2-8][0-9]{12}|9[0-1][0-9]{11}|92[0-5][0-9]{10})|[4-6][0-9]{13}|8[2-8][0-9]{12})|6(4[4-9][0-9]{13}|5[0-9]{14}))$', '^[0-9]{3,4}$'],
            'DICL': ['^(30[0-5][0-9]{13}|3095[0-9]{12}|35(2[8-9][0-9]{12}|[3-8][0-9]{13})|36[0-9]{12}|3[8-9][0-9]{14}|6011(0[0-9]{11}|[2-4][0-9]{11}|74[0-9]{10}|7[7-9][0-9]{10}|8[6-9][0-9]{10}|9[0-9]{11})|62(2(12[6-9][0-9]{10}|1[3-9][0-9]{11}|[2-8][0-9]{12}|9[0-1][0-9]{11}|92[0-5][0-9]{10})|[4-6][0-9]{13}|8[2-8][0-9]{12})|6(4[4-9][0-9]{13}|5[0-9]{14}))$', '^[0-9]{3}$'],
            'SM': ['(^(5[0678])[0-9]{11,18}$)|(^(6[^05])[0-9]{11,18}$)|(^(601)[^1][0-9]{9,16}$)|(^(6011)[0-9]{9,11}$)|(^(6011)[0-9]{13,16}$)|(^(65)[0-9]{11,13}$)|(^(65)[0-9]{15,18}$)|(^(49030)[2-9]([0-9]{10}$|[0-9]{12,13}$))|(^(49033)[5-9]([0-9]{10}$|[0-9]{12,13}$))|(^(49110)[1-2]([0-9]{10}$|[0-9]{12,13}$))|(^(49117)[4-9]([0-9]{10}$|[0-9]{12,13}$))|(^(49118)[0-2]([0-9]{10}$|[0-9]{12,13}$))|(^(4936)([0-9]{12}$|[0-9]{14,15}$))', '^([0-9]{3}|[0-9]{4})?$']
        };
        $('.cc-type').on('change', function () {
            var p = $(this).parents('form,.fieldset,fieldset').first();
            $(p).find('.cc-number').attr('pattern', creditCartTypes[$('#cc-type').val()][0]);
            $(p).find('.cc-verification').attr('pattern', creditCartTypes[$('#cc-type').val()][1]);
        });
        $('.section.address').on('click', '.edit', function () {
            var f = $('.section.address div#modal-address form');
            var json = $(this).parent('[data-id]').data('json');
            var l = 2;
            while (typeof json === 'string' && l--) {
                json = JSON.parse(json);
            }
            var t = $(f).find('select,input,textarea');
            $(t).each(function () {
                var v = json[$(this).attr('name')];
                if (v) {
                    if ($(this).is('[type=radio],[type=checkbox]')) {
                        if ($(this).val() === v) {
                            this.checked = true;
                        }
                    } else if ($(this).is('select')) {
                        $(this).attr('data-default', v).val(v).trigger('change');
                    } else {
                        $(this).val(v);
                    }
                }
            });
            console.log($('.section.address div#modal-address'));
            $('.section.address div#modal-address').modal('show');
        }).on('click', '[name$=address_id]', function () {
            var url = GLOBAL.BASE_URL + 'checkout/order/selectaddress/';
            if (GLOBAL.AJAX[url]) {
                GLOBAL.AJAX[url].readyState < 4 ? GLOBAL.AJAX[url] = null : GLOBAL.AJAX[url].abort();
            }
            GLOBAL.AJAX[url] = $.ajax(url, {
                type: 'post',
                data: $(this).serialize() + '&csrf=' + csrf,
                success: function () {
                    GLOBAL.AJAX[url] = null;
                    loadShipping();
                    $('.checkout-steps [data-change-after="address"]').trigger('afterchange.redseanet');
                }
            });
        });
        $('.section.address .btn-add').on('click', function () {
            $('.section.address div#modal-address form').trigger('reset');
        });
        $('.section.address .btn-cancel').on('click', function () {
            if ($('.section.address .list [name=shipping_address_id]').length) {
                $('.section.address div#modal-address').modal('hide');
            }
            $('.section.address div#modal-address form').trigger('reset');
        });
        $('.section.address div#modal-address form').on('submit', function () {
            if ($(this).valid()) {
                $('.section.address div#modal-address').modal('hide');
                loadShipping();
            }
        }).on('afterajax.redseanet', function (e, json) {
            console.log('afterajax.redseanet-----');
            var t = $('.section.address [data-id=' + json.data.id + ']');
            if (t.length) {
                $(t).attr('data-json', JSON.stringify(json.data.json))
                        .find('label').text(json.data.content);
            } else {
                var tmpl = $('#tmpl-address-list').html();
                $('.section.address .list').append($(tmpl.replace(/\{id\}/g, json.data.id).replace(/\{content\}/g, json.data.content)).attr('data-json', JSON.stringify(json.data.json)));
                $('.section.address .list [data-id=' + json.data.id + ']>[type=radio]').trigger('click');
            }
        });
        $('.section.review').on('change', '[name^=shipping_method]', function () {
            var url = GLOBAL.BASE_URL + 'checkout/order/selectshipping/';
            if (GLOBAL.AJAX[url]) {
                GLOBAL.AJAX[url].readyState < 4 ? GLOBAL.AJAX[url] = null : GLOBAL.AJAX[url].abort();
            }
            GLOBAL.AJAX[url] = $.ajax(url, {
                type: 'post',
                data: $('.section.review [name^=shipping_method]').serialize() + '&csrf=' + csrf,
                success: function () {
                    GLOBAL.AJAX[url] = null;
                    loadCoupon();
                    $('.checkout-steps [data-change-after="shipping"]').trigger('afterchange.redseanet');
                }
            });
        }).on('click', '.coupon [type=radio]', function () {
            var url = GLOBAL.BASE_URL + 'checkout/order/selectcoupon/';
            if (GLOBAL.AJAX[url]) {
                GLOBAL.AJAX[url].readyState < 4 ? GLOBAL.AJAX[url] = null : GLOBAL.AJAX[url].abort();
            }
            var v = $(this).val() ? $(this).siblings('label[for]').text() : '';
            $(this).parents('.coupon').find('.dropdown-toggle .label').text(function () {
                return v ? v : $(this).attr('title');
            });
            GLOBAL.AJAX[url] = $.ajax(url, {
                type: 'post',
                data: $('.section.review .coupon [type=radio]:checked').serialize() + '&csrf=' + csrf,
                success: function () {
                    GLOBAL.AJAX[url] = null;
                    loadPayment();
                    loadReview();
                    $('.checkout-steps [data-change-after="coupon"]').trigger('afterchange.redseanet');
                }
            });
        });
        $('.section.review .coupon [type=radio]:checked').each(function () {
            var v = $(this).val() ? $(this).siblings('label[for]').text() : '';
            $(this).parents('.coupon').find('.dropdown-toggle .label').text(function () {
                return v ? v : $(this).attr('title');
            });
        });
        $('.section.payment').on('click', '[name=payment_method]', function () {
            var url = GLOBAL.BASE_URL + 'checkout/order/selectpayment/';
            $(this).siblings('[data-toggle=tab]').tab('show');
            if (GLOBAL.AJAX[url]) {
                GLOBAL.AJAX[url].readyState < 4 ? GLOBAL.AJAX[url] = null : GLOBAL.AJAX[url].abort();
            }
            GLOBAL.AJAX[url] = $.ajax(url, {
                type: 'post',
                data: $('.section.payment').find('input:not([type=radio],[type=checkbox]),[type=radio]:checked,[type=checkbox]:checked,select,textarea').not(':disabled').serialize() + '&csrf=' + csrf,
                success: function () {
                    GLOBAL.AJAX[url] = null;
                    loadReview();
                    $('.checkout-steps [data-change-after="payment"]').trigger('afterchange.redseanet');
                }
            });
        }).on('hide.bs.tab', '[data-toggle=tab]', function () {
            $($(this).attr('href')).find('input,select,textarea').prop('disabled', true);
        }).on('show.bs.tab', '[data-toggle=tab]', function () {
            $($(this).attr('href')).find('input,select,textarea').prop('disabled', false);
        }).on('change', '[name="payment[data][cc]"]', function () {
            if ($(this).val()) {
                $(this).siblings('.credit-card.hidden').addClass('hidden');
            } else {
                $(this).siblings('.credit-card.hidden').removeClass('hidden');
            }
        });
        $('.section.payment .tab-pane:not(.active) input:not(:disabled),.section.payment .tab-pane:not(.active) select:not(:disabled),.section.payment .tab-pane:not(.active) textarea:not(:disabled)').prop('disabled', true);
        $('.section.payment .tab-pane.active :disabled').prop('disabled', false);
        $('.checkout-steps').on('click', '.section.review .btn-checkout', function () {
            var hasError = 0;
            var errorMessage = '';
            if ($('input:radio[name=payment_method]:checked').length > 0 && $('input:radio[name=payment_method]:checked').val() != '') {
                var pamentMethodValue = $('input:radio[name=payment_method]:checked').val();
                $('#payment-description-' + pamentMethodValue + ' input[type=number],#payment-description-' + pamentMethodValue + ' input[type=text],#payment-description-' + pamentMethodValue + ' input[type=tel],#payment-description-' + pamentMethodValue + ' select,#payment-description-' + pamentMethodValue + ' textarea').each(function () {
                    if ($(this).hasClass('required') && $(this).val() == '') {
                        hasError = 1
                        errorMessage = $(this).data('msg-required');
                        $(this).addClass('invalid');
                        $(this).focus();
                        return false;
                    } else {
                        $(this).removeClass('invalid');
                    }
                });
                if (hasError == 1) {
                    alert(errorMessage);
                    return false;
                }
                $('#payment-description-' + pamentMethodValue + ' [type=radio],#payment-description-' + pamentMethodValue + ' [type=checkbox]').each(function () {
                    if ($(this).hasClass('required') && $('input:radio[rel=' + $(this).attr('rel') + ']:checked').length == 0) {
                        hasError = 1
                        errorMessage = $(this).data('msg-required');
                        $(this).parent('label').addClass('invalid');
                        $(this).focus();
                        return false;
                    } else {
                        $(this).parent('label').removeClass('invalid');
                    }
                });
                if (hasError == 1) {
                    alert(errorMessage);
                    return false;
                }
            } else {
                $('html,body').animate({
                    scrollTop: $('input:radio[name=payment_method]').first().siblings('label').offset().top
                }, 1000);
                alert(translate('Please choose payment method') + '!');
                return false;
            }
            if (!($("input#is_virtual") && $("input#is_virtual").val() == '1')) {
                if ($("input[name=shipping_address_id]:checked").length == 0) {
                    $('html,body').animate({
                        scrollTop: $('.section.address .btn-add').offset().top
                    }, 1000);
                    alert(translate('Please add and choose shipping address') + '!');
                    $("input[name=shipping_address_id]").focus();
                    return false;
                }
            }

            var url = GLOBAL.BASE_URL + 'checkout/order/place/';
            var o = this;
            if (GLOBAL.AJAX[url]) {
                GLOBAL.AJAX[url].readyState < 4 ? GLOBAL.AJAX[url] = null : GLOBAL.AJAX[url].abort();
            }
            var fd = new FormData;
            $('.checkout-steps select,.checkout-steps textarea,.checkout-steps input:not([type=radio],[type=checkbox]),.checkout-steps [type=radio]:checked,.checkout-steps [type=checkbox]:checked').each(function () {
                fd.append($(this).attr('name'), $(this).val());
            });
            load();
            GLOBAL.AJAX[url] = new XMLHttpRequest();
            GLOBAL.AJAX[url].onreadystatechange = function (e) {
                if (GLOBAL.AJAX[url].readyState == 4) {
                    responseHandler.call(o, GLOBAL.AJAX[url].responseText);
                    GLOBAL.AJAX[url] = null;
                    $('.section.review .btn.btn-checkout').show();
                    onError();
                }
            };
            GLOBAL.AJAX[url].open('POST', url);
            GLOBAL.AJAX[url].setRequestHeader('Cache-Control', 'no-cache');
            GLOBAL.AJAX[url].setRequestHeader('X-REQUESTED-WITH', 'XMLHttpRequest');
            GLOBAL.AJAX[url].send(fd);
        });
        $('.checkout-steps').on('afterajax.redseanet', '[data-load]', function () {
            $(this).siblings('.clicked').removeClass('clicked');
            $(this).toggleClass('clicked');
            var load = $(this).data('load');
            if (typeof load === 'string') {
                load = load.indexOf(',') === -1 ? [load] : eval('(' + load + ')');
            }
            for (var i in load) {
                loadStep(load[i]);
            }
        });
        $('.checkout-steps').on('afterload.redseanet', '[data-load-after]', function () {
            var url = $(this).data('url');
            if (url) {
                var o = this;
                if (GLOBAL.AJAX[url]) {
                    GLOBAL.AJAX[url].readyState < 4 ? GLOBAL.AJAX[url] = null : GLOBAL.AJAX[url].abort();
                }
                GLOBAL.AJAX[url] = $.ajax(url, {
                    type: 'get',
                    success: function (xhr) {
                        GLOBAL.AJAX[url] = null;
                        $(o).html($(xhr).html());
                    }
                });
            }
        });
        $('.checkout-steps').on('afterchange.redseanet', '[data-change-after]', function () {
            var url = $(this).data('url');
            if (url) {
                var o = this;
                if (GLOBAL.AJAX[url]) {
                    GLOBAL.AJAX[url].readyState < 4 ? GLOBAL.AJAX[url] = null : GLOBAL.AJAX[url].abort();
                }
                GLOBAL.AJAX[url] = $.ajax(url, {
                    type: 'get',
                    success: function (xhr) {
                        GLOBAL.AJAX[url] = null;
                        $(o).html($(xhr).html());
                    }
                });
            }
        }).on('afterajax.redseanet', '.section.address .list .delete', function () {
            if (!$('.section.address .list>li').not($(this).parents('li').first()).length) {
                $('.section.address .form-edit-address').slideDown();
            }
        });
    });
}));
