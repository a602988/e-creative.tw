($.ui = {
    init: function () {
        var self = this;
        self.load();
        self.sound.init();
        self.helper.init();
        sewii.helper.ready(function () {
            $.fn.ready = sewii.helper.ready;
            self.onReady();
        });
    }, load: function () {
        var self = this;
        self.loader = {
            x4dd49682a2e45501: sewii.loader.include.library('4dd49682a2e45501'),
            x3929d2bc13ed28a1: sewii.loader.include.library('3929d2bc13ed28a1'),
            xc8633bedc25cf4b3: sewii.loader.include.library('c8633bedc25cf4b3'),
            xcf21b1118fc03a3f: sewii.loader.include.library('cf21b1118fc03a3f'),
            x531bfeaa38415f7e: sewii.loader.include.library('531bfeaa38415f7e'),
            xd3224c65496a99fc: sewii.loader.include.library('d3224c65496a99fc'),
            xb935591f2b7a43b8: sewii.loader.include.library('b935591f2b7a43b8'),
            x43563f9b64cce748: sewii.loader.include.library('43563f9b64cce748'),
            xaf85c8a24c4358b4: sewii.loader.include.library('af85c8a24c4358b4'),
            x42fc68082cbd6361: sewii.loader.include.library('42fc68082cbd6361'),
            x04e645a6cb5bd628: sewii.loader.include.library('04e645a6cb5bd628'),
            xa33c75f3b2b160e2: sewii.loader.include.library('a33c75f3b2b160e2'),
            x2540ecbd720b3994: sewii.loader.include.library('2540ecbd720b3994'),
            maps: self.pages.contact.map.load(),
            tracker: self.tracker.load(),
            facebook: self.facebook.load(),
        };
    }, lead: function () {
        var self = this, root = $('html');
        if (sewii.env.simulate.desktop) {
            sewii.env.device.mobile = false;
            root.addClass('simulate desktop');
            root.find('meta[name="viewport"]').attr('content', function (i, content) {
                content = sewii.stringify(content);
                content = content.replace(/initial-scale=[^,]+/i, 'initial-scale=0.4');
                content = content.replace(/user-scalable=[^,]+/i, 'user-scalable=yes');
                return content;
            });
        }
        $.each($.extend({}, sewii.env.os, sewii.env.device, sewii.env.browser), function (name, value) {
            value && root.addClass(name);
        });
        root.find('img').each(function () {
            var me = $(this);
            if (!me.hasClass('without') && !me.is('[data-src]')) {
                me.attr('data-src', me.attr('src'));
                me.removeAttr('src');
            }
            me.show();
        });
        $('a[href="#"]').click(false);
    }, onReady: function () {
        var self = this;
        self.lead();
        self.tracker.init();
        self.facebook.init();
        self.animator.init(function () {
            self.pages.init();
        });
    }, preloader: function (options) {
        var callee = arguments.callee, defaultProcess = 'default', historyLoaded = [], historyFailed = [];
        callee.instances = callee.instances || {};
        if (!sewii.isPlainObject(options)) {
            var process = sewii.stringify(options) || defaultProcess;
            return callee.instances[process];
        }
        options.process = options.process || defaultProcess;
        return (callee.instances[options.process] = ({
            state: [],
            loaded: [],
            failed: [],
            settings: {urls: [], onComplete: null, onLoading: null, workspace: null, gather: true, lazy: 0},
            init: function (options) {
                var self = this;
                $.extend(true, self.settings, options);
                $($.proxy(self.onReady, self));
                return self;
            },
            onReady: function () {
                var self = this;
                if (self.settings.gather) {
                    self.gatherImages(self.settings.workspace);
                }
                self.onStart();
            },
            onStart: function () {
                var self = this,
                    info = {all: self.settings.urls.length, loaded: 0, success: 0, error: 0, progressed: 0},
                    trigger = function (method, args, index) {
                        var eventObject = $.extend({}, args);
                        if (sewii.isSet(index)) {
                            eventObject.url = self.settings.urls[index];
                        }
                        method && method.call(self, eventObject);
                    };
                if (self.aborted) return;
                trigger(self.settings.onStart, info);
                if (!info.all) {
                    trigger(self.onComplete, info);
                } else {
                    $.each(self.settings.urls, function (index, url) {
                        if (self.aborted) return false;
                        if (historyLoaded[url] || historyFailed[url]) {
                            info.loaded++;
                            historyLoaded[url] && info.success++;
                            historyFailed[url] && info.error++;
                            trigger(self.onLoading, info, index);
                            return true;
                        }
                        var image = new Image(), unbind = function () {
                            image.onload = image.onerror = null;
                        };
                        image.onload = function () {
                            unbind();
                            info.loaded++;
                            info.success++;
                            self.loaded[url] = true;
                            historyLoaded[url] = true;
                            trigger(self.onLoading, info, index);
                        };
                        image.onerror = function () {
                            unbind();
                            info.loaded++;
                            info.error++;
                            self.failed[url] = true;
                            historyFailed[url] = true;
                            trigger(self.onLoading, info, index);
                        };
                        image.src = url;
                    });
                }
            },
            onLoading: function (event) {
                var self = this;
                if (self.aborted) return;
                event.progressed = Math.round((event.loaded / event.all) * 100);

                function loading() {
                    if (self.aborted) return;
                    if (self.settings.onLoading) {
                        self.settings.onLoading.call(self, event);
                    }
                    if (event.loaded === event.all) {
                        self.onComplete.call(self, event);
                    }
                }

                if (!self.settings.lazy) loading(); else setTimeout(loading, event.loaded * self.settings.lazy);
            },
            onComplete: function (event) {
                var self = this;
                if (self.aborted) return;
                if (self.settings.onComplete) {
                    delete event.url;
                    event.progressed = 100;
                    self.settings.onComplete.call(self, event);
                }
            },
            abort: function () {
                var self = this;
                self.aborted = true;
            },
            gatherImages: function (workspace) {
                var self = this;

                function addUrl(url) {
                    var key = url;
                    if (self.state[key]) return;
                    var url = self.buildUrl(url);
                    self.settings.urls.push(url);
                    self.state[key] = true;
                }

                $(workspace || document).find('*:not(script, link)').each(function () {
                    var element = $(this), background = element.css('backgroundImage'), matches;
                    if (background && background.indexOf('url') !== -1) {
                        if ((matches = background.match(/^url\("?([^"]*)"?\)/))) {
                            addUrl(matches[1]);
                        }
                    }
                    if (element.get(0).tagName.toLowerCase() === 'img') {
                        if ((matches = element.attr('data-src'))) {
                            element.attr('src', matches);
                            addUrl(matches);
                        } else if ((matches = element.attr('src'))) {
                            addUrl(matches);
                        }
                    }
                });
            },
            parseCss: function () {
            },
            buildUrl: function (url, workspace) {
                var self = this, baseUrl = self.baseUrl();
                if (baseUrl && url && !url.match(/^https?:\/\/.+$/i) && !url.match(/^\//)) {
                    url = baseUrl.replace(/\/*$/, '/' + url);
                }
                return url;
            },
            baseUrl: function () {
                var self = this;
                if (arguments.callee.cache) return arguments.callee.cache;
                return (arguments.callee.cache = $('base[href]').attr('href'));
            },
            isLoaded: function (uri) {
                var self = this, url = self.buildUrl(uri);
                return !!self.loaded[url];
            }
        }).init(options));
    }, animator: {
        init: function (callback) {
            var self = this;
            $.ui.loader.x4dd49682a2e45501.success(function () {
                self.tweener.init();
                callback && callback();
            });
        }, tweener: {
            init: function () {
                var self = this;
                self.natives = TweenMax;
                self.overriding();
            }, overriding: function () {
                var self = this;
                $.each(self.natives, function (name, member) {
                    if ($.isFunction(member) && name.match(/^[a-z]+/i)) {
                        self[name] || (self[name] = function () {
                            var args = Array.prototype.slice.call(arguments), map = [];
                            if (args.length === 1 && $.isPlainObject(args[0])) {
                                $.each(args[0], function (key, val) {
                                    map.push(val);
                                });
                                args = map;
                            }
                            var returned = self.natives[name].apply(self.natives, args);
                            return (returned === self.natives) ? self : returned;
                        });
                    }
                });
            }
        }, timeline: function (options) {
            var self = this;
            self.natives = new TimelineMax($.extend({}, options));
            $.ui.animator.tweener.overriding.call(self);
        }, easing: function (easing) {
            var self = this;
            if ($.easing && $.easing[easing]) {
                return easing;
            }
            return 'linear';
        }, requestAnimationFrame: function (callback) {
            var self = this, requestMethod = function (callback) {
                return window.setTimeout(callback, 1000 / 60);
            };
            if (window.requestAnimationFrame) {
                requestMethod = window.requestAnimationFrame;
            } else if (window.webkitRequestAnimationFrame) {
                requestMethod = window.webkitRequestAnimationFrame;
            } else if (window.mozRequestAnimationFrame) {
                requestMethod = window.mozRequestAnimationFrame;
            } else if (window.window.oRequestAnimationFrame) {
                requestMethod = window.window.oRequestAnimationFrame;
            } else if (window.msRequestAnimationFrame) {
                requestMethod = window.msRequestAnimationFrame;
            }
            return requestMethod(callback);
        }, cancelAnimationFrame: function (handle) {
            var self = this, cancelMethod = function (timer) {
                return window.clearTimeout(timer);
            };
            if (window.cancelAnimationFrame) {
                cancelMethod = window.cancelAnimationFrame;
            } else if (window.webkitCancelAnimationFrame) {
                cancelMethod = window.webkitCancelAnimationFrame;
            } else if (window.webkitCancelRequestAnimationFrame) {
                cancelMethod = window.webkitCancelRequestAnimationFrame;
            } else if (window.mozCancelRequestAnimationFrame) {
                cancelMethod = window.mozCancelRequestAnimationFrame;
            } else if (window.oCancelRequestAnimationFrame) {
                cancelMethod = window.oCancelRequestAnimationFrame;
            } else if (window.msCancelRequestAnimationFrame) {
                cancelMethod = window.msCancelRequestAnimationFrame;
            }
            return cancelMethod(handle);
        },
    }, sound: {
        list: {
            'effect-01': {volume: .2},
            'effect-02': {volume: .2},
            'effect-03': {volume: .2},
            'effect-04': {volume: .2},
            'effect-05': {volume: .2},
            'effect-06': {volume: .2},
            'effect-07': {volume: .1},
            'effect-08': {volume: .2},
            'effect-09': {volume: .2},
            'effect-10': {volume: .2},
            'effect-11': {volume: .2},
            'page-flip-01': {volume: 1},
            'page-flip-02': {volume: 1},
            'page-flip-03': {volume: 1},
            'page-flip-04': {volume: 1},
        }, init: function () {
            var self = this;
            self.audio = {};
            self.preload();
        }, preload: function () {
            var self = this;
            self.isSupport() && $(window).load(function () {
                sewii.each(self.list, function (name) {
                });
            });
        }, load: function (name, callback) {
            var self = this;
            if (self.isSupport() && !self.audio[name]) {
                var path = 'styles/sounds', format = sewii.env.browser.msie ? 'mp3' : 'wav',
                    uri = path + '/' + name + '.' + format, info = self.list[name] || {};
                self.audio[name] = new Audio();
                self.audio[name].src = uri;
                self.audio[name].volume = info.volume || 1;
                self.audio[name].oncanplay = function () {
                    self.audio[name].oncanplay = null;
                    self.audio[name].canPlay = true;
                    callback && callback();
                };
                self.audio[name].load();
            }
        }, play: function (name) {
            var self = this;
            if (!self.isSupport()) return;
            if (!self.audio[name]) {
                return self.load(name, function () {
                    self.play(name);
                });
            }
            if (self.audio[name].canPlay) {
                self.audio[name].currentTime = 0;
                self.audio[name].play();
            }
        }, isSupport: function () {
            return !!window.Audio;
        }
    }, helper: {
        init: function () {
            var self = this;
            $.fn.htmlOuter = function () {
                return $('<div/>').html($(this).clone()).html();
            };
            $.fn.visible = function () {
                return $(this).css('visibility', 'visible');
            };
            $.fn.invisible = function () {
                return $(this).css('visibility', 'hidden');
            };
            $.fn.opacity = function (value) {
                return $(this).css('opacity', value);
            };
            $.fn.transparent = function () {
                return $(this).opacity(0);
            };
            $.fn.opaque = function () {
                return $(this).opacity(1);
            };
            $.fn.disableSelection = function () {
                return this.attr('unselectable', 'on').css('user-select', 'none').on('selectstart', false);
            };
            $.fn.removeCss = function (property) {
                return this.css(property, '');
            };
            $.fn.importantCss = function (property, value) {
                var content = this.attr('style') || '';
                content.replace(new RegExp(property + ':[^;]+;', 'g'), '');
                content += ' ' + property + ': ' + value + ' !important;';
                return this.css('cssText', content);
            };
            $.fn.fit = function () {
                return this.each(function () {
                    var image = $(this), originalWidth = image.data('original-width'),
                        originalHeight = image.data('original-height'),
                        imageWidth = originalWidth || (this.naturalWidth || image.width()),
                        imageHeight = originalHeight || (this.naturalHeight || image.height()),
                        wrapper = image.parent(), clientWidth = wrapper.width(), clientHeight = wrapper.height(),
                        zoomScale = clientWidth / imageWidth, fittedWidth = imageWidth * zoomScale,
                        fittedHeight = imageHeight * zoomScale, offsetSize = 2, marginLeft = 0, marginTop = 0;
                    if (fittedHeight < clientHeight) {
                        zoomScale = clientHeight / imageHeight;
                        fittedWidth = imageWidth * zoomScale;
                        fittedHeight = imageHeight * zoomScale;
                    }
                    fittedWidth = Math.round(fittedWidth) + offsetSize;
                    fittedHeight = Math.round(fittedHeight) + offsetSize;
                    marginLeft = -Math.round(fittedWidth / 2), marginTop = -Math.round(fittedHeight / 2);
                    $(image).css({
                        display: 'block',
                        position: 'absolute',
                        left: '50%',
                        top: '50%',
                        width: fittedWidth,
                        height: fittedHeight,
                        marginLeft: marginLeft,
                        marginTop: marginTop,
                    });
                    originalWidth || image.data('original-width', imageWidth);
                    originalHeight || image.data('original-height', imageHeight);
                });
            };
            $.loadImage = function (url, options) {
                if (sewii.isPlainObject(url)) {
                    options = sewii.extend({}, url);
                    url = options.url;
                }
                var settings = {}, config = $.extend({}, settings, options),
                    isSupportProgress = $.ui.helper.support.xhr2() && $.ui.helper.support.blob(), image = new Image();
                if (isSupportProgress && config.progress || config.forceXhr) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', url, true);
                    xhr.responseType = 'arraybuffer';
                    xhr.onload = function (event) {
                        xhr.onload = xhr.onprogress = null;
                        var status = event.target.status;
                        if (status !== 200) {
                            return xhr.onerror(event);
                        }
                        xhr.onerror = null;
                        if (config.createBlob !== false) {
                            var blob = new Blob([this.response]);
                            image.src = window.URL.createObjectURL(blob);
                        }
                        config.load && config.load.call(image, event);
                    };
                    xhr.onerror = function (event) {
                        xhr.onload = xhr.onerror = xhr.onprogress = null;
                        config.error && config.error.call(image, event);
                    };
                    if (config.progress) {
                        xhr.onprogress = function (event) {
                            event.progressed = parseInt(event.loaded / event.total * 100);
                            config.progress && config.progress.call(image, event);
                        };
                    }
                    xhr.send();
                    return xhr;
                } else {
                    var aborted = false, abort = function () {
                        aborted = true;
                        unbind();
                    }, unbind = function () {
                        image.onload = image.onerror = null;
                    }, onPorgress = function (now) {
                        config.progress && config.progress.call(image, {progressed: now,});
                    };
                    image.onload = function (event) {
                        unbind();
                        if (aborted) return;
                        image.loaded = true;
                        onPorgress(100);
                        config.load && config.load.call(image, event);
                    };
                    image.onerror = function (event) {
                        unbind();
                        if (aborted) return;
                        config.error && config.error.call(image, event);
                    };
                    onPorgress(0);
                    image.abort = abort;
                    image.src = url;
                    return image;
                }
            };
            $.fn.spriteImage = function (options) {
                var settings = {cols: 10, rows: 10, name: 'sprite-tile', resize: true,},
                    config = $.extend({}, settings, options);
                config.rows = config.rows || 1;
                config.cols = config.cols || 1;

                function Plugin(image, index) {
                    var plugin = this, wrapper = image.parent(), wrapperWidth = wrapper.width(),
                        imageUri = image.attr('src'), imageWidth = image.width(), imageHeight = image.height(),
                        imageRatio = imageWidth / imageHeight, imagePercent = imageWidth / wrapperWidth,
                        tileWidth = Math.round(imageWidth / config.cols),
                        tileHeight = Math.round(imageHeight / config.rows),
                        deficitX = imageWidth - Math.round(config.cols * tileWidth),
                        deficitY = imageHeight - Math.round(config.rows * tileHeight),
                        id = config.name + '-' + parseInt(Math.random() * 100000) + '-' + (index || 0),
                        container = $('<div/>', {
                            'class': config.name,
                            id: id,
                            css: {
                                display: 'inline-block',
                                position: 'relative',
                                width: imageWidth,
                                height: imageHeight,
                            }
                        });
                    (function initialize() {
                        create();
                        if (config.resize) {
                            $(window).bind('resize', function () {
                                plugin.resize();
                            });
                        }
                    }());

                    function create() {
                        var tiles = '';
                        for (var row = 0; row < config.rows; row++) {
                            for (var col = 0; col < config.cols; col++) {
                                var tile = info(row, col),
                                    tileCss = 'position: absolute; overflow: hidden; left: ' + tile.left + 'px; top: ' + tile.top + 'px; width: ' + tile.width + 'px; height: ' + tile.height + 'px',
                                    innerCss = 'position: absolute; overflow: hidden; left: 0px; top: 0px; width: ' + tile.width + 'px; height: ' + tile.height + 'px',
                                    imageCss = 'position: absolute; display: block; left: ' + (-tile.left) + 'px; top: ' + (-tile.top) + 'px; width: ' + imageWidth + 'px; height: ' + imageHeight + 'px';
                                tiles += '<div id="' + tile.id + '" style="' + tileCss + '">';
                                tiles += '<div class="inner" style="' + innerCss + '">';
                                tiles += '<img src="' + imageUri + '" style="' + imageCss + '" />';
                                tiles += '</div>';
                                tiles += '</div>';
                            }
                        }
                        image.replaceWith(container.html(tiles));
                    }

                    this.container = container;
                    this.resize = function () {
                        imageWidth = wrapper.width() * imagePercent;
                        imageHeight = imageWidth / imageRatio;
                        tileWidth = Math.round(imageWidth / config.cols);
                        tileHeight = Math.round(imageHeight / config.rows);
                        deficitX = imageWidth - Math.round(config.cols * tileWidth);
                        deficitY = imageHeight - Math.round(config.rows * tileHeight);
                        container.css({width: imageWidth, height: imageHeight});
                        var callee = arguments.callee, $tiles = callee.tiles || {}, $inners = callee.inners || {},
                            $images = callee.images || {};
                        for (var row = 0; row < config.rows; row++) {
                            for (var col = 0; col < config.cols; col++) {
                                var tile = info(row, col),
                                    $tile = $tiles[tile.id] || ($tiles[tile.id] = container.find('#' + tile.id)),
                                    $inner = $inners[tile.id] || ($inners[tile.id] = $tile.find('.inner')),
                                    $image = $images[tile.id] || ($images[tile.id] = $tile.find('img'));
                                $tile.css({width: tile.width, height: tile.height, left: tile.left, top: tile.top});
                                $inner.css({width: tile.width, height: tile.height,});
                                $image.css({width: imageWidth, height: imageHeight, left: -tile.left, top: -tile.top});
                            }
                        }
                    };

                    function info(row, col) {
                        var info = {
                            id: id + '-' + row + '-' + col,
                            width: tileWidth,
                            height: tileHeight,
                            left: Math.round(col * tileWidth),
                            top: Math.round(row * tileHeight)
                        };
                        info.width += (col === config.cols - 1) ? deficitX : 0;
                        info.height += (row === config.rows - 1) ? deficitY : 0;
                        return info;
                    }
                }

                return this.each(function (index) {
                    var name = 'plugin_spriteImage';
                    if (!$.data(this, name)) {
                        var self = $(this), instance = new Plugin(self, index);
                        $.data(this, name, instance);
                    }
                });
            };
            $.overlay = (function () {
                var defaults = {zIndex: 10000000, opacity: .5, background: '#000000',}, fadeSpeed = 300,
                    className = 'sw-overlay', selector = '.' + className, overlay = $('<div/>');
                overlay.css({
                    position: 'fixed',
                    left: '0',
                    top: '0',
                    width: '100%',
                    height: '100%',
                    display: 'none',
                    cursor: 'wait',
                    zIndex: defaults.zIndex,
                    background: defaults.background,
                    opacity: defaults.opacity,
                });
                $(window).bind('mousedown mouseup keydown keypress', function (event) {
                    if ($(selector).length) {
                        event.preventDefault();
                        return false;
                    }
                });
                return {
                    show: function (options) {
                        var settings = $.extend({}, defaults, options);
                        overlay.clone().addClass(className).appendTo('body').css({
                            zIndex: settings.zIndex,
                            background: settings.background,
                            opacity: settings.opacity,
                        }).fadeIn(fadeSpeed);
                    }, hide: function () {
                        $(selector).fadeOut(fadeSpeed, function () {
                            $(this).remove();
                        });
                    },
                };
            }());
            $.fn.resized = function (callback, options) {
                var that = this;
                if ($.isPlainObject(callback)) {
                    options = $.extend({}, callback);
                    callback = options.callback;
                }
                if ($.isFunction(callback)) {
                    var timer, delay, settings = {now: false, delay: 60}, config = $.extend({}, settings, options);
                    $(that).resize(function () {
                        if (sewii.env.browser.oldIe) {
                            var currentHeight = $(window).height();
                            if (currentHeight === arguments.callee.lastResizeHeight) return;
                            arguments.callee.lastResizeHeight = currentHeight;
                        }
                        delay = $.isFunction(config.delay) ? config.delay() : config.delay;
                        delay = (delay === true) ? settings.delay : delay;
                        delay = (delay === false) ? 0 : settings.delay;
                        delay = parseInt(delay) || 0;
                        if (delay > 0) {
                            clearTimeout(timer);
                            timer = setTimeout(function () {
                                callback.call(that);
                            }, delay);
                        } else callback.call(that);
                    });
                    if (config.now) {
                        callback.call(that);
                    }
                }
                return that;
            };
            $(window).bind('keydown', function (e) {
                if (e.altKey || e.ctrlKey || e.metaKey || $(e.target).is(":input")) {
                    return;
                }
                var key = e.keyCode,
                    info = {isLeft: key === 37, isUp: key === 38, isRight: key === 39, isDown: key === 40};
                if (info.isRight || info.isUp || info.isLeft || info.isDown) {
                    info && $(window).trigger('directionKeyDown', [info]);
                    info.isRight && $(window).trigger('rightKeyDown', [info]);
                    info.isUp && $(window).trigger('upKeyDown', [info]);
                    info.isLeft && $(window).trigger('leftKeyDown', [info]);
                    info.isDown && $(window).trigger('downKeyDown', [info]);
                    return;
                }
            });
        }, support: {
            xhr2: function () {
                return window.ProgressEvent && window.FormData;
            }, blob: function () {
                return window.Blob && window.URL && window.URL.createObjectURL;
            }, transition: function () {
                var style = document.documentElement.style;
                return style.transition !== undefined || style.MsTransition !== undefined || style.webkitTransition !== undefined || style.MozTransition !== undefined || style.OTransition !== undefined;
            }, hightPerformance: function () {
                var callee = arguments.callee;
                if (sewii.ifSet(callee.result)) {
                    return callee.result;
                }
                if (sewii.env.os.windows) {
                    var matches = sewii.env.userAgentOiginal.match(/windows nt[ \/]([\w.]+)/),
                        version = matches ? parseFloat(matches[1]) : -1;
                    if (version < 6.0) return (callee.result = false);
                } else if (sewii.env.os.android) {
                    var matches = sewii.env.userAgentOiginal.match(/android[ \/]([\w.]+)/),
                        version = matches ? parseFloat(matches[1]) : -1;
                    if (version < 4.0) return (callee.result = false);
                } else if (sewii.env.device.iphone) {
                    if (window.screen.height < 568) return (callee.result = false);
                } else if (sewii.env.device.ipad) {
                    if (window.screen.width < 2048) return (callee.result = false);
                }
                return (callee.result = sewii.env.browser.msie >= 9 || sewii.env.browser.edge >= 12 || sewii.env.browser.chrome >= 7 || sewii.env.browser.safari >= 5 || sewii.env.browser.firefox >= 4 || sewii.env.browser.opera >= 15);
            }
        }, error: {
            init: function () {
                var self = this;
                if (!self.inited) {
                    self.inited = true;
                    self.notify = $('<div class="sewii-plugin-notify" />');
                    self.notify.appendTo('body').hide().click(function () {
                        window.location.reload();
                    });
                }
            }, show: function (message, timeout) {
                var self = this, spped = 800, easing = $.ui.animator.easing('easeOutExpo');
                self.init();
                timeout = sewii.ifSet(timeout, 5000);
                self.notify.stop().slideUp().text(message).slideDown(spped, easing, function () {
                    $(this).delay(timeout).slideUp();
                });
            }, connection: function (timeout) {
                var self = this;
                self.show('網路發生問題，不明的錯誤原因。', timeout);
            }, loading: function (timeout) {
                var self = this;
                self.show('網路發生問題，載入頁面失敗。', timeout);
            },
        },
    }, tracker: {
        init: function () {
            var self = this, id = $('#google-analytics').val();
            self.wait(function () {
                ga('create', id, 'auto');
                ga('require', 'displayfeatures');
            });
        }, load: function (callback) {
            var self = this, api = location.protocol + '//www.google-analytics.com/analytics.js';
            return sewii.loader.include.path(api).success(callback);
        }, wait: function (callback) {
            var self = this, loaders = [$.ui.loader.tracker];
            sewii.loader.response(loaders).success(function () {
                callback && callback();
            });
        }, sendPageView: function () {
            var self = this;
            self.wait(function () {
                setTimeout(function () {
                    var url = sewii.url.query.modify().toUrl(), uri = sewii.url.getRequestUri(),
                        title = sewii.history.title();
                    ga('set', 'location', url);
                    ga('send', 'pageview', uri, title);
                });
            });
        }, sendEvent: function (category, action, label, value) {
            var self = this;
            self.wait(function () {
                ga('send', 'event', category, action, label || null, value || 1);
            });
        }, sendSocial: function (network, action, target) {
            var self = this;
            self.wait(function () {
                ga('send', 'social', network, action, target);
            });
        }, sendTiming: function () {
            var self = this;
            self.wait(function () {
                ga('send', 'timing');
            });
        },
    }, facebook: {
        EVENT_INITED: 'facebook.inited', init: function () {
            var self = this, id = $('#facebook').val(), root = $('<div id="fb-root"/>').appendTo('body');
            self.wait(function () {
                FB.init({appId: id, version: 'v2.0', xfbml: false});
                self.onInited.called = true;
                sewii.event.fire(self.EVENT_INITED);
            });
        }, load: function (callback) {
            var self = this, api = location.protocol + '//connect.facebook.net/zh_TW/sdk.js';
            return sewii.loader.include.path(api).success(callback);
        }, wait: function (callback) {
            var self = this, loaders = [$.ui.loader.facebook];
            sewii.loader.response(loaders).success(function () {
                callback && callback();
            });
        }, onInited: function (callback) {
            var self = this, callee = arguments.callee;
            if (callee.called) {
                return callback && callback();
            }
            sewii.event.off(self.EVENT_INITED).once(self.EVENT_INITED, callback);
        }
    }, pages: {
        init: function () {
            var self = this;
            self.common.init(self);
            sewii.event.once(self.common.preloader.EVENT_OPENING_DONE, function () {
                sewii.router.routing.fire();
            });
            sewii.each(self, function (id, page) {
                page.route && page.route();
            });
        }, getUnit: function () {
            var self = this;
            return sewii.url.param('unit') || 'default';
        }, isUnit: function (unit) {
            var self = this;
            unit = sewii.stringify(unit).split(/\s*,\s*/);
            return $.inArray(self.getUnit(), unit) !== -1;
        }, getId: function (unit) {
            var self = this;
            unit = unit || self.getUnit();
            if (self[unit] && self[unit].id) {
                return self[unit].id;
            }
            return 'home';
        }, getNamespace: function () {
            var self = this, id = $('#namespace').val();
            return id;
        }, common: {
            init: function (parent) {
                var self = this, that = $('#layout');
                self.parent = parent;
                self.that = that;
                self.patch();
                self.awwwards();
                self.navigator.init(self);
                self.preloader.init(self);
                self.header.init(self);
                self.footer.init(self);
            }, patch: function () {
                var self = this;
                $('.bg2img').each(function () {
                    var element = $(this), background = element.css('backgroundImage'), matches;
                    if (background && background.indexOf('url') !== -1) {
                        if (matches = background.match(/^url\("?([^"]*)"?\)/)) {
                            element.css('backgroundImage', 'none');
                            element.attr('src', matches[1]);
                            if (element.attr('data-src')) {
                                element.attr('data-src', matches[1]);
                            }
                        }
                    }
                });
                (function scrolling() {
                    var targets = ['#layout', '#layout .scrollable.touchable',];
                    $.each(targets, function (index, selector) {
                        $('html').find(selector).each(function () {
                            var scrollable = this;
                            scrollable.addEventListener('touchstart', function (event) {
                                this.allowUp = (this.scrollTop > 0);
                                this.allowDown = (this.scrollTop < this.scrollHeight - this.clientHeight);
                                this.slideBeginY = event.touches[0].pageY;
                            });
                            scrollable.addEventListener('touchmove', function (event) {
                                var up = (event.touches[0].pageY > this.slideBeginY);
                                var down = (event.touches[0].pageY < this.slideBeginY);
                                this.slideBeginY = event.touches[0].pageY;
                                if ((up && this.allowUp) || (down && this.allowDown)) {
                                    event.stopPropagation();
                                } else {
                                    event.preventDefault();
                                }
                            });
                        });
                    });
                }());
            }, awwwards: function () {
                var self = this;
                $(window).resized({
                    callback: function () {
                        var clientWidth = $(window).width(), me = self.that.find('.awwwards');
                        me[clientWidth <= 850 ? 'hide' : 'show']();
                    }, delay: false, now: true,
                });
            }, navigator: {
                init: function (parent) {
                    var self = this, main = $('#main'), pages = main.find('> .page[id]'),
                        items = $('#' + parent.header.id + ', #' + parent.footer.id).find('.navigation .items > li > a[data-id]');
                    self.parent = parent;
                    self.main = main;
                    self.pages = pages;
                    self.items = items;
                    self.bind();
                    self.prepare();
                }, bind: function () {
                    var self = this;
                    $.ui.loader.x3929d2bc13ed28a1.success(function () {
                        self.items.each(function () {
                            var text = new SplitText(this, {type: 'chars'}),
                                underline = $('<div class="underline" />').appendTo(this);
                            $(this).attr('href', function (i, href) {
                                return $(this).data('href', href) && null;
                            }).bind({
                                click: function () {
                                    var uri = $(this).data('href');
                                    sewii.router.routing.go(uri);
                                    return false;
                                }, mouseenter: function () {
                                    var me = $(this);
                                    $.ui.animator.tweener.staggerFromTo({
                                        target: text.chars,
                                        duration: .7,
                                        from: (function () {
                                            return sewii.env.support.$3d ? {
                                                rotationX: -360,
                                                rotationZ: -360,
                                            } : {rotation: -360,};
                                        }()),
                                        to: (function () {
                                            return sewii.env.support.$3d ? {
                                                ease: Back.easeOut,
                                                rotationX: 0,
                                                rotationZ: 0,
                                            } : {ease: Back.easeOut, rotation: 0,};
                                        }()),
                                        stagger: .05
                                    });
                                    $.ui.animator.tweener.fromTo({
                                        target: underline,
                                        duration: .3,
                                        from: {opacity: 1, width: '0%'},
                                        to: {delay: .2, width: '100%'},
                                    });
                                    $.ui.sound.play('effect-03');
                                }, mouseleave: function () {
                                    var me = $(me);
                                    $.ui.animator.tweener.killTweensOf(underline);
                                    $.ui.animator.tweener.to({target: underline, duration: .15, params: {opacity: 0},});
                                }
                            });
                        });
                    });
                }, prepare: function () {
                    var self = this;
                    $(self.pages.get().reverse()).each(function (i) {
                        var page = $(this), id = page.attr('id'),
                            index = self.items.filter('[data-id="' + id + '"]').parent().index();
                        if (index !== -1) {
                            page.css({
                                zIndex: Math.abs(index - self.pages.length) + 1,
                                transform: sewii.env.support.$3d ? 'translate3d(0px, 0px, 0px)' : 'translated(0px, 0px)'
                            });
                        }
                    });
                }, getUri: function (id) {
                    var self = this, item = self.items.filter('[data-id="' + id + '"]');
                    if (item.length) {
                        var uri = item.data('href');
                        return uri;
                    }
                    return null;
                }, select: function (unit) {
                    var self = this, item = self.items.filter('[data-id="' + unit + '"]');
                    self.items.removeClass('actived');
                    item.addClass('actived');
                }, go: function (to, params) {
                    params = params || {};
                    var self = this, callee = arguments.callee, event = params.event || {},
                        lazy = event.isExternal ? 100 : 0;
                    clearTimeout(callee.timer);
                    callee.timer = setTimeout(function () {
                        var duration = 0.6, delay = 0.05, defaultly = self.defaultly(),
                            target = self.targetly(to) || (to = defaultly.attr('id')) && defaultly,
                            current = self.currently() || target, from = current.attr('id'),
                            isLeft = self.index(to) > self.index(from), isRight = !isLeft, slideType = 'x',
                            toReset = {x: 0, left: 0}, toAside = {x: '100000%', left: '100000%'}, toReadied = {},
                            toHidden = {}, toVisible = {}, zIndex = (self.zIndex = sewii.ifSet(self.zIndex, 100) + 1),
                            pageWidth = self.pages.width();
                        if (target.length) {
                            var settings = sewii.extend({
                                enterTiming: 1,
                                exeuntTiming: 1,
                                soundTiming: .5,
                                soundEffect: 'effect-10',
                                cancelable: true
                            }, params);
                            settings.event = event;
                            settings.event.to = $.ui.pages[to];
                            settings.event.from = $.ui.pages[from];
                            var fireEvent = (function (token) {
                                return function (name, parameters) {
                                    parameters = parameters || {};
                                    var events = {
                                        isInited: function (context) {
                                            return !!context.init.called;
                                        }, callback: function (context, name, event) {
                                            sewii.callback(context, name, event);
                                        }, preload: function () {
                                            var context = sewii.ifSet(parameters.context, settings.event.to),
                                                event = sewii.ifSet(parameters.event, settings.event),
                                                listener = 'onPreload', callback = function () {
                                                    if (token === callee.token) {
                                                        sewii.callback(parameters, 'callback');
                                                    }
                                                };
                                            if (!(listener in context)) {
                                                return callback();
                                            }
                                            event.callback = callback;
                                            this.callback(context, listener, event);
                                        }, come: function () {
                                            var context = sewii.ifSet(parameters.context, settings.event.to),
                                                event = sewii.ifSet(parameters.event, settings.event);
                                            this.isInited(context) && this.callback(context, 'onCome', event);
                                        }, enter: function () {
                                            var context = sewii.ifSet(parameters.context, settings.event.to),
                                                event = sewii.ifSet(parameters.event, settings.event);
                                            $.ui.animator.tweener.set(self.pages.not(target), toAside);
                                            this.isInited(context) && this.callback(context, 'onEnter', event);
                                        }, leave: function () {
                                            var context = sewii.ifSet(parameters.context, settings.event.from),
                                                event = sewii.ifSet(parameters.event, settings.event);
                                            this.isInited(context) && this.callback(context, 'onLeave', event);
                                        }, exeunt: function () {
                                            var context = sewii.ifSet(parameters.context, settings.event.from),
                                                event = sewii.ifSet(parameters.event, settings.event);
                                            this.isInited(context) && this.callback(context, 'onExeunt', event);
                                        }, sound: function () {
                                            $.ui.sound.play(settings.soundEffect);
                                        },
                                    };
                                    if (token === callee.token) {
                                        sewii.callback(events, name);
                                    }
                                };
                            }(callee.token = Math.random()));
                            self.pages.removeClass('actived');
                            target.addClass('actived');
                            self.select(to);
                            (function () {
                                callee.last = callee.last || {};
                                if (callee.last['in']) {
                                    var lastAnimationIsActive = callee.last['in'].isActive(),
                                        lastAnimationIsNotFireExeunt = callee.last['in'].progress() < callee.last.settings.exeuntTiming,
                                        lastPageIsNotSameDest = callee.last.settings.event.from.id !== to,
                                        isNotCancable = lastPageIsNotSameDest || !settings.cancelable;
                                    if (lastAnimationIsActive && lastAnimationIsNotFireExeunt && isNotCancable) {
                                        fireEvent('exeunt', {
                                            context: $.ui.pages[callee.last.settings.event.from.id],
                                            event: callee.last.settings.event
                                        });
                                    }
                                }
                                callee.last.settings = settings;
                            }());
                            if (to === from) {
                                return fireEvent('preload', {
                                    callback: function () {
                                        fireEvent('come');
                                        fireEvent('enter');
                                    }
                                });
                            }
                            fireEvent('leave');
                            fireEvent('preload', {
                                callback: function () {
                                    fireEvent('come');
                                    toReadied.zIndex = zIndex;
                                    toReadied[slideType] = isRight ? -pageWidth : pageWidth;
                                    toHidden[slideType] = isLeft ? -pageWidth : pageWidth;
                                    toVisible[slideType] = 0;
                                    $.ui.animator.tweener.set([target, current], toReset);
                                    callee.last['out'] = $.ui.animator.tweener.to({
                                        target: current,
                                        duration: duration,
                                        params: {css: toHidden, ease: Expo.easeInOut, delay: delay, overwrite: true,}
                                    });
                                    callee.last['in'] = $.ui.animator.tweener.fromTo({
                                        target: target, duration: duration, from: {css: toReadied}, params: {
                                            css: toVisible,
                                            ease: Expo.easeInOut,
                                            delay: delay,
                                            overwrite: true,
                                            onUpdate: function () {
                                                var progress = this.progress();
                                                if (!arguments.callee.sound && progress >= settings.soundTiming) {
                                                    arguments.callee.sound = true;
                                                    fireEvent('sound');
                                                }
                                                if (!arguments.callee.enter && progress >= settings.enterTiming) {
                                                    arguments.callee.enter = true;
                                                    fireEvent('enter');
                                                }
                                                if (!arguments.callee.exeunt && progress >= settings.exeuntTiming) {
                                                    arguments.callee.exeunt = true;
                                                    fireEvent('exeunt');
                                                }
                                            }
                                        }
                                    });
                                }
                            });
                        }
                    }, lazy);
                }, index: function (id) {
                    var self = this;
                    return self.items.filter('[data-id="' + id + '"]').first().parent().index();
                }, defaultly: function () {
                    var self = this, page = self.pages.first();
                    return page;
                }, currently: function () {
                    var self = this, actived = self.pages.filter('.actived'),
                        page = actived.length ? actived.first() : null;
                    return page;
                }, targetly: function (id) {
                    var self = this, target = self.pages.filter('#' + id), page = target.length ? target.first() : null;
                    return page;
                },
            }, preloader: {
                id: 'loading',
                EVENT_OPENING_DONE: 'common.preloader.opening.done',
                EVENT_LOADING_DONE: 'common.preloader.loading.done',
                init: function (parent) {
                    var self = this, that = $('#' + self.id), structure = that.find('.structure'),
                        inner = structure.children('.inner'), wrap = inner.children('.wrap'),
                        initial = wrap.children('.initial'), context = wrap.children('.context'),
                        progressed = context.find('.progressed'), filler = context.find('.filler'),
                        logo = context.find('.logo'), blank = context.find('.blank');
                    self.parent = parent;
                    self.that = that.addClass('masked');
                    self.structure = structure;
                    self.inner = inner;
                    self.wrap = wrap;
                    self.initial = initial;
                    self.context = context;
                    self.progressed = progressed;
                    self.filler = filler;
                    self.logo = logo;
                    self.blank = blank;
                    self.build();
                    self.accelerate();
                    self.resize();
                    self.stars.init(self);
                    self.opening();
                },
                build: function () {
                    var self = this;
                    self.instances = self.instances || {};
                    self.memory = new sewii.memory(-1, self.id);
                    self.reset();
                },
                resize: function (progressed) {
                    var self = this, callee = arguments.callee, handler = function (progressed) {
                        var wrapWidth = self.wrap.width(), maxLength = 4, charRatio = 1.68,
                            fontSize = wrapWidth * charRatio / maxLength,
                            text = (progressed = progressed || self.progressed).text();
                        self.filler.css('fontSize', fontSize);
                        progressed.css('fontSize', fontSize);
                        if (text.length >= maxLength) {
                            progressed.addClass('done');
                        } else {
                            progressed.removeClass('done');
                        }
                    };
                    if (!callee.bound) {
                        callee.bound = true;
                        self.that.addClass('masked');
                        $(window).resized({callback: handler, delay: self.isHidden(), now: true,});
                        return;
                    }
                    handler(progressed);
                },
                setLoaded: function (id) {
                    var self = this;
                    self.memory.set(id, true);
                },
                hasLoaded: function (id) {
                    var self = this;
                    return self.memory.get(id) === true;
                },
                isOpening: function () {
                    var self = this;
                    return parseInt(self.load.times) === 1;
                },
                isBusy: function () {
                    var self = this;
                    return self.that.is('.busy');
                },
                isShowing: function () {
                    var self = this;
                    return self.that.is('.showing');
                },
                isShowed: function () {
                    var self = this;
                    return self.that.is('.showed');
                },
                isShow: function () {
                    var self = this;
                    return self.isShowing() || self.isShowed();
                },
                isHiding: function () {
                    var self = this;
                    return self.that.is('.hiding');
                },
                isHidden: function () {
                    var self = this;
                    return self.that.is('.hidden');
                },
                isHide: function () {
                    var self = this;
                    return self.isHiding() || self.isHidden();
                },
                isDisabled: function (that) {
                    var self = this;
                    return (that || self.that).is(':hidden');
                },
                opening: function () {
                    var self = this, callee = arguments.callee, currentId = self.parent.parent.getId(),
                        hasLoaded = self.hasLoaded(currentId), onDone = function () {
                            self.that.addClass('started');
                            sewii.event.fire(self.EVENT_OPENING_DONE);
                        };
                    self.instances[self.id] = $.ui.preloader({
                        process: self.id, workspace: self.that, onStart: function () {
                            if (!self.isDisabled()) {
                                self.decelerate();
                                self.stars.play();
                            }
                        }, onComplete: function () {
                            if (self.isDisabled()) {
                                return onDone();
                            }
                            callee.timeline = new $.ui.animator.timeline();
                            callee.timeline.to({
                                target: self.initial,
                                duration: .4,
                                params: {alpha: 0, display: 'none'}
                            }).fromTo({
                                target: self.context,
                                duration: 1,
                                from: {alpha: 0},
                                to: {alpha: 1, display: 'block',}
                            });
                            if (hasLoaded) {
                                self.filler.hide();
                                self.progressed.hide();
                                callee.timeline.addCallback({
                                    callback: function () {
                                        sewii.event.fire(self.EVENT_OPENING_DONE);
                                    }
                                });
                            } else {
                                callee.timeline.fromTo({
                                    target: self.logo,
                                    duration: .6,
                                    from: {top: '-=' + $(window).height() * .06 * 1.1,},
                                    to: {ease: Cubic.easeOut, top: '+=' + $(window).height() * .06 * 1.1}
                                }).fromTo({
                                    target: self.progressed,
                                    duration: .6,
                                    from: {scaleY: 0, alpha: 0, transformOrigin: '0 100%'},
                                    to: {
                                        ease: Expo.easeInOut, scaleY: 1, alpha: 1, onUpdate: function () {
                                            if (!arguments.callee.done && this.progress() >= 1) {
                                                arguments.callee.done = true;
                                                $.ui.animator.tweener.set(this.target, {
                                                    transformOrigin: '50% 50%',
                                                    onComplete: function () {
                                                        onDone();
                                                    }
                                                });
                                            }
                                        }
                                    },
                                    position: '-=.25'
                                });
                            }
                            $.ui.tracker.sendEvent('Loading', 'Opening', $.ui.pages.getUnit());
                        }
                    });
                },
                accelerate: function () {
                    var self = this, unit = $.ui.pages.getUnit(), page = $('#' + unit);
                    if (!self.isDisabled()) {
                        if (self.that.length) {
                            self.instances[self.id] = $.ui.preloader({process: self.id, workspace: self.that});
                        }
                        if (page.length) {
                            self.instances[unit] = $.ui.preloader({process: unit, workspace: page});
                        }
                    }
                },
                decelerate: function () {
                    var self = this;
                    if (!self.isDisabled()) {
                        sewii.each(self.instances, function (id, instance) {
                        });
                    }
                },
                load: function (options) {
                    var self = this, callee = arguments.callee, token = Math.random(), hello = false, eventObject = {},
                        settings = {}, defaults = {}, onLoaded = function () {
                            self.stars.pause(function () {
                                self.that.removeClass('busy');
                                settings.callback && settings.callback.call(self, eventObject);
                            });
                        };
                    callee.token = token;
                    callee.times = (callee.times || 0) + 1;
                    callee.preloaded = callee.preloaded || {};
                    self.settings = settings = $.extend(defaults, options);
                    self.routing = settings.routing;
                    settings.lazy = self.isOpening() ? 25 : 20;
                    settings.lazy = self.isSupportUpdate() ? settings.lazy : 8;
                    self.cancel();
                    self.that.addClass('busy');
                    var urls = self.getImages(settings.id);

                    function toPreload(callback) {
                        if (!callee.preloaded[settings.id]) {
                            return (self.instances.actuality = $.ui.preloader({
                                lazy: 0,
                                process: settings.id,
                                urls: urls,
                                gather: false,
                                onComplete: function () {
                                    callee.preloaded[settings.id] = true;
                                    callback && callback();
                                }
                            }));
                        }
                        callback && callback();
                    }

                    if (self.hasLoaded(settings.id) || self.isDisabled()) {
                        return toPreload(onLoaded);
                    }
                    $.ajax(sewii.url.query.modify('unit=hello&hash=' + token).toPath()).done(function (data) {
                        /^200$/.test(data) && (hello = true);
                    });
                    self.reset();
                    self.stars.play();
                    self.show(function () {
                        if (callee.token !== token) {
                            return;
                        }
                        if (self.isSupportUpdate() && urls.length <= 90) {
                            toPreload();
                        }
                        setTimeout(function () {
                            urls = self.getImages(settings.id, true);
                            self.instances.effect = $.ui.preloader({
                                lazy: settings.lazy,
                                process: settings.id,
                                urls: urls,
                                gather: false,
                                onStart: function () {
                                    sewii.event.off(self.EVENT_LOADING_DONE).once(self.EVENT_LOADING_DONE, function () {
                                        if (!hello) {
                                            return $.ui.helper.error.loading();
                                        }
                                        self.setLoaded(settings.id);
                                        onLoaded();
                                    });
                                },
                                onLoading: function (event) {
                                    self.update(event.progressed);
                                },
                                onComplete: function () {
                                    callee.preloaded[settings.id] = true;
                                    if (!this.settings.urls.length) {
                                        sewii.event.fire(self.EVENT_LOADING_DONE);
                                    }
                                }
                            });
                        });
                    });
                    $.ui.tracker.sendEvent('Loading', 'Transition', $.ui.pages.getUnit());
                },
                slide: function (action, callback) {
                    var self = this, callee = arguments.callee, duration = 0.6, delay = 0.05, ease = Expo.easeInOut,
                        toSlide = {}, toReadied = {}, toReset = {x: 0, left: 0},
                        toAside = {x: '100000%', left: '100000%'}, slideType = 'x', routing = self.routing,
                        to = routing.to.id, from = routing.from.id,
                        isLeft = self.parent.navigator.index(to) > self.parent.navigator.index(from), isRight = !isLeft,
                        isHide = action === false, isShow = action === true, slideSize = $(window).width(),
                        setState = function (state) {
                            $.each(['showing', 'showed', 'hiding', 'hidden'], function (i, className) {
                                self.that.removeClass(className);
                            });
                            self.that.addClass(state);
                        }, onComplete = function () {
                            if (isHide) {
                                self.isOpening() && self.that.addClass('inside');
                                $.ui.animator.tweener.set(self.that, toAside);
                            }
                            isShow && setState('showed');
                            isHide && setState('hidden');
                            callee.callback && callee.callback();
                        };
                    callee.callback = callback;
                    if (self.isOpening() && isShow) {
                        return onComplete();
                    }
                    if (isShow) {
                        toReadied[slideType] = isLeft ? -slideSize : slideSize;
                        toSlide[slideType] = 0;
                        if (self.that.is('.showed')) {
                            return onComplete();
                        }
                        if (self.that.is('.showing')) {
                            return;
                        }
                        if (self.that.is('.hiding')) {
                            delete toReadied[slideType];
                        }
                    }
                    if (isHide) {
                        toReadied[slideType] = 0;
                        toSlide[slideType] = isRight ? -slideSize : slideSize;
                        if (self.that.is('.hidden')) {
                            return onComplete();
                        }
                        if (self.that.is('.hiding')) {
                            return;
                        }
                        if (self.that.is('.showing')) {
                            delete toReadied[slideType];
                        }
                    }
                    if (!self.that.is('.showing') && !self.that.is('.hiding')) {
                        $.ui.animator.tweener.set(self.that, toReset);
                    }
                    callee.timeline = $.ui.animator.tweener.fromTo({
                        target: self.that, duration: duration, from: {css: toReadied}, params: {
                            css: toSlide,
                            ease: ease,
                            delay: delay,
                            overwrite: true,
                            onComplete: onComplete,
                            onStart: function () {
                                isShow && setState('showing');
                                isHide && setState('hiding');
                                (function () {
                                    var className = 'opaque';
                                    if (!self.isSupportUpdate()) {
                                        className += ' non3d';
                                    }
                                    if (sewii.env.browser.webkit && self.that.is('.inside')) {
                                        if (self.routing.from.id === 'team' && self.routing.from.person) {
                                            if (self.routing.from.person.isOpened() || self.routing.from.person.isOpening()) {
                                                return self.that.addClass(className);
                                            }
                                        }
                                        if (self.routing.from.id === 'works' && self.routing.from.detail) {
                                            if (self.routing.from.detail.isOpened() || self.routing.from.detail.isOpening()) {
                                                return self.that.addClass(className);
                                            }
                                        }
                                    }
                                    self.that.removeClass(className);
                                }());
                            }
                        }
                    });
                },
                show: function (callback) {
                    var self = this;
                    if (self.isDisabled()) return callback && callback();
                    self.slide(true, function () {
                        callback && callback();
                    });
                },
                hide: function (callback) {
                    var self = this;
                    if (self.isDisabled()) return callback && callback();
                    self.slide(false, function () {
                        callback && callback();
                    });
                },
                cancel: function () {
                    var self = this;
                    if (self.isBusy()) {
                        return window.location.reload();
                        if (self.hasLoaded(self.settings.id) && self.isShow()) {
                            self.hide();
                        }
                        $.each(self.instances, function (i, preloader) {
                            preloader.abort();
                        });
                        $.each(self.update.timelines || {}, function (i, timeline) {
                            timeline.progress(1);
                        });
                        self.context.children('.progressed').slice(1).remove();
                    }
                    return true;
                },
                update: function (now, reset) {
                    var self = this, callee = arguments.callee, token = Math.random(), percentage = '<i>%</i>',
                        current = now || 0, progressed = current + percentage, easeIn = Expo.easeOut,
                        easeOut = Expo.easeIn, speedIn = .6, speedOut = .6, isDone = current >= 100,
                        onDone = function () {
                            sewii.event.fire(self.EVENT_LOADING_DONE);
                        };
                    if (reset) {
                        delete callee.progressed;
                        return self.progressed.html(progressed).removeClass('done');
                    }
                    callee.progressed = callee.progressed || {};
                    if (callee.progressed[progressed]) return; else callee.progressed[progressed] = true;
                    if (!self.isSupportUpdate()) {
                        self.progressed.opaque().html(progressed);
                        isDone && (self.resize() || onDone());
                        return;
                    }
                    callee.cloned = self.progressed.clone().opaque().removeClass('done');
                    callee.future = callee.cloned.html(progressed).prependTo(self.context);
                    callee.past = self.progressed;
                    self.progressed = callee.future;
                    if (isDone) {
                        easeIn = Expo.easeOut;
                        self.resize();
                    }
                    callee.timelines = callee.timelines || {};
                    callee.timelines[token] = new $.ui.animator.timeline();
                    if (self.isOpening() && false) {
                        callee.timelines[token].fromTo({
                            target: callee.future,
                            duration: speedIn,
                            from: {
                                transformOrigin: '50% 50%',
                                transformPerspective: 400,
                                scale: .1,
                                opacity: .1,
                                x: 0,
                                y: -self.that.height(),
                                z: -500,
                            },
                            to: {
                                ease: easeIn, scale: 1, opacity: .3, y: 0, z: 0, onStart: function () {
                                    if (isDone) {
                                        $.ui.animator.tweener.set(callee.future, {opacity: 1});
                                    }
                                }
                            }
                        }).fromTo({
                            target: callee.past,
                            duration: speedOut,
                            from: {scale: 1, opacity: .15, x: 0, y: 0, z: 0,},
                            to: {
                                ease: easeOut,
                                scale: 10,
                                opacity: 0,
                                y: self.that.height() * .3,
                                y: self.that.height() * 1.3,
                                z: 1000,
                                onUpdate: function () {
                                    if (isDone && !arguments.callee.done && this.progress() >= 1) {
                                        arguments.callee.done = true;
                                        onDone();
                                    }
                                },
                                onComplete: function () {
                                    isDone && self.clearProgressed();
                                    delete callee.timelines[token];
                                }
                            },
                            position: '-=.37'
                        });
                    } else {
                        callee.timelines[token].fromTo({
                            target: callee.future,
                            duration: speedIn,
                            from: {
                                transformOrigin: '50% 50%',
                                transformPerspective: 300,
                                scale: .1,
                                opacity: .1,
                                x: -(self.that.width() * .55),
                                y: 0,
                                z: -400,
                            },
                            to: {
                                ease: easeIn, scale: 1, opacity: .15, x: 0, z: 0, onStart: function () {
                                    if (isDone) {
                                        $.ui.animator.tweener.set(callee.future, {opacity: 1});
                                    }
                                },
                            }
                        }).fromTo({
                            target: callee.past,
                            duration: speedOut,
                            from: {scale: 1, opacity: .15, x: 0, y: 0, z: 0,},
                            to: {
                                ease: easeOut,
                                scale: .1,
                                opacity: 0,
                                x: (self.that.width() * .55),
                                z: -400,
                                onUpdate: function () {
                                    if (isDone && !arguments.callee.done && this.progress() >= 1) {
                                        arguments.callee.done = true;
                                        onDone();
                                    }
                                },
                                onComplete: function () {
                                    isDone && self.clearProgressed();
                                    delete callee.timelines[token];
                                }
                            },
                            position: '-=.45'
                        });
                    }
                },
                isSupportUpdate: function () {
                    var self = this;
                    return $.ui.helper.support.hightPerformance() && sewii.env.support.$3d && !sewii.env.device.mobile && self.isOpening();
                },
                reset: function () {
                    var self = this;
                    self.filler.show();
                    self.progressed.show();
                    self.progressed.opaque();
                    self.clearProgressed();
                    self.update(0, true);
                },
                clearProgressed: function () {
                    var self = this;
                    self.context.children('.progressed').slice(1).remove();
                },
                getImages: function (id, isPadding) {
                    var self = this, callee = arguments.callee, urls = self.gatherImages(id), padding = 100;
                    $.merge(urls, self.getExternalImages());
                    if (isPadding && urls.length < padding) {
                        for (var i = 0, max = padding - urls.length, blank = self.blank.attr('src'); i < max; i++) {
                            urls.push(blank);
                        }
                    }
                    urls.reverse();
                    return urls;
                },
                getExternalImages: function () {
                    var self = this, callee = arguments.callee, urls = [];
                    if (callee.cache) {
                        return callee.cache;
                    }
                    $.merge(urls, self.gatherImages('header'));
                    $.merge(urls, self.gatherImages('footer'));
                    return (callee.cache = urls);
                },
                gatherImages: function (id) {
                    var self = this, callee = arguments.callee, workspace = $('#' + id);
                    if (callee[id]) {
                        return callee[id];
                    }
                    if (!callee.preloader) {
                        callee.preloader = $.ui.preloader({process: 'gatherPreloader', gather: false});
                    }
                    callee.preloader.state = [];
                    callee.preloader.settings.urls = [];
                    if (workspace.length) {
                        callee.preloader.gatherImages(workspace);
                    }
                    return (callee[id] = callee.preloader.settings.urls);
                },
                stars: {
                    init: function (parent) {
                        var self = this;
                        if (!self.isSupport()) return;
                        self.parent = parent;
                        self.resize();
                        self.build();
                    }, resize: function () {
                        var self = this;
                        if (!self.isSupport()) return;
                        $(window).resized({
                            callback: function () {
                                self.clientWidth = window.innerWidth;
                                self.clientHeight = window.innerHeight;
                                self.positionX = self.clientWidth / 2;
                                self.positionY = self.clientHeight / 2;
                                self.canvas.width = self.clientWidth;
                                self.canvas.height = self.clientHeight;
                            }, delay: 100, now: false,
                        });
                    }, build: function () {
                        var self = this;
                        if (!self.isSupport()) return;
                        self.canvas = $('<canvas class="stars"/>').transparent().appendTo(self.parent.that).get(0);
                        self.depth = 10;
                        self.speed = .1;
                        self.lineWidth = 5;
                        self.quantity = 500;
                        self.alpha = .1;
                        self.colorOffset = 190;
                        self.colorBase = 64;
                        self.colorStep = 0;
                        self.queue = [];
                        self.clientWidth = window.innerWidth;
                        self.clientHeight = window.innerHeight;
                        self.positionX = self.clientWidth / 2;
                        self.positionY = self.clientHeight / 2;
                        self.canvas.width = self.clientWidth;
                        self.canvas.height = self.clientHeight;
                        self.context = self.canvas.getContext('2d');
                        for (var i = 0, emptyBuffer; i < self.quantity; i++) {
                            self.emit(emptyBuffer = {});
                            self.queue.push(emptyBuffer);
                        }
                        self.mousewheel();
                        self.mousemove();
                    }, play: function (callback) {
                        var self = this;
                        if (!self.isSupport() || self.isPlaying) return callback && callback();
                        $.ui.animator.tweener.to({
                            target: self.canvas,
                            duration: .3,
                            params: {
                                alpha: 1, onStart: function () {
                                    self.setStyle();
                                    self.parent.that.addClass('transparent');
                                    self.handle = $.ui.animator.requestAnimationFrame($.proxy(self.loop, self));
                                    self.isPlaying = true;
                                    callback && callback();
                                }
                            }
                        });
                    }, pause: function (callback) {
                        var self = this;
                        if (!self.isSupport() || !self.isPlaying) return callback && callback();
                        $.ui.animator.tweener.to({
                            target: self.canvas,
                            duration: .4,
                            params: {
                                alpha: .1, onStart: function () {
                                    self.parent.that.removeClass('transparent');
                                }, onUpdate: function () {
                                    if (!arguments.callee.done && this.progress() >= .5) {
                                        arguments.callee.done = true;
                                        callback && callback();
                                    }
                                }, onComplete: function () {
                                    $.ui.animator.cancelAnimationFrame(self.handle);
                                    self.isPlaying = false;
                                },
                            }
                        });
                    }, setStyle: function () {
                        var self = this;
                        self.backgroundColor = self.parent.that.css('backgroundColor');
                        self.lineColor = self.parent.that.css('color');
                    }, mousewheel: function () {
                        var self = this;
                        return;
                        $.ui.loader.xd3224c65496a99fc.success(function () {
                            $(self.parent.that).mousewheel(function (event, detail) {
                                var velocity = detail ? -detail / 3 : wheelDelta / 120;
                                if ((velocity > 0 && self.speed < 1) || (velocity < 0 && self.speed + velocity / 25 > 0.1)) {
                                    self.speed += velocity / 25;
                                }
                            });
                        });
                    }, mousemove: function () {
                        var self = this;
                        $(self.parent.that).mousemove(function (event) {
                            if (self.parent.that.is('.inside')) {
                                self.positionX = event.clientX;
                                self.positionY = event.clientY;
                            }
                        });
                    }, emit: function (buffer) {
                        var self = this;
                        buffer.startX = (Math.random() * self.clientWidth - self.clientWidth * 0.5) * self.depth;
                        buffer.startY = (Math.random() * self.clientHeight - self.clientHeight * 0.5) * self.depth;
                        buffer.depth = self.depth;
                        buffer.endX = 0;
                        buffer.endY = 0;
                    }, loop: function () {
                        var self = this, callee = arguments.callee;
                        self.context.globalAlpha = self.alpha;
                        self.context.fillStyle = self.backgroundColor;
                        self.context.fillRect(0, 0, self.clientWidth, self.clientHeight);
                        for (var j = 0; j < self.quantity; j++) {
                            var buffer = self.queue[j], startX = buffer.startX / buffer.depth,
                                startY = buffer.startY / buffer.depth,
                                lineWidth = 1 / buffer.depth * self.lineWidth + 1, lineColor = (function () {
                                    if (self.lineColor) return self.lineColor;
                                    var red = Math.floor(Math.sin(self.alpha * j + self.colorStep) * self.colorBase + self.colorOffset),
                                        green = Math.floor(Math.sin(self.alpha * j + 2 + self.colorStep) * self.colorBase + self.colorOffset),
                                        blue = Math.floor(Math.sin(self.alpha * j + 4 + self.colorStep) * self.colorBase + self.colorOffset),
                                        rgb = [red, green, blue].join(',');
                                    return rgba;
                                }());
                            if (buffer.endX != 0) {
                                self.context.strokeStyle = lineColor;
                                self.context.lineWidth = lineWidth;
                                self.context.beginPath();
                                self.context.moveTo(startX + self.positionX, startY + self.positionY);
                                self.context.lineTo(buffer.endX + self.positionX, buffer.endY + self.positionY);
                                self.context.stroke();
                            }
                            buffer.endX = startX;
                            buffer.endY = startY;
                            buffer.depth -= self.speed;
                            if (buffer.depth < self.speed || buffer.endX > self.clientWidth || buffer.endY > self.clientHeight) self.emit(buffer);
                        }
                        self.colorStep -= 0.1;
                        self.handle = $.ui.animator.requestAnimationFrame($.proxy(callee, self));
                    }, isSupport: function () {
                        var self = this;
                        return false;
                        if (sewii.env.browser.safari < 6 || sewii.env.browser.msie < 10 || sewii.env.device.mobile) {
                            return false;
                        }
                        return $.ui.helper.support.hightPerformance();
                    }
                },
            }, header: {
                id: 'header', init: function (parent) {
                    var self = this, that = $('#' + self.id), navigation = that.find('.navigation');
                    self.parent = parent;
                    self.that = that.visible();
                    self.navigation = navigation;
                    self.resize();
                    self.logo();
                    self.collapasible.init(self);
                }, resize: function () {
                    var self = this;
                    $(window).resized({
                        callback: function () {
                            var clientWidth = $(window).width();
                            self.that.toggleClass('mini-size', clientWidth <= 1280);
                            self.that.toggleClass('micro-size', clientWidth <= 980);
                            self.that.toggleClass('nano-size', clientWidth <= 850);
                        }, delay: false, now: true,
                    });
                }, logo: function () {
                    var self = this;
                    self.that.find('a.logo').attr('href', function (i, href) {
                        return $(this).data('href', href) && null;
                    }).click(function () {
                        var uri = $(this).data('href'), target = $(this).attr('_blank');
                        uri && sewii.helper.open(uri, {target: target});
                        return false;
                    });
                }, collapasible: {
                    init: function (parent) {
                        var self = this, that = parent.navigation.find('.collapse'),
                            menu = parent.navigation.find('.scrollable');
                        self.parent = parent;
                        self.that = that;
                        self.menu = menu;
                        self.resize();
                        self.toggle();
                        self.select();
                        self.hover();
                    }, resize: function () {
                        var self = this;
                        $(window).resized({
                            callback: function () {
                                self.isSupport() ? self.hide(true) : self.show(true);
                            }, delay: false, now: true,
                        });
                    }, toggle: function () {
                        var self = this;
                        self.that.attr('href', function (i, href) {
                            return $(this).data('href', href) && null;
                        }).bind({
                            click: function () {
                                self.isHidden() ? self.show() : self.hide();
                            },
                        });
                    }, select: function () {
                        var self = this;
                        self.menu.find('a[data-id]').click(function () {
                            if (self.isSupport()) {
                                self.hide(true);
                            }
                        });
                    }, hover: function () {
                        var self = this, timer = null, timeout = 1000;
                        self.parent.navigation.bind({
                            mouseover: function () {
                                if (self.isSupport()) {
                                    clearTimeout(timer);
                                }
                            }, mouseleave: function () {
                                if (self.isSupport()) {
                                    timer = setTimeout(function () {
                                    }, timeout);
                                }
                            },
                        });
                    }, show: function (force) {
                        var self = this, speed = force ? 0 : 600, easing = $.ui.animator.easing('easeInOutExpo');
                        if (force || self.isSupport()) {
                            self.parent.navigation.addClass('actived');
                            self.menu.stop().removeAttr('style').slideDown(speed, easing);
                        }
                    }, hide: function (force) {
                        var self = this, speed = force ? 0 : 600, easing = $.ui.animator.easing('easeInExpo');
                        if (force || self.isSupport()) {
                            self.menu.stop().slideUp(speed, easing, function () {
                                self.parent.navigation.removeClass('actived');
                            });
                        }
                    }, isHidden: function () {
                        var self = this;
                        return self.menu.is(':hidden');
                    }, isShowed: function () {
                        var self = this;
                        return !self.isHidden();
                    }, isSupport: function () {
                        var self = this;
                        return self.parent.that.is('.nano-size');
                    }
                },
            }, footer: {
                id: 'footer', init: function (parent) {
                    var self = this, that = $('#' + self.id);
                    self.parent = parent;
                    self.that = that.visible();
                    self.resize();
                    self.mailto();
                    self.ask();
                    self.facebook();
                }, resize: function () {
                    var self = this;
                    $(window).resized({
                        callback: function () {
                            var clientWidth = $(window).width();
                            self.that.toggleClass('small-size', clientWidth <= 1100);
                            self.that.toggleClass('mini-size', clientWidth <= 950);
                            self.that.toggleClass('micro-size', clientWidth <= 830);
                            self.that.toggleClass('nano-size', clientWidth <= 650);
                        }, delay: false, now: true,
                    });
                }, mailto: function () {
                    var self = this;
                    self.that.find('.email + .value').text(function (i, value) {
                        value = String(value).replace('(at)', '@');
                        $(this).click(function () {
                            sewii.helper.redirect('mailto:' + value);
                            $.ui.tracker.sendEvent('Mailto', 'Click', value);
                            return false;
                        });
                        return value;
                    });
                }, ask: function () {
                    var self = this;
                    self.that.find('a.ask').attr('href', function (i, href) {
                        return $(this).data('href', href) && null;
                    }).click(function () {
                        var uri = $(this).data('href');
                        uri && sewii.router.routing.go(uri);
                        return false;
                    });
                }, facebook: function () {
                    var self = this;
                    self.that.find('a.facebook').attr('href', function (i, href) {
                        return $(this).data('href', href) && null;
                    }).click(function () {
                        var uri = $(this).data('href'), target = $(this).attr('target');
                        if (uri) {
                            $.ui.tracker.sendSocial('Facebook', 'Link', uri);
                            $.ui.tracker.sendEvent('Facebook', 'Link', uri);
                            sewii.helper.open(uri, {target: target});
                        }
                        return false;
                    });
                },
            },
        }, home: {
            id: 'home', init: function (parent, callback) {
                var self = this, that = $('#' + self.id);
                self.parent = parent;
                self.that = that;
                self.that.visible();
                self.route();
                self.resize();
                self.cases.parent = self;
                $.each(self.cases, function (i, item) {
                    if (self.isCaseName(i)) {
                        item.init = (function () {
                            var abstracts = self.cases.abstracts,
                                animation = $.extend({}, abstracts.animation, item.animation),
                                child = $.extend({}, abstracts, item, {init: abstracts.init, animation: animation});
                            (self.cases[item.id] = child).init(self.cases);
                            return arguments.callee;
                        }());
                    }
                });
                self.slideshow.init(self);
                self.onInit(callback);
            }, isActive: function () {
                var self = this;
                return self.parent.isUnit('default');
            }, getUri: function () {
                var self = this;
                return $.ui.pages.common.navigator.getUri(self.id);
            }, title: function () {
                var self = this;
                return self.that.children('.heading').text() || null;
            }, route: function () {
                var self = this;
                sewii.router.routing.on('default', function (event) {
                    $.ui.pages.common.navigator.go(self.id, {enterTiming: .85, cancelable: false, event: event});
                });
            }, onPreload: function (event) {
                var self = this, routing = event;
                $.ui.pages.common.preloader.load({
                    id: self.id, routing: routing, callback: function (event) {
                        var preloader = this, isInited = self.init.called, callback = function () {
                            isInited || preloader.hide();
                            sewii.callback(routing, 'callback');
                        };
                        if (!isInited) {
                            self.init.called = true;
                            self.init($.ui.pages, callback);
                            return;
                        }
                        callback();
                    }
                });
            }, onCome: function (event) {
                var self = this, id = event.route.to;
                self.slideshow.preview(id);
            }, onEnter: function (event) {
                var self = this, id = event.route.to, isSameUnit = event.route.unit === event.previous.route.unit,
                    params = $.extend({starting: (!id && !isSameUnit) || (event.isExternal && !isSameUnit) || (event.isForce)}, event.state.params);
                sewii.history.title(null, -1);
                $.ui.tracker.sendPageView();
                self.slideshow.play(id, params);
            }, onLeave: function (event) {
                var self = this;
                if (!self.init.called) return;
                self.slideshow.pause();
            }, onExeunt: function (event) {
                var self = this;
                self.slideshow.restore();
            }, onInit: function (callback) {
                var self = this;
                callback && self.wait(function () {
                    $.ui.animator.requestAnimationFrame(function () {
                        callback && callback();
                    });
                });
            }, wait: function (callback) {
                var self = this,
                    loaders = [$.ui.loader.x3929d2bc13ed28a1, $.ui.loader.xc8633bedc25cf4b3, $.ui.loader.xcf21b1118fc03a3f, $.ui.loader.xb935591f2b7a43b8, $.ui.loader.xd3224c65496a99fc, $.ui.loader.xaf85c8a24c4358b4];
                sewii.loader.response(loaders).success(function () {
                    callback && callback();
                });
            }, resize: function () {
                var self = this;
                $(window).resized({
                    callback: function () {
                        var clientWidth = $(window).width(), clientHeight = $(window).height(),
                            items = self.that.find('.items .item');
                        items.toggleClass('full-size', clientWidth <= 1920);
                        items.toggleClass('super-size', clientWidth <= 1600);
                        items.toggleClass('long-size', clientWidth <= 1440);
                        items.toggleClass('medium-size', clientWidth <= 1366);
                        items.toggleClass('small-size', clientWidth <= 1280);
                        items.toggleClass('mini-size', clientWidth <= 1024);
                        items.toggleClass('micro-size', clientWidth <= 800);
                        items.toggleClass('mini-size-height', clientHeight <= 600);
                        items.toggleClass('max-size-height', clientHeight - clientWidth >= 0);
                        items.toggleClass('medium-size-height', clientHeight - clientWidth >= -100);
                        items.each(function () {
                            $(this).css('width', clientWidth);
                        });
                        self.that.find('.scalable > img').fit();
                        items.find('.displayer .inside').each(function () {
                            var frame = $(this).parent().find('img[src*="-frame"]'), frameWidth = frame.width(),
                                frameHeight = frame.height(), wrapper = frame.parent(), clientWidth = wrapper.width(),
                                clientHeight = wrapper.height(), offsetSize = 2, marginTop = 0, marginLeft = 0;
                            frameWidth = Math.round(frameWidth) + offsetSize;
                            frameHeight = Math.round(frameHeight) + offsetSize;
                            marginLeft = -Math.round(frameWidth / 2), marginTop = -Math.round(frameHeight / 2);
                            $(this).css({
                                display: 'block',
                                position: 'absolute',
                                left: '50%',
                                top: '50%',
                                width: frameWidth,
                                height: frameHeight,
                                marginLeft: marginLeft,
                                marginTop: marginTop,
                            });
                        });
                    }, delay: function () {
                        return !self.isActive();
                    }, now: true,
                });
            }, isCaseName: function (name) {
                return /^[a-z]\d+/.test(name);
            }, slideshow: {
                init: function (parent) {
                    var self = this, that = parent.that.find('.slideshow'), scrollable = that.find('.scrollable'),
                        items = scrollable.find('.items .item'), pages = that.find('.pager li a');
                    self.parent = parent;
                    self.that = that;
                    self.scrollable = scrollable;
                    self.items = items;
                    self.pages = pages;
                    self.pager();
                    self.click();
                    self.keyboard();
                    self.mousewheel();
                    self.touch();
                    self.player.init(self);
                    self.draggable.init(self);
                }, pager: function () {
                    var self = this;
                    self.pages.attr('href', function (i, href) {
                        return $(this).data('href', href) && null;
                    }).click(function (event, params) {
                        var uri = $(this).data('href');
                        if (uri && !self.isBusy()) {
                            sewii.router.routing.go(uri, {params: params});
                        }
                        return false;
                    });
                }, click: function () {
                    var self = this;
                    self.items.find('.link').attr('href', function (i, href) {
                        return $(this).data('href', href) && null;
                    }).click(function () {
                        if (!self.draggable.isSupport()) {
                            var uri = $(this).data('href');
                            uri && sewii.router.routing.go(uri);
                            return false;
                        }
                    });
                }, slide: function (to, params) {
                    params = params || {};
                    to = this.getDest(to);
                    var self = this, callee = arguments.callee, from = self.currently().data('id'),
                        current = self.items.filter('[data-id="' + from + '"]'),
                        target = self.items.filter('[data-id="' + to + '"]'),
                        page = self.pages.filter('[data-id="' + to + '"]');
                    if (target.length) {
                        if (callee.last === to) return; else callee.last = to;
                        if (self.that.is('.busy')) {
                            params.duration = 0;
                        }
                        self.that.addClass('busy');
                        self.pages.removeClass('actived');
                        page.addClass('actived');
                        if (current.length && from !== to) {
                            if (self.parent.cases[from]) {
                                self.parent.cases[from].pause();
                            }
                        }
                        if (self.parent.cases[to] && from !== to) {
                            self.parent.cases[to].restore();
                        }
                        self.draggable.disable();
                        self.zIndex = self.zIndexMax();
                        self.dIndex = sewii.ifSet(self.dIndex, 5) + 1;
                        var duration = sewii.ifSet(params.duration, 0.6), delay = 0.05,
                            directions = ['top', 'left', 'bottom', 'right'],
                            direction = params.direction || directions[self.dIndex % directions.length],
                            isTop = direction === 'top', isLeft = direction === 'left',
                            isBottom = direction === 'bottom', isRight = direction === 'right',
                            slideType = isTop || isBottom ? 'top' : 'left',
                            invisibleSize = isTop || isBottom ? target.height() : target.width(),
                            toReset = {x: 0, y: 0, left: 0, top: 0}, toReadied = {}, toHidden = {}, toVisible = {};
                        toVisible[slideType] = 0;
                        toHidden[slideType] = isLeft || isTop ? -invisibleSize : invisibleSize;
                        toReadied[slideType] = isRight || isBottom ? -invisibleSize : invisibleSize;
                        toReadied.zIndex = self.zIndex;
                        if (params.starting) {
                            toReadied[slideType] = 0;
                            duration = 0;
                        }
                        $.ui.animator.tweener.set([target, current], toReset);
                        $.ui.animator.tweener.to({
                            target: current,
                            duration: duration,
                            params: {css: toHidden, ease: Circ.easeIn, delay: delay, overwrite: true}
                        });
                        $.ui.animator.tweener.fromTo({
                            target: target, duration: duration, from: {css: toReadied}, to: {
                                css: toVisible,
                                ease: Circ.easeInOut,
                                delay: delay,
                                overwrite: true,
                                onComplete: function () {
                                    (function () {
                                        var index = (target.index() + 1) % self.items.length,
                                            item = self.items.eq(index).visible(),
                                            next = self.items.eq(index).visible().data('id');
                                        $.ui.animator.tweener.set(item, $.extend({zIndex: self.zIndex - 1}, toReset));
                                        if (self.parent.cases[next]) {
                                            self.parent.cases[next].restore();
                                        }
                                        self.draggable.enable();
                                    }());
                                    if (self.parent.cases[to]) {
                                        self.parent.cases[to].play(function () {
                                            self.onCurrentPlayEnd && self.onCurrentPlayEnd();
                                        });
                                    }
                                    self.that.removeClass('busy');
                                }
                            }
                        });
                    }
                }, prev: function (params) {
                    var self = this, index = (self.currently().parent().index() - 1) % self.pages.length;
                    self.pages.eq(index).trigger('click', params);
                }, next: function (params) {
                    var self = this, index = (self.currently().parent().index() + 1) % self.pages.length;
                    self.pages.eq(index).trigger('click', params);
                }, keyboard: function () {
                    var self = this, timer = null;
                    $(window).bind('directionKeyDown', function (e, info) {
                        if (!self.parent.isActive()) return;
                        clearTimeout(timer);
                        timer = setTimeout(function () {
                            (info.isLeft || info.isUp) && self.prev();
                            (info.isRight || info.isDown) && self.next();
                        }, 200);
                    });
                }, mousewheel: function () {
                    var self = this, balance = 4, prevTimes = 0, nextTimes = 0, timer = null;
                    $.ui.loader.xd3224c65496a99fc.success(function () {
                        self.that.mousewheel(function (event, delta) {
                            var isPrev = delta >= 1, isNext = delta <= -1;
                            clearTimeout(timer);
                            timer = setTimeout(function () {
                                prevTimes = nextTimes = 0;
                            }, 60);
                            if (isPrev && prevTimes++ >= balance) {
                                prevTimes = 0;
                                self.prev();
                            }
                            if (isNext && nextTimes++ >= balance) {
                                nextTimes = 0;
                                self.next();
                            }
                        });
                    });
                }, touch: function () {
                    var self = this;
                    sewii.env.support.touchable && $.ui.loader.xaf85c8a24c4358b4.success(function () {
                        Hammer(self.scrollable.get(0), {
                            swipeVelocityX: .3,
                            swipeVelocityY: .3,
                        }).on('swipe', function (event) {
                            var next = event.gesture.direction === 'right' || event.gesture.direction === 'down',
                                prev = event.gesture.direction === 'left' || event.gesture.direction === 'up';
                            prev && self.prev({direction: event.gesture.direction === 'left' ? 'left' : 'top'});
                            next && self.next({direction: event.gesture.direction === 'right' ? 'right' : 'bottom'});
                        });
                    });
                }, isBusy: function () {
                    var self = this;
                    return self.that.is('.busy');
                }, defaultly: function () {
                    var self = this, page = self.pages.first();
                    return page;
                }, currently: function () {
                    var self = this, actived = self.pages.filter('.actived'),
                        page = actived.length ? actived.first() : self.defaultly();
                    return page;
                }, getDest: function (id) {
                    var self = this, target = self.pages.filter('[data-id="' + id + '"]').data('id');
                    return target || self.defaultly().data('id');
                }, zIndexMax: function () {
                    var self = this;
                    return (self.zIndex = sewii.ifSet(self.zIndex, 100) + 1);
                }, preview: function (id) {
                    var self = this;
                    id = self.getDest(id);
                    self.items.filter('[data-id="' + id + '"]').css({
                        zIndex: self.zIndexMax(),
                        visibility: 'visible',
                        left: 'auto',
                        top: 'auto',
                        right: 'auto',
                        bottom: 'auto'
                    });
                }, play: function (id, params) {
                    var self = this;
                    self.slide(id, params);
                    self.player.play();
                }, pause: function () {
                    var self = this, currentId = self.currently().data('id');
                    self.player.pause();
                    $.ui.pages.home.cases[currentId].pause();
                    self.slide.last = null;
                }, restore: function () {
                    var self = this;
                    sewii.each(self.parent.cases, function (id) {
                        if (self.parent.isCaseName(id)) {
                            self.parent.cases[id].restore();
                        }
                    });
                    self.pages.removeClass('actived');
                }, player: {
                    timeout: 7000, init: function (parent) {
                        var self = this;
                        self.parent = parent;
                        self.listen();
                        self.parent.onCurrentPlayEnd = function () {
                            self.next();
                        };
                    }, listen: function () {
                        var self = this,
                            events = 'mousemove mousewheel keydown click touchmove pointermove MSPointerMove';
                        $(window).bind(events, function () {
                            if (!self.timer) return;
                            clearTimeout(self.resetTimer);
                            self.resetTimer = setTimeout(function () {
                                if (!self.parent.parent.isActive()) return;
                                self.next();
                            }, 50);
                        });
                    }, next: function () {
                        var self = this;
                        clearTimeout(self.timer);
                        self.timer = setTimeout(function () {
                            if (self.paused) return;
                            self.parent.next();
                            self.timer = setTimeout(arguments.callee, self.timeout);
                        }, self.timeout);
                    }, play: function () {
                        var self = this;
                        self.paused = false;
                        clearTimeout(self.timer);
                        clearTimeout(self.resetTimer);
                    }, pause: function () {
                        var self = this;
                        self.paused = true;
                        clearTimeout(self.timer);
                        clearTimeout(self.resetTimer);
                    }
                }, draggable: {
                    init: function (parent) {
                        var self = this;
                        if (!self.isSupport()) return;
                        self.parent = parent;
                        self.bind();
                        self.click();
                    }, wait: function (callback) {
                        var self = this, loaders = [$.ui.loader.xc8633bedc25cf4b3, $.ui.loader.xcf21b1118fc03a3f];
                        sewii.loader.response(loaders).success(function () {
                            callback && callback();
                        });
                    }, bind: function () {
                        var self = this;
                        self.wait(function () {
                            self.parent.items.addClass('grab');
                            self.parent.items.find('.link').attr('data-clickable', 'false').addClass('grab');
                            self.draggables = Draggable.create(self.parent.items, {
                                type: "left, top",
                                type: "x, y",
                                bounds: window,
                                lockAxis: false,
                                zIndexBoost: false,
                                throwProps: true,
                                force3D: true,
                                edgeResistance: .85,
                                onClick: function (e) {
                                    $(e.target).trigger('click');
                                },
                                onPress: function (e) {
                                    $(e.target).trigger('mousedown', e.button);
                                },
                                onDragEnd: function (e) {
                                    $(e.target).trigger('mousemove');
                                },
                                onDrag: function () {
                                    self.parent.items.addClass('grabbing');
                                },
                                onRelease: function () {
                                    self.parent.items.removeClass('grabbing');
                                }
                            });
                        });
                    }, click: function () {
                        var self = this, toListenClick = true;
                        self.parent.items.bind({
                            mousedown: function (e, button) {
                                toListenClick = e.button !== 2 && button !== 2;
                            }, mousemove: (function () {
                                var x, y;
                                return function (e) {
                                    if (e.clientX === x && e.clientY === y) return;
                                    x = e.clientX;
                                    y = e.clientY;
                                    toListenClick = false;
                                };
                            })(), click: function (e) {
                                var me = $(this), callee = arguments.callee,
                                    lastTriggeredTime = callee.lastTriggeredTime;
                                callee.lastTriggeredTime = e.timeStamp;
                                if (lastTriggeredTime) {
                                    var diff = e.timeStamp - lastTriggeredTime;
                                    if (diff <= 100) return false;
                                }
                                if (toListenClick) {
                                    var uri = me.find('.link').data('href');
                                    uri && sewii.router.routing.go(uri);
                                }
                                return false;
                            }
                        });
                    }, enable: function () {
                        var self = this;
                        if (!self.isSupport()) return;
                        $.each(self.draggables || [], function (i, draggable) {
                            draggable.enable();
                        });
                    }, disable: function () {
                        var self = this;
                        if (!self.isSupport()) return;
                        $.each(self.draggables || [], function (i, draggable) {
                            draggable.disable();
                        });
                    }, isSupport: function () {
                        if (sewii.env.os.android || sewii.env.os.ios) return false;
                        return !sewii.env.support.touchable;
                    },
                }
            }, parallax: {
                init: function (parent, id) {
                    var self = this;
                    if (!self.isSupport()) return;
                    self.parent = parent;
                    $.ui.loader.xb935591f2b7a43b8.success(function () {
                        var workspace = self.workspace(id);
                        workspace.find('.layers').each(function () {
                            $($(this).find('.layer').get().reverse()).each(function (i) {
                                $(this).css('z-index', i + 1);
                            });
                            var layer = $(this).parallax({
                                scalarX: 25,
                                scalarY: 25,
                                frictionX: 0.1,
                                frictionY: 0.1,
                                calibrationDelay: 0,
                                supportDelay: 0
                            });
                            workspace.data('parallax', layer.data('api'));
                            self.pause(id);
                        });
                    });
                }, restore: function (id) {
                    if (!this.isSupport()) return;
                    var parallax = this.workspace(id).data('parallax');
                }, play: function (id) {
                    if (!this.isSupport()) return;
                    var parallax = this.workspace(id).data('parallax');
                    parallax && setTimeout(function () {
                        parallax.enable();
                    }, parallax.supportDelay);
                }, pause: function (id) {
                    if (!this.isSupport()) return;
                    var parallax = this.workspace(id).data('parallax');
                    parallax && setTimeout(function () {
                        parallax.disable();
                    }, parallax.supportDelay);
                }, workspace: function (id) {
                    if (!this.isSupport()) return;
                    return this.parent.that.find('.item[data-id="' + id + '"]');
                }, isSupport: function () {
                    if (!window.requestAnimationFrame || sewii.env.os.android || sewii.env.os.ios) return false;
                    return true;
                },
            }, fadeshow: {
                timeout: 3000, init: function (parent, id) {
                    var self = this;
                    self.parent = parent;
                    self.timelines = {};
                }, next: function (id, params) {
                    params = params || {};
                    var self = this, outSpeed = .7, inSpeed = .9, filter = '[src*="-page"]',
                        workspace = self.workspace(id), pages = workspace.find('.displayer img').filter(filter);
                    if (pages.length > 1) {
                        var actived = pages.filter('.actived:first'), current = actived.length ? actived : pages.eq(-1),
                            next = current.prev(filter);
                        if (!next.length) {
                            next = pages.eq(-1);
                        }
                        if (params.restore) {
                            clearTimeout(self.timer);
                            pages.removeClass('actived');
                            pages.transparent();
                            pages.eq(-1).opaque();
                            return;
                        }
                        if (self.isPaused(id)) {
                            return;
                        }
                        next.transparent();
                        self.timelines[id] = self.timelines[id] || {};
                        self.timelines[id].out = $.ui.animator.tweener.to({
                            target: current,
                            duration: outSpeed,
                            params: {
                                alpha: 0, onComplete: function () {
                                    pages.removeClass('actived');
                                    self.timelines[id]['in'] = $.ui.animator.tweener.to({
                                        target: next,
                                        duration: inSpeed,
                                        params: {
                                            alpha: 1, onComplete: function () {
                                                next.addClass('actived');
                                                clearTimeout(self.timer);
                                                self.timer = setTimeout(function () {
                                                    self.isPaused() || self.next(id);
                                                }, self.timeout);
                                            }
                                        }
                                    });
                                }
                            }
                        });
                    }
                }, restore: function (id) {
                    var self = this;
                    sewii.each(self.timelines[id], function (i, timeline) {
                        timeline.pause(0);
                    });
                    self.next(id, {restore: true});
                }, play: function (id) {
                    var self = this;
                    sewii.each(self.timelines[id], function (i, timeline) {
                        timeline.pause(0);
                    });
                    self.timer = self.workspace(id).find('.displayer').removeClass('paused');
                    clearTimeout(self.timer);
                    setTimeout(function () {
                        self.next(id);
                    }, self.timeout);
                }, pause: function (id) {
                    var self = this;
                    self.workspace(id).find('.displayer').addClass('paused');
                    clearTimeout(self.timer);
                    sewii.each(self.timelines[id], function (i, timeline) {
                        timeline.pause();
                    });
                }, isPaused: function (id) {
                    return this.workspace(id).find('.displayer').hasClass('paused');
                }, workspace: function (id) {
                    return this.parent.that.find('.item[data-id="' + id + '"]');
                }
            }, cases: {
                abstracts: {
                    id: 'abstracts', init: function (parent) {
                        var self = this;
                        self.parent = parent;
                        self.animation.init(self);
                        self.parent.parent.parallax.init(parent.parent, self.id);
                        self.parent.parent.fadeshow.init(parent.parent, self.id);
                    }, play: function (callback) {
                        var self = this;
                        if (!self.playing) {
                            self.playing = true;
                            self.paused = false;
                            self.parent.parent.parallax.play(self.id);
                            self.animation.play(function () {
                                self.parent.parent.fadeshow.play(self.id);
                                callback && callback();
                            });
                        }
                    }, pause: function () {
                        var self = this;
                        if (!self.paused) {
                            self.paused = true;
                            self.playing = false;
                            self.animation.pause();
                            self.parent.parent.fadeshow.pause(self.id);
                            self.parent.parent.parallax.pause(self.id);
                        }
                    }, restore: function () {
                        var self = this;
                        if (self.paused) {
                            self.animation.restore();
                            self.parent.parent.fadeshow.restore(self.id);
                            self.parent.parent.parallax.restore(self.id);
                        }
                    }, animation: {
                        delay: .01, init: function (parent) {
                            var self = this;
                            self.parent = parent;
                            self.timeline = new $.ui.animator.timeline();
                        }, wait: function (callback) {
                            var self = this;
                            callback && callback();
                        }, fire: function () {
                            var self = this, timer;
                            if (!self.timeline || !self.timeline.build) return;
                            self.timeline.build();
                            $(window).resized({
                                callback: function () {
                                    var delay = 60, started = 0, ended = self.timeline.totalDuration(),
                                        isActived = function () {
                                            return self.parent.parent.parent.isActive() && self.parent.id == self.parent.parent.parent.slideshow.currently().data('id');
                                        }, execute = function () {
                                            self.timeline.pause(ended);
                                            sewii.each(self.timeline.getChildren(), function (i, timeline) {
                                                var target = $(timeline.target);
                                                if (target.length) {
                                                    var styled = sewii.stringify(target.attr('style')),
                                                        styleDisplay = styled.match(/(display)\s*:\s*([^;]*);/),
                                                        stylePosition = styled.match(/(position)\s*:\s*([^;]*);/);
                                                    target.removeAttr('style');
                                                    styleDisplay && target.css(styleDisplay[1], styleDisplay[2]);
                                                    stylePosition && target.css(stylePosition[1], stylePosition[2]);
                                                }
                                            });
                                            self.timeline.clear();
                                            self.timeline.build();
                                            self.timeline.pause(isActived() ? ended : started);
                                        };
                                    if (!isActived()) {
                                        clearTimeout(timer);
                                        timer = setTimeout(function () {
                                            execute();
                                        }, delay);
                                    } else execute();
                                }, delay: false, now: false,
                            });
                        }, play: function (callback) {
                            var self = this, currentTime = self.timeline.time(),
                                idleTime = self.timeline.getLabelTime('idle'), callbackProxy = function () {
                                    var func = callback;
                                    (callback = null) || func && func();
                                };
                            if (currentTime >= idleTime) {
                                callbackProxy();
                            } else {
                                self.onPlayEnd = callbackProxy;
                                self.timeline.play();
                                self.timeline.eventCallback("onComplete", function () {
                                    callbackProxy();
                                });
                            }
                        }, pause: function () {
                            var self = this;
                            self.timeline.pause();
                        }, restore: function () {
                            var self = this;
                            self.timeline.pause(0);
                        },
                    },
                }, e000332: {
                    id: 'e000332', animation: {
                        init: function (parent) {
                            var self = this, base = parent.parent.parent,
                                that = base.that.find('.item[data-id="' + parent.id + '"]');
                            self.that = that;
                            self.parent = parent;
                            self.timeline = new $.ui.animator.timeline();
                            that.length && self.wait(function () {
                                self.timeline.build = function () {
                                    self.timeline.from({
                                        target: that.find('.displayer'),
                                        duration: 1,
                                        params: {ease: Cubic.easeInOut, opacity: 0, scale: .3, y: '250%',},
                                        position: '-=1'
                                    }).staggerFrom({
                                        target: sewii.shuffle(that.find('[class*="product"]').get()),
                                        duration: 1.2,
                                        params: {ease: Cubic.easeInOut, opacity: 0, scale: .3, y: '150%',},
                                        stagger: .08,
                                        position: '-=.5'
                                    }).staggerFrom({
                                        target: that.find('.caption > *').get().reverse(),
                                        duration: 1,
                                        params: {opacity: 0, y: (screen.width * 1.5), ease: Expo.easeOut,},
                                        stagger: .20,
                                        position: '-=1.8'
                                    }).addLabel("idle").addCallback({
                                        callback: function () {
                                            self.onPlayEnd && self.onPlayEnd();
                                        }, position: '-=.8'
                                    }).pause();
                                };
                                self.fire();
                            });
                        },
                    }
                }, e000364: {
                    id: 'e000364', animation: {
                        init: function (parent) {
                            var self = this, base = parent.parent.parent,
                                that = base.that.find('.item[data-id="' + parent.id + '"]');
                            self.that = that;
                            self.parent = parent;
                            self.timeline = new $.ui.animator.timeline();
                            that.length && self.wait(function () {
                                var words = that.find('.caption').find('.name, .type, .title'),
                                    text = new SplitText(words, {type: 'chars'});
                                self.timeline.build = function () {
                                    self.timeline.fromTo({
                                        target: that.find('.displayer'),
                                        duration: 1,
                                        from: {
                                            opacity: 0.3,
                                            rotationX: 25,
                                            z: -10,
                                            transformPerspective: 200,
                                            transformOrigin: '50% 100% 0',
                                        },
                                        to: {opacity: 1, rotationX: 0, z: 0, ease: Circ.easeInOut, delay: self.delay,},
                                        position: '0'
                                    }).from({
                                        target: that.find('.caption'),
                                        duration: 1,
                                        params: {ease: Circ.easeOut, opacity: 0},
                                        position: '-=.7'
                                    }).staggerFrom({
                                        target: text.chars,
                                        duration: 1,
                                        params: {scale: 2, opacity: 0, x: screen.width / 2, ease: Expo.easeOut,},
                                        stagger: .035,
                                        position: '-=1'
                                    }).addLabel("idle").addCallback({
                                        callback: function () {
                                            self.onPlayEnd && self.onPlayEnd();
                                        }, position: '-=.8'
                                    }).pause();
                                };
                                self.fire();
                            });
                        }, wait: function (callback) {
                            var self = this;
                            $.ui.loader.x3929d2bc13ed28a1.success(function () {
                                callback && callback();
                            });
                        }
                    }
                }, m000160: {
                    id: 'm000160', animation: {
                        init: function (parent) {
                            var self = this, base = parent.parent.parent,
                                that = base.that.find('.item[data-id="' + parent.id + '"]');
                            self.that = that;
                            self.parent = parent;
                            self.timeline = new $.ui.animator.timeline();
                            that.length && self.wait(function () {
                                var text = that.find('.subject').children('strong, p'),
                                    subject = new SplitText(text, {type: 'chars'});
                                self.timeline.build = function () {
                                    self.timeline.from({
                                        target: that.find('.subject strong'),
                                        duration: 1,
                                        params: {opacity: 0, delay: self.delay},
                                        position: '-=.0'
                                    }).staggerFrom({
                                        target: subject.chars,
                                        duration: .3,
                                        params: {
                                            opacity: .5,
                                            scale: 3,
                                            rotationY: 90,
                                            x: -20,
                                            transformOrigin: '0% 50% 50',
                                            ease: Circ.easeInOut,
                                        },
                                        stagger: .04,
                                        position: '-=1'
                                    }).from({
                                        target: that.find('.displayer'),
                                        duration: 2,
                                        params: {opacity: 1, bottom: '-100%', ease: Circ.easeInOut,},
                                        position: '-=1.0'
                                    }).from({
                                        target: that.find('.caption'),
                                        duration: 1,
                                        params: {opacity: 0, top: '40%', ease: Circ.easeOut},
                                        position: '-=.8'
                                    }).from({
                                        target: that.find('.month'),
                                        duration: 2,
                                        params: {ease: Cubic.easeInOut, opacity: 0, scale: .3, top: '40%'},
                                        position: '-=1.2'
                                    }).addLabel("idle").addCallback({
                                        callback: function () {
                                            self.onPlayEnd && self.onPlayEnd();
                                        }, position: '-=.8'
                                    }).pause();
                                };
                                self.fire();
                            });
                        }, wait: function (callback) {
                            var self = this, loaders = [$.ui.loader.x3929d2bc13ed28a1,];
                            sewii.loader.response(loaders).success(function () {
                                callback && callback();
                            });
                        }
                    }
                }, e000359: {
                    id: 'e000359', animation: {
                        init: function (parent) {
                            var self = this, base = parent.parent.parent,
                                that = base.that.find('.item[data-id="' + parent.id + '"]');
                            self.that = that;
                            self.parent = parent;
                            self.timeline = new $.ui.animator.timeline();
                            that.length && self.wait(function () {
                                var text = that.find('.subject').children('strong, p'),
                                    subject = new SplitText(text, {type: 'words'});
                                self.timeline.build = function () {
                                    self.timeline.staggerFrom({
                                        target: subject.words,
                                        duration: 1,
                                        params: {
                                            opacity: .0,
                                            scale: 5,
                                            rotationX: -180,
                                            y: 150,
                                            delay: self.delay,
                                            transformOrigin: '50% 100% 50',
                                            ease: Circ.easeInOut,
                                        },
                                        stagger: .1,
                                        position: '-=0'
                                    }).from({
                                        target: that.find('.caption'),
                                        duration: 1,
                                        params: {opacity: 0, scale: 0, ease: Circ.easeInOut},
                                        position: '-=.8'
                                    }).from({
                                        target: that.find('.displayer'),
                                        duration: 1,
                                        params: {bottom: '-150%', ease: Circ.easeOut},
                                        position: '-=.8'
                                    }).addLabel("idle").addCallback({
                                        callback: function () {
                                            self.onPlayEnd && self.onPlayEnd();
                                        }, position: '-=.8'
                                    }).pause();
                                };
                                self.fire();
                            });
                        }, wait: function (callback) {
                            var self = this, loaders = [$.ui.loader.x3929d2bc13ed28a1,];
                            sewii.loader.response(loaders).success(function () {
                                callback && callback();
                            });
                        }
                    }
                }, e000385: {
                    id: 'e000385', animation: {
                        init: function (parent) {
                            var self = this, base = parent.parent.parent,
                                that = base.that.find('.item[data-id="' + parent.id + '"]');
                            self.that = that;
                            self.parent = parent;
                            self.timeline = new $.ui.animator.timeline();
                            that.length && self.wait(function () {
                                self.timeline.build = function () {
                                    self.timeline.from({
                                        target: that.find('.product'),
                                        duration: 1,
                                        params: {opacity: 0, right: '-50%', delay: self.delay, ease: Cubic.easeOut,},
                                        position: '-=.0'
                                    }).from({
                                        target: that.find('.displayer'),
                                        duration: 1,
                                        params: {y: '150%', opacity: 0, scale: .3, ease: Cubic.easeInOut,},
                                        position: '-=.5'
                                    }).from({
                                        target: that.find('.bird'),
                                        duration: 1,
                                        params: {ease: Cubic.easeInOut, opacity: 0, scale: .3, y: '150%',},
                                        position: '-=.5'
                                    }).staggerFrom({
                                        target: that.find('.caption > *').get().reverse(),
                                        duration: 1,
                                        params: {opacity: 0, x: -(screen.height * 1.5) + '%', ease: Expo.easeOut,},
                                        stagger: .20,
                                        position: '-=.5'
                                    }).addLabel("idle").addCallback({
                                        callback: function () {
                                            self.onPlayEnd && self.onPlayEnd();
                                        }, position: '-=.6'
                                    }).pause();
                                };
                                self.fire();
                            });
                        }
                    }
                }, e000356: {
                    id: 'e000356', animation: {
                        init: function (parent) {
                            var self = this, base = parent.parent.parent,
                                that = base.that.find('.item[data-id="' + parent.id + '"]');
                            self.that = that;
                            self.parent = parent;
                            self.timeline = new $.ui.animator.timeline();
                            that.length && self.wait(function () {
                                self.timeline.build = function () {
                                    self.timeline.staggerFrom({
                                        target: that.find('.logo, .caption > *').get().reverse(),
                                        duration: 1,
                                        params: {
                                            opacity: 0,
                                            x: -(screen.width * 1.5),
                                            ease: Expo.easeOut,
                                            delay: self.delay,
                                        },
                                        stagger: .20,
                                        position: '-=.0'
                                    }).from({
                                        target: that.find('.displayer'),
                                        duration: 1,
                                        params: {rotation: -180, transformOrigin: '0% 100% 0', ease: Circ.easeOut,},
                                        position: '-=.8'
                                    }).addLabel("idle").addCallback({
                                        callback: function () {
                                            self.onPlayEnd && self.onPlayEnd();
                                        }, position: '-=.8'
                                    }).pause();
                                };
                                self.fire();
                            });
                        }
                    }
                }, m000168: {
                    id: 'm000168', animation: {
                        init: function (parent) {
                            var self = this, base = parent.parent.parent,
                                that = base.that.find('.item[data-id="' + parent.id + '"]');
                            self.that = that;
                            self.parent = parent;
                            self.timeline = new $.ui.animator.timeline();
                            that.length && self.wait(function () {
                                self.timeline.build = function () {
                                    self.timeline.staggerFrom({
                                        target: that.find('.product1, .product2, .caption > *').get().reverse(),
                                        duration: 1,
                                        params: {
                                            delay: self.delay,
                                            ease: Expo.easeOut,
                                            opacity: 0,
                                            y: (screen.height * 1.5),
                                        },
                                        stagger: .20,
                                        position: '-=.0'
                                    }).from({
                                        target: that.find('.displayer'),
                                        duration: 1,
                                        params: {
                                            rotation: 180,
                                            bottom: '-25%',
                                            ease: Circ.easeOut,
                                            transformOrigin: '100% 100% 0',
                                        },
                                        position: '-=.8'
                                    }).addLabel("idle").addCallback({
                                        callback: function () {
                                            self.onPlayEnd && self.onPlayEnd();
                                        }, position: '-=.8'
                                    }).pause();
                                };
                                self.fire();
                            });
                        }
                    }
                }, e000331: {
                    id: 'e000331', animation: {
                        init: function (parent) {
                            var self = this, base = parent.parent.parent,
                                that = base.that.find('.item[data-id="' + parent.id + '"]');
                            self.that = that;
                            self.parent = parent;
                            self.timeline = new $.ui.animator.timeline();
                            that.length && self.wait(function () {
                                var words = that.find('.caption').find('.name, .type, .title'),
                                    text = new SplitText(words, {type: 'chars'});
                                self.timeline.build = function () {
                                    self.timeline.fromTo({
                                        target: that.find('.displayer'),
                                        duration: 1,
                                        from: {
                                            opacity: 0.3,
                                            rotationX: 25,
                                            z: -10,
                                            transformPerspective: 200,
                                            transformOrigin: '50% 100% 0',
                                        },
                                        to: {opacity: 1, rotationX: 0, z: 0, ease: Circ.easeInOut, delay: self.delay,},
                                        position: '0'
                                    }).from({
                                        target: that.find('.caption'),
                                        duration: 1,
                                        params: {ease: Circ.easeOut, opacity: 0},
                                        position: '-=.7'
                                    }).staggerFrom({
                                        target: text.chars,
                                        duration: 1,
                                        params: {scale: 2, opacity: 0, x: screen.width / 2, ease: Expo.easeOut,},
                                        stagger: .035,
                                        position: '-=1'
                                    }).addLabel("idle").addCallback({
                                        callback: function () {
                                            self.onPlayEnd && self.onPlayEnd();
                                        }, position: '-=.8'
                                    }).pause();
                                };
                                self.fire();
                            });
                        }, wait: function (callback) {
                            var self = this;
                            $.ui.loader.x3929d2bc13ed28a1.success(function () {
                                callback && callback();
                            });
                        }
                    }
                }, m000187: {
                    id: 'm000187', animation: {
                        init: function (parent) {
                            var self = this, base = parent.parent.parent,
                                that = base.that.find('.item[data-id="' + parent.id + '"]');
                            self.that = that;
                            self.parent = parent;
                            self.timeline = new $.ui.animator.timeline();
                            that.length && self.wait(function () {
                                self.timeline.build = function () {
                                    self.timeline.fromTo({
                                        target: that.find('.displayer'),
                                        duration: 1,
                                        from: {rotationY: 60, scale: .1, opacity: .0, transformPerspective: 600},
                                        to: {
                                            rotationY: 360,
                                            scale: 1,
                                            opacity: 1,
                                            transformPerspective: 1200,
                                            delay: self.delay,
                                        },
                                        position: '+=.0'
                                    }).staggerFrom({
                                        target: that.find('.caption > *'),
                                        duration: 1,
                                        params: {ease: Expo.easeOut, opacity: 0, x: -(screen.width * 1.5) + 'px',},
                                        stagger: .25,
                                        position: '-=.5'
                                    }).addLabel("idle").addCallback({
                                        callback: function () {
                                            self.onPlayEnd && self.onPlayEnd();
                                        }, position: '-=.8'
                                    }).pause();
                                };
                                self.fire();
                            });
                        }
                    }
                }, m000089: {
                    id: 'm000089', animation: {
                        init: function (parent) {
                            var self = this, base = parent.parent.parent,
                                that = base.that.find('.item[data-id="' + parent.id + '"]');
                            self.that = that;
                            self.parent = parent;
                            self.timeline = new $.ui.animator.timeline();
                            that.length && self.wait(function () {
                                self.timeline.build = function () {
                                    self.timeline.from({
                                        target: that.find('.displayer'),
                                        duration: .8,
                                        params: {
                                            delay: self.delay,
                                            ease: Circ.easeOut,
                                            right: '-60%',
                                            bottom: '-60%',
                                            rotation: 380,
                                            opacity: 0,
                                        },
                                        position: '-=.0'
                                    }).from({
                                        target: that.find('.cup'),
                                        duration: 1,
                                        params: {ease: Expo.easeOut, left: '-60%',},
                                        position: '-=.4'
                                    }).from({
                                        target: that.find('.caption'),
                                        duration: 1,
                                        params: {ease: Expo.easeOut, left: '-100%',},
                                        position: '-=.8'
                                    }).addLabel("idle").addCallback({
                                        callback: function () {
                                            self.onPlayEnd && self.onPlayEnd();
                                        }, position: '-=.8'
                                    }).pause();
                                };
                                self.fire();
                            });
                        }
                    }
                }, m000100: {
                    id: 'm000100', animation: {
                        init: function (parent) {
                            var self = this, base = parent.parent.parent,
                                that = base.that.find('.item[data-id="' + parent.id + '"]');
                            self.that = that;
                            self.parent = parent;
                            self.timeline = new $.ui.animator.timeline();
                            that.length && self.wait(function () {
                                var text = that.find('.subject').children('strong, p'),
                                    subject = new SplitText(text, {type: 'chars'});
                                self.timeline.build = function () {
                                    self.timeline.from({
                                        target: that.find('.displayer'),
                                        duration: 1,
                                        params: {delay: self.delay, ease: Circ.easeOut, bottom: '-50%',},
                                        position: '-=.0'
                                    }).staggerFrom({
                                        target: subject.chars,
                                        duration: .8,
                                        params: {
                                            delay: .01,
                                            opacity: .0,
                                            scale: 3,
                                            rotationX: -180,
                                            y: 10,
                                            transformOrigin: '0% 30% 5',
                                            ease: Back.easeInOut,
                                        },
                                        stagger: .020,
                                        position: '-=.9'
                                    }).from({
                                        target: that.find('.caption'),
                                        duration: 1.2,
                                        params: {ease: Back.easeInOut, opacity: .0, scale: 0,},
                                        position: '-=.90'
                                    }).addLabel("idle").addCallback({
                                        callback: function () {
                                            self.onPlayEnd && self.onPlayEnd();
                                        }, position: '-=1'
                                    }).pause();
                                };
                                self.fire();
                            });
                        }, wait: function (callback) {
                            var self = this;
                            $.ui.loader.x3929d2bc13ed28a1.success(function () {
                                callback && callback();
                            });
                        }
                    }
                }, m000120: {
                    id: 'm000120', animation: {
                        init: function (parent) {
                            var self = this, base = parent.parent.parent,
                                that = base.that.find('.item[data-id="' + parent.id + '"]');
                            self.that = that;
                            self.parent = parent;
                            self.timeline = new $.ui.animator.timeline();
                            that.length && self.wait(function () {
                                self.timeline.build = function () {
                                    self.timeline.from({
                                        target: that.find('.displayer'),
                                        duration: 1,
                                        params: {
                                            delay: self.delay,
                                            ease: Cubic.easeInOut,
                                            opacity: 0,
                                            scale: .3,
                                            y: '150%',
                                        },
                                        position: '-=.0'
                                    }).staggerFrom({
                                        target: $(that.find('.product1, .product2, .product3').get().reverse()),
                                        duration: 1,
                                        params: {opacity: 0, scale: .5, right: '-=25%', top: '-=25%',},
                                        stagger: .25,
                                        position: '-=.55'
                                    }).from({
                                        target: that.find('.caption'),
                                        duration: .8,
                                        params: {ease: Expo.easeOut, left: '-100%',},
                                        position: '-=.55'
                                    }).addLabel("idle").addCallback({
                                        callback: function () {
                                            self.onPlayEnd && self.onPlayEnd();
                                        }, position: '-=.6'
                                    }).pause();
                                };
                                self.fire();
                            });
                        }
                    }
                }, m000028: {
                    id: 'm000028', animation: {
                        init: function (parent) {
                            var self = this, base = parent.parent.parent,
                                that = base.that.find('.item[data-id="' + parent.id + '"]');
                            self.that = that;
                            self.parent = parent;
                            self.timeline = new $.ui.animator.timeline();
                            that.length && self.wait(function () {
                                self.timeline.build = function () {
                                    self.timeline.from({
                                        target: that.find('.house'),
                                        duration: 1.5,
                                        params: {delay: self.delay, opacity: 0, scale: 2,},
                                        position: '-=.0'
                                    }).fromTo({
                                        target: that.find('.displayer'),
                                        duration: 1,
                                        from: {rotationY: 60, scale: .1, opacity: .0, transformPerspective: 600},
                                        to: {rotationY: 360, scale: 1, opacity: 1, transformPerspective: 1200},
                                        position: '-=1'
                                    }).from({
                                        target: that.find('.caption'),
                                        duration: 1,
                                        params: {ease: Circ.easeOut, alpha: 0, scale: 2},
                                        position: '-=.3'
                                    }).addLabel("idle").addCallback({
                                        callback: function () {
                                            self.onPlayEnd && self.onPlayEnd();
                                        }, position: '-=.8'
                                    }).pause();
                                };
                                self.fire();
                            });
                        }
                    }
                }, m000110: {
                    id: 'm000110', animation: {
                        init: function (parent) {
                            var self = this, base = parent.parent.parent,
                                that = base.that.find('.item[data-id="' + parent.id + '"]');
                            self.that = that;
                            self.parent = parent;
                            self.timeline = new $.ui.animator.timeline();
                            that.length && self.wait(function () {
                                var text = that.find('.caption').find('.title, .type, .name'),
                                    subject = new SplitText(text, {type: 'chars'});
                                self.timeline.build = function () {
                                    self.timeline.fromTo({
                                        target: that.find('.displayer'),
                                        duration: 1,
                                        from: {
                                            rotationX: 45,
                                            scale: .1,
                                            opacity: .0,
                                            z: -600,
                                            transformPerspective: 600
                                        },
                                        to: {
                                            delay: self.delay,
                                            rotationX: -360,
                                            scale: 1,
                                            opacity: 1,
                                            z: -0,
                                            transformPerspective: 600
                                        },
                                        position: '-=1'
                                    }).from({
                                        target: that.find('.caption'),
                                        duration: 1,
                                        params: {ease: Expo.easeOut, left: '-100%'},
                                        position: '-=.2'
                                    }).staggerFromTo({
                                        target: subject.chars,
                                        duration: .6,
                                        from: {ease: Expo.easeOut, color: '#fff'},
                                        to: {ease: Expo.easeOut, color: '#daa520'},
                                        stagger: .020,
                                        position: '-=.5'
                                    }).staggerTo({
                                        target: subject.chars,
                                        duration: .6,
                                        params: {ease: Expo.easeOut, color: '#fff',},
                                        stagger: .020,
                                        position: '-=.7'
                                    }).addLabel("idle").addCallback({
                                        callback: function () {
                                            self.onPlayEnd && self.onPlayEnd();
                                        }, position: '-=1'
                                    }).pause();
                                };
                                self.fire();
                            });
                        }, wait: function (callback) {
                            var self = this;
                            $.ui.loader.x3929d2bc13ed28a1.success(function () {
                                callback && callback();
                            });
                        }
                    }
                },
            }
        }, about: {
            id: 'about', init: function (parent, callback) {
                var self = this, that = $('#' + self.id), banner = that.find('>.banner'),
                    description = banner.find('>.description'), info = that.find('> .info'),
                    brief = info.find('>.brief'), scrollbox = info.find('.scrollbox');
                self.parent = parent;
                self.that = that;
                self.banner = banner;
                self.description = description;
                self.info = info;
                self.brief = brief;
                self.scrollbox = scrollbox;
                self.route();
                self.resize();
                self.scrollbar();
                that.visible();
                self.animation.init(self);
                self.fadeshow.init(self);
                self.onInit(callback);
            }, isActive: function () {
                var self = this;
                return self.parent.isUnit('about');
            }, getUri: function () {
                var self = this;
                return $.ui.pages.common.navigator.getUri(self.id);
            }, title: function () {
                var self = this;
                return self.that.children('.heading').text() || null;
            }, route: function () {
                var self = this;
                sewii.router.routing.on(self.id, function (event) {
                    $.ui.pages.common.navigator.go(event.route.unit, {enterTiming: .65, event: event});
                });
            }, onPreload: function (event) {
                var self = this, routing = event;
                $.ui.pages.common.preloader.load({
                    id: self.id, routing: routing, callback: function (event) {
                        var preloader = this, isInited = self.init.called, callback = function () {
                            isInited || preloader.hide();
                            sewii.callback(routing, 'callback');
                        };
                        if (!isInited) {
                            self.init.called = true;
                            self.init($.ui.pages, callback);
                            return;
                        }
                        callback();
                    }
                });
            }, onEnter: function (event) {
                var self = this;
                sewii.history.title(self.title(), -1);
                $.ui.tracker.sendPageView();
                self.animation.play();
                self.fadeshow.play();
            }, onLeave: function (event) {
                var self = this;
                self.animation.pause();
                self.fadeshow.pause();
            }, onExeunt: function (event) {
                var self = this;
                self.animation.restore();
                self.fadeshow.restore();
                if (self.scrollbarInstance) {
                    self.scrollbarInstance.update();
                }
            }, onInit: function (callback) {
                var self = this;
                callback && self.wait(function () {
                    $.ui.animator.requestAnimationFrame(function () {
                        callback && callback();
                    });
                });
            }, wait: function (callback) {
                var self = this, loaders = [$.ui.loader.x3929d2bc13ed28a1, $.ui.loader.x43563f9b64cce748];
                sewii.loader.response(loaders).success(function () {
                    callback && callback();
                });
            }, scrollbar: function () {
                var self = this;
                $.ui.loader.x43563f9b64cce748.success(function () {
                    self.scrollbarInstance = self.scrollbox.scrollbar({
                        resize: false,
                        hideScrollbar: true
                    }).data('plugin_scrollbar');
                });
            }, resize: function () {
                var self = this, footer = $('#footer');
                $(window).resized({
                    callback: function () {
                        var clientWidth = $(window).width(), clientHeight = $(window).height(),
                            bannerHeight = self.banner.height(), footerHeight = footer.outerHeight(),
                            scrollableHeight = self.scrollbox.find('.scrollable').height(),
                            infoHeight = clientHeight - bannerHeight - footerHeight,
                            infoSpacing = (parseInt(self.info.css('border-spacing')) || 0) * 2, offsetHeight = 3;
                        self.that.toggleClass('mini-size', clientWidth <= 1280 || clientHeight <= 700);
                        self.that.toggleClass('nano-size', clientWidth <= 850);
                        self.info.css('height', infoHeight).visible();
                        var textHeight = infoHeight - infoSpacing,
                            currentHeight = scrollableHeight > textHeight ? textHeight : scrollableHeight;
                        self.scrollbox.css('height', currentHeight + offsetHeight);
                        self.scrollbarInstance && self.scrollbarInstance.resize();
                        self.that.find('.text').css('vertical-align', function () {
                            return (scrollableHeight > textHeight) ? 'top' : 'middle';
                        });
                        self.that.find('.scalable > img').fit();
                    }, delay: function () {
                        return !self.isActive();
                    }, now: true,
                });
            }, fadeshow: {
                timeout: 10000, init: function (parent) {
                    var self = this;
                    self.parent = parent;
                    self.timelines = {};
                    self.items = self.workspace().find('.item');
                    self.items.opaque();
                    self.animation.init(self);
                }, next: function (params) {
                    params = params || {};
                    var self = this, speed = 3;
                    if (self.items.length > 1) {
                        var actived = self.items.filter('.actived:first'),
                            current = actived.length ? actived : self.items.eq(-1), next = current.prev();
                        if (!next.length) {
                            next = self.items.eq(-1);
                        }
                        if (params.restore) {
                            clearTimeout(self.timer);
                            self.items.removeClass('actived');
                            self.items.transparent();
                            self.items.eq(-1).opaque();
                            return;
                        }
                        if (self.isPaused()) {
                            return;
                        }
                        next.transparent();
                        self.timelines.out = $.ui.animator.tweener.to({
                            target: current,
                            duration: speed,
                            params: {
                                opacity: 0, onComplete: function () {
                                    current.removeClass('actived');
                                }
                            }
                        });
                        self.timelines['in'] = $.ui.animator.tweener.fromTo({
                            target: next,
                            duration: speed,
                            from: {opacity: 0,},
                            to: {
                                opacity: 1, onComplete: function () {
                                    next.addClass('actived');
                                    clearTimeout(self.timer);
                                    self.timer = setTimeout(function () {
                                        self.isPaused() || self.next();
                                    }, self.timeout);
                                }
                            }
                        });
                        setTimeout(function () {
                            self.isPaused() || self.animation.play();
                        }, speed * .45);
                    }
                }, restore: function () {
                    var self = this;
                    sewii.each(self.timelines, function (i, timeline) {
                        timeline.pause(0);
                    });
                    self.next({restore: true});
                    self.animation.restore();
                }, play: function () {
                    var self = this;
                    sewii.each(self.timelines, function (i, timeline) {
                        timeline.pause(0);
                    });
                    self.workspace().removeClass('paused');
                    clearTimeout(self.timer);
                    self.timer = setTimeout(function () {
                        self.next();
                    }, self.timeout);
                    self.animation.play(1300);
                }, pause: function () {
                    var self = this;
                    self.workspace().addClass('paused');
                    clearTimeout(self.timer);
                    sewii.each(self.timelines, function (i, timeline) {
                        timeline.pause();
                    });
                    self.animation.pause();
                }, isPaused: function () {
                    var self = this;
                    return self.workspace().hasClass('paused');
                }, workspace: function (selector) {
                    var self = this, container = self.parent.banner;
                    if (selector) container = container.find(selector);
                    return container;
                }, animation: {
                    init: function (parent) {
                        var self = this;
                        self.parent = parent;
                        if (self.isSupport()) {
                            self.timeline = new $.ui.animator.timeline().to({
                                target: self.parent.workspace('.items'),
                                duration: 70,
                                params: {scale: 1.2},
                            }).pause();
                        }
                    }, play: function (delay) {
                        var self = this;
                        self.isSupport() && setTimeout(function () {
                            self.times = (self.times || 0) + 1;
                            if (self.times % 2 === 0) {
                                self.parent.isPaused() || self.timeline.reverse();
                            } else {
                                self.parent.isPaused() || self.timeline.play();
                            }
                        }, delay || 0);
                    }, pause: function () {
                        var self = this;
                        if (!self.isSupport()) return;
                        self.timeline.pause();
                    }, restore: function () {
                        var self = this;
                        if (!self.isSupport()) return;
                        self.timeline.pause(0);
                        self.times = 0;
                    }, isSupport: function () {
                        return sewii.env.browser.webkit || sewii.env.browser.firefox || sewii.env.browser.opera;
                    }
                }
            }, animation: {
                init: function (parent) {
                    var self = this;
                    self.parent = parent;
                    self.animate();
                    self.brief.init(self);
                    self.description.init(self);
                }, animate: function () {
                    var self = this;
                    self.timeline = new $.ui.animator.timeline().from({
                        target: self.parent.banner,
                        duration: .8,
                        params: {ease: Expo.easeInOut, right: '-100%', opacity: 0,},
                        position: '-=.0'
                    }).from({
                        target: self.parent.description,
                        duration: .8,
                        params: {ease: Circ.easeInOut, right: '100%', opacity: 0,},
                        position: '-=.5'
                    }).from({
                        target: self.parent.scrollbox,
                        duration: .8,
                        params: {ease: Expo.easeInOut, right: '-100%', opacity: 0,},
                        position: '-=.5'
                    }).from({
                        target: self.parent.brief.find('span[data-id="first"]'),
                        duration: .8,
                        params: {ease: Expo.easeOut, left: '-150%',},
                        position: '-=.2'
                    }).addCallback({
                        callback: function () {
                            self.brief.play();
                        }, position: '+=.2'
                    }).addLabel("idle").pause();
                }, play: function () {
                    var self = this;
                    self.timeline.play();
                    self.brief.play();
                    self.description.resume();
                }, pause: function () {
                    var self = this;
                    self.timeline.pause();
                    self.brief.pause();
                    self.description.pause();
                }, restore: function () {
                    var self = this;
                    self.timeline.pause(0);
                    self.brief.restore();
                    self.description.restore();
                }, brief: {
                    init: function (parent) {
                        var self = this;
                        self.parent = parent;
                        self.wait(function () {
                            self.animate();
                        });
                    }, wait: function (callback) {
                        var self = this, loaders = [$.ui.loader.x3929d2bc13ed28a1];
                        sewii.loader.response(loaders).success(function () {
                            callback && callback();
                        });
                    }, animate: function () {
                        var self = this, items = self.parent.parent.brief.find('span[data-id]'),
                            what = new SplitText(items.filter('[data-id="first"]'), {type: 'words, chars'}),
                            can = new SplitText(items.filter('[data-id="second"]'), {type: 'chars'}),
                            any = new SplitText(items.filter('[data-id="last"]'), {type: 'chars'}), color = {
                                defaults: '#000',
                                original: items.css('color'),
                                reverse: items.last().children().first().css('color'),
                            };
                        items.slice(0, 2).find('div').each(function () {
                            var character = $(this);
                            if (character.text() === 'o') {
                                character.addClass('icon-logo');
                            }
                        });
                        self.timeline = new $.ui.animator.timeline({
                            repeat: -1,
                            repeatDelay: 5,
                            yoyo: true
                        }).addCallback({
                            callback: function () {
                                $.ui.pages.about.animation.description.replay();
                            }, position: '+=0'
                        }).staggerTo({
                            target: what.chars,
                            duration: .6,
                            params: {ease: Expo.easeOut, color: color.reverse || color.defaults,},
                            stagger: .06,
                            position: '+=1.5'
                        }).staggerTo({
                            target: what.chars,
                            duration: .6,
                            params: {ease: Expo.easeOut, color: color.original || color.defaultsl,},
                            stagger: .06,
                            position: '-=.4'
                        }).staggerTo({
                            target: what.words,
                            duration: .4,
                            params: {ease: Expo.easeOut, top: '-=20',},
                            stagger: .35,
                            position: '-=.1'
                        }).staggerTo({
                            target: what.words,
                            duration: .4,
                            params: {ease: Back.easeOut, color: color.original || color.defaults,},
                            stagger: .35,
                            position: '-=1.3'
                        }).to({
                            target: items.eq(0),
                            duration: 1,
                            params: {top: '-=60', opacity: 0},
                            position: '-=.0'
                        }).staggerFrom({
                            target: can.chars,
                            duration: .8,
                            params: {
                                ease: Circ.easeOut, opacity: 0, onStart: function () {
                                }
                            },
                            stagger: .15,
                            position: '-=.5'
                        }).pause();
                        var cans = items.slice(2), max = cans.length - 1;
                        cans.each(function (index) {
                            var item = $(this);
                            if (index < max) {
                                self.timeline.from({
                                    target: item,
                                    duration: .6,
                                    params: {ease: Expo.easeOut, scale: 0, opacity: 0},
                                    position: index === 0 ? '-=.2' : '-=.1'
                                }).to({
                                    target: item,
                                    duration: .6,
                                    params: {
                                        ease: Expo.easeInOut, scale: 1.2, opacity: 0, onStart: function () {
                                        }
                                    },
                                    position: '-=.0'
                                });
                            } else {
                                self.timeline.fromTo({
                                    target: item,
                                    duration: 1,
                                    from: {opacity: 0, scale: 5},
                                    to: {ease: Elastic.easeInOut, rotation: 360, opacity: 1, scale: 1},
                                    position: '-=.0'
                                }).staggerTo({
                                    target: can.chars,
                                    duration: .8,
                                    to: {
                                        ease: Back.easeInOut,
                                        opacity: 1,
                                        scale: 1,
                                        rotationX: -360,
                                        y: 10,
                                        transformOrigin: '0% 30% 5'
                                    },
                                    stagger: .030,
                                    position: '-=.1'
                                }).staggerTo({
                                    target: any.chars,
                                    duration: .8,
                                    to: {ease: Expo.easeInOut, rotation: 3600},
                                    stagger: .10,
                                    position: '-=.3'
                                }).addCallback({
                                    callback: function () {
                                        $.ui.pages.about.animation.description.replay();
                                    }, position: '+=0'
                                });
                            }
                        });
                    }, play: function () {
                        var self = this;
                        self.wait(function () {
                            var parentCurrentTime = self.parent.timeline.time(),
                                parentIdleTime = self.parent.timeline.getLabelTime('idle');
                            if (parentCurrentTime >= parentIdleTime) {
                                self.timeline.play();
                            }
                        });
                    }, pause: function () {
                        var self = this;
                        self.wait(function () {
                            self.timeline.pause();
                        });
                    }, restore: function () {
                        var self = this;
                        self.wait(function () {
                            self.timeline.pause(0);
                        });
                    }
                }, description: {
                    init: function (parent) {
                        var self = this;
                        self.parent = parent;
                        self.animate();
                    }, animate: function () {
                        var self = this;
                        self.timeline = new $.ui.animator.timeline().from({
                            target: self.parent.parent.description,
                            duration: 1,
                            params: {ease: Circ.easeInOut, rotationY: 360},
                            position: '-=1'
                        }).pause();
                    }, replay: function () {
                        var self = this;
                        self.timeline.play(0);
                    }, resume: function () {
                        var self = this;
                        if (self.timeline.progress() > 0) {
                            self.timeline.play();
                        }
                    }, play: function () {
                        var self = this;
                        self.timeline.play();
                    }, pause: function () {
                        var self = this;
                        self.timeline.pause();
                    }, restore: function () {
                        var self = this;
                        self.timeline.pause(0);
                    }
                }
            }
        }, team: {
            id: 'team', init: function (parent, callback) {
                var self = this, that = $('#' + self.id);
                self.parent = parent;
                self.that = that;
                self.route();
                self.resize();
                self.that.visible();
                self.people.init(self);
                self.person.init(self);
                self.onInit(callback);
            }, isActive: function () {
                var self = this;
                return self.parent.isUnit('team');
            }, getUri: function () {
                var self = this;
                return $.ui.pages.common.navigator.getUri(self.id);
            }, title: function () {
                var self = this;
                return self.that.children('.heading').text() || null;
            }, route: function () {
                var self = this;
                sewii.router.routing.on(self.id, function (event) {
                    $.ui.pages.common.navigator.go(event.route.unit, {enterTiming: 1, event: event});
                });
            }, onPreload: function (event) {
                var self = this, routing = event;
                $.ui.pages.common.preloader.load({
                    id: self.id, routing: routing, callback: function (event) {
                        var preloader = this, isInited = self.init.called, callback = function () {
                            isInited || preloader.hide();
                            sewii.callback(routing, 'callback');
                        };
                        if (!isInited) {
                            self.init.called = true;
                            self.init($.ui.pages, callback);
                            return;
                        }
                        callback();
                    }
                });
            }, onCome: function (event) {
                var self = this, id = event.route.name, isExixts = self.person.page(id) !== -1;
                if (id && isExixts) {
                    self.people.preview();
                }
            }, onEnter: function (event) {
                var self = this, id = event.route.name, current = self.person.currently(),
                    isExixts = self.person.page(id) !== -1, trace = function (s) {
                        return;
                        console.log(event.route.unit, s);
                    };
                sewii.history.title(self.title(), -1);
                sewii.history.title(self.person.title(id), -2);
                $.ui.tracker.sendPageView();
                self.people.play();
                self.person.play();
                if (id && isExixts) {
                    if (self.person.isOpening()) {
                        if (current === id) return trace(1);
                        self.person.force('open', function () {
                            self.person.turn(id);
                        }) && trace(2);
                    } else if (self.person.isClosing()) {
                        self.person.force('close', function () {
                            self.person.open(id);
                        }) && trace(3);
                    } else if (self.person.isOpened()) {
                        if (current === id) return trace(4);
                        self.person.turn(id) || trace(5);
                    } else {
                        self.person.open(id) || trace(6);
                    }
                } else {
                    if (self.person.isOpening()) {
                        self.person.force('open', function () {
                            self.person.close() || trace(7);
                        });
                    } else if (self.person.isClosing()) {
                        self.person.doNothing || trace(8);
                    } else if (self.person.isOpened()) {
                        self.person.close() || trace(9);
                    }
                }
            }, onLeave: function (event) {
                var self = this;
                self.people.pause();
                self.person.pause();
            }, onExeunt: function (event) {
                var self = this;
                self.people.restore();
                self.person.restore();
            }, onInit: function (callback) {
                var self = this;
                callback && self.wait(function () {
                    $.ui.animator.requestAnimationFrame(function () {
                        callback && callback();
                    });
                });
            }, wait: function (callback) {
                var self = this,
                    loaders = [$.ui.loader.x42fc68082cbd6361, $.ui.loader.xd3224c65496a99fc, $.ui.loader.xaf85c8a24c4358b4];
                sewii.loader.response(loaders).success(function () {
                    callback && callback();
                });
            }, resize: function () {
                var self = this;
                $(window).resized({
                    callback: function () {
                        var clientWidth = $(window).width(), clientHeight = $(window).height();
                        self.that.toggleClass('mini-size', clientWidth <= 1024 || clientHeight <= 700);
                        self.that.toggleClass('micro-size', clientWidth <= 800);
                        self.that.toggleClass('nano-size', clientWidth <= 600);
                        self.that.find('.scalable.background > img').fit();
                    }, delay: function () {
                        return !self.isActive();
                    }, now: true,
                });
            }, people: {
                init: function (parent) {
                    var self = this, that = parent.that.find('.people'), scrollable = that.find('.scrollable'),
                        space = scrollable.find('.items'), items = space.find('.item');
                    self.parent = parent;
                    self.that = that;
                    self.scrollable = scrollable;
                    self.space = space;
                    self.items = items;
                    self.reflect();
                    self.resize();
                    self.prev();
                    self.next();
                    self.click();
                    self.hover();
                    self.mousewheel();
                    self.keyboard();
                    self.touch();
                    self.that.visible();
                    self.that.disableSelection();
                    self.animation.init(self);
                }, reflect: function () {
                    var self = this;
                    if (sewii.env.browser.msie <= 8) {
                        return;
                    }
                    self.items.each(function () {
                        var image = $(this).find('img'), url = image.attr('src'), width = image.width(),
                            height = image.height(), reflect = $('<div class="reflect"/>'),
                            layer = $('<div class="layer"/>'), offsetBottom = -15, bottom = -(height + offsetBottom);
                        if (false && sewii.env.browser.webkit) {
                            return $(this).css({'-webkit-box-reflect': 'below ' + offsetBottom + 'px ' + '-webkit-gradient(linear, 0 0, 0 100%, from(transparent), color-stop(.2, transparent), to(rgba(3, 3, 3, .25)))'});
                        }
                        reflect.appendTo(this).css({
                            transform: 'scaleY(-1)',
                            background: 'url(' + url + ') bottom center no-repeat',
                            backgroundSize: width + 'px ' + height + 'px',
                            position: 'absolute',
                            left: 0,
                            bottom: bottom,
                            zIndex: 1,
                            width: width,
                            height: height,
                            opacity: .15
                        });
                        layer.appendTo(this).css({
                            position: 'absolute',
                            left: 0,
                            bottom: bottom,
                            zIndex: 2,
                            width: width,
                            height: height,
                        });
                    });
                }, resize: function () {
                    var self = this;
                    self.resizeTimes = 0;
                    $(window).resized({
                        callback: function () {
                            var scrollableWidth = self.scrollable.width(),
                                currentItems = self.items.slice(self.left().index()),
                                lastIndex = currentItems.length - 1, innerWidth = 0;
                            currentItems.each(function (index) {
                                innerWidth += self.size(this);
                                var isOverBound = innerWidth > scrollableWidth;
                                if (isOverBound) self.hide(this); else self.show(this);
                                if (index === lastIndex) {
                                    (function () {
                                        var callee = arguments.callee, prev = self.left().prev();
                                        if (prev.length) {
                                            var currentWidth = 0;
                                            self.items.slice(self.left().index()).each(function () {
                                                currentWidth += self.size(this);
                                            });
                                            if (scrollableWidth > prev.width() + currentWidth) {
                                                self.show(prev);
                                                callee();
                                            }
                                        }
                                    }());
                                }
                            });
                            self.center();
                            self.resizeTimes++;
                        }, delay: function () {
                            return !self.parent.isActive();
                        }, now: true,
                    });
                }, center: function (duration) {
                    var self = this, scrollableWidth = self.scrollable.width(), visibleWidth = (function () {
                        var size = 0;
                        self.items.filter(':not(.hide)').each(function () {
                            size += self.size(this);
                        });
                        return size;
                    }()), invisibleWidth = (function () {
                        var size = 0;
                        self.items.each(function () {
                            if (!$(this).is('.hide')) return false; else size += self.size(this);
                        });
                        return size;
                    }());
                    var center = scrollableWidth / 2 - (invisibleWidth + visibleWidth / 2),
                        offsetSize = (parseInt(self.items.css('marginLeft')) / 2) || 0;
                    self.space.stop().animate({left: center - offsetSize}, {
                        duration: sewii.ifSet(duration, 600),
                        easing: $.ui.animator.easing('easeOutCirc'),
                    });
                    if (!self.that.hasClass('bound-navigation')) {
                        self.that.bind('navigation', function () {
                            if (self.that.hasClass('animation-ready')) {
                                self.that.find('.prev')[self.left().prev().length ? 'visible' : 'invisible']();
                                self.that.find('.next')[self.right().next().length ? 'visible' : 'invisible']();
                            }
                        }).addClass('bound-navigation');
                    } else {
                        self.that.trigger('navigation');
                    }
                }, prev: function () {
                    var self = this, button = self.that.find('.prev'), boundClass = 'bound';
                    if (!button.hasClass(boundClass)) {
                        button.addClass(boundClass).attr('href', function (i, href) {
                            return $(this).data('href', href) && null;
                        }).click(function () {
                            if (self.that.hasClass('animation-ready')) {
                                var left = self.left(), right = self.right();
                                if (left.prev().length) {
                                    self.show(left.prev(), 0);
                                    self.hide(right, right.width());
                                    self.center();
                                }
                            }
                            return false;
                        });
                    } else button.click();
                }, next: function () {
                    var self = this, button = self.that.find('.next'), boundClass = 'bound';
                    if (!button.hasClass(boundClass)) {
                        button.addClass(boundClass).attr('href', function (i, href) {
                            return $(this).data('href', href) && null;
                        }).click(function () {
                            if (self.that.hasClass('animation-ready')) {
                                var left = self.left(), right = self.right();
                                if (right.next().length) {
                                    self.hide(left, -left.width());
                                    self.show(right.next(), 0);
                                    self.center();
                                }
                            }
                            return false;
                        });
                    } else button.click();
                }, click: function () {
                    var self = this, button = self.items.find('a'), boundClass = 'bound';
                    if (!button.hasClass(boundClass)) {
                        button.addClass(boundClass).attr('href', function (i, href) {
                            return $(this).data('href', href) && null;
                        }).click(function () {
                            var uri = $(this).data('href');
                            if (uri && !self.parent.person.isBusy()) {
                                sewii.router.routing.go(uri);
                            }
                            return false;
                        });
                    } else button.click();
                }, hover: function () {
                    var self = this, boundClass = 'bound', button = self.items;
                    if (!button.hasClass(boundClass)) {
                        button.addClass(boundClass).bind({
                            mouseenter: function (e) {
                                if (!self.that.hasClass('animation-ready') || !$(e.target).is('a')) {
                                    return;
                                }
                                $.ui.animator.tweener.to({
                                    target: this,
                                    duration: 1,
                                    params: {ease: Elastic.easeOut, ease: Expo.easeOut, scale: 1.35, bottom: '-50px'},
                                });
                            }, mouseleave: function (e) {
                                $.ui.animator.tweener.to({
                                    target: this,
                                    duration: 1,
                                    params: {
                                        ease: Bounce.easeOut,
                                        ease: Elastic.easeOut,
                                        ease: Expo.easeOut,
                                        scale: 1,
                                        bottom: 0
                                    },
                                });
                            },
                        });
                    } else button.mouseenter();
                }, mousewheel: function () {
                    var self = this;
                    $.ui.loader.xd3224c65496a99fc.success(function () {
                        $(self.that).mousewheel(function (e, delta) {
                            if (self.parent.person.isBusy()) return;
                            if (self.parent.person.isOpened()) return;
                            delta >= 1 && self.prev();
                            delta <= -1 && self.next();
                        });
                        return false;
                    });
                }, keyboard: function () {
                    var self = this;
                    $(window).bind('directionKeyDown', function (e, info) {
                        if ($.ui.pages.team.person.isOpened()) return;
                        if (!self.parent.isActive()) return;
                        (info.isLeft || info.isUp) && self.prev();
                        (info.isRight || info.isDown) && self.next();
                    });
                }, touch: function () {
                    var self = this;
                    sewii.env.support.touchable && $.ui.loader.xaf85c8a24c4358b4.success(function () {
                        Hammer(self.space.get(0), {swipeVelocityX: .1,}).on('swipe', function (event) {
                            var next = event.gesture.direction === 'left', prev = event.gesture.direction === 'right',
                                velocity = Math.abs(Math.ceil(event.gesture.velocityX * 1));
                            prev && $.each(new Array(velocity), function () {
                                self.prev();
                            });
                            next && $.each(new Array(velocity), function () {
                                self.next();
                            });
                        });
                    });
                }, hide: function (item, moving, duration) {
                    var self = this, size = $(item).width();
                    $(item).stop().animate({
                        left: sewii.ifSet(moving, size),
                        opacity: 0,
                    }, {
                        duration: self.resizeTimes === 0 ? 0 : sewii.ifSet(duration, 600),
                        easing: $.ui.animator.easing('easeOutCirc'),
                        complete: function () {
                            self.center();
                        }
                    }).addClass('hide');
                }, show: function (item, moving, duration) {
                    var self = this, size = $(item).width();
                    $(item).stop().animate({
                        left: sewii.ifSet(moving, 0),
                        opacity: 1,
                    }, {
                        duration: self.resizeTimes === 0 ? 0 : sewii.ifSet(duration, 600),
                        easing: $.ui.animator.easing('easeOutCirc'),
                        complete: function () {
                            self.center();
                        }
                    }).removeClass('hide');
                }, size: function (item) {
                    var width = $(item).width(), marginLeft = parseInt($(item).css('marginLeft')) || 0,
                        marginRight = parseInt($(item).css('marginRight')) || 0;
                    return width + marginLeft + marginRight;
                }, left: function () {
                    return this.visible().first();
                }, right: function () {
                    return this.visible().last();
                }, visible: function () {
                    return this.items.filter(':not(.hide)');
                }, invisible: function () {
                    return this.items.filter('.hide');
                }, preview: function () {
                    var self = this;
                    if (self.animation.preview()) {
                        self.items.stop(true);
                    }
                }, play: function () {
                    var self = this;
                    self.animation.play();
                }, pause: function () {
                    var self = this;
                    self.animation.pause();
                    self.items.stop();
                }, restore: function () {
                    var self = this;
                    self.items.stop(true);
                    $.each(self.items, function () {
                        var left = self.left(), right = self.right();
                        if (left.prev().length) {
                            self.show(left.prev(), 0, 0);
                            self.hide(right, right.width(), 0);
                            self.center(0);
                        }
                    });
                    self.animation.restore();
                }, animation: {
                    init: function (parent) {
                        var self = this, caption = parent.parent.that.find('.caption');
                        self.parent = parent;
                        self.caption = caption;
                        self.animate();
                    }, animate: function () {
                        var self = this, callee = arguments.callee;
                        callee.windowSize = $(window).width() * $(window).height();
                        $.ui.animator.tweener.set(self.parent.items, {scale: 0, bottom: -100});
                        $.ui.animator.tweener.set(self.parent.items.filter('.hide'), {scale: 1, bottom: 0});
                        self.timeline = new $.ui.animator.timeline().addLabel({
                            label: 'showing',
                            position: 0
                        }).staggerTo({
                            target: sewii.shuffle($(self.parent.items.not('.hide').get().reverse())),
                            duration: .6,
                            to: {ease: Back.easeOut, scale: 1, bottom: 0},
                            stagger: .5,
                            position: '+=.1'
                        }).fromTo({
                            target: self.caption, duration: sewii.env.support.$3d ? 5 : .8, from: (function () {
                                var params = {rotationX: -90, z: -100, opacity: .5,};
                                if (!sewii.env.support.$3d) {
                                    params = {marginTop: -$(window).height(),};
                                }
                                return params;
                            }()), to: (function () {
                                var params = {
                                    ease: Elastic.easeOut,
                                    rotationX: 0,
                                    z: 0,
                                    opacity: 1,
                                    transformPerspective: 400,
                                    transformOrigin: '50% 0 0',
                                    onStart: function () {
                                        self.parent.that.addClass('animation-ready').trigger('navigation');
                                    }
                                };
                                if (!sewii.env.support.$3d) {
                                    params = {marginTop: 0, ease: Expo.easeOut, onStart: params.onStart,};
                                }
                                return params;
                            }()), position: sewii.env.support.$3d ? '-=.0' : '-=.2',
                        }).pause();
                    }, preview: function () {
                        var self = this, currentTime = self.timeline.time(),
                            showingTime = self.timeline.getLabelTime('showing');
                        if (currentTime <= showingTime) {
                            self.restore();
                            self.timeline.play(-.001, false);
                            return true;
                        }
                        return false;
                    }, play: function () {
                        var self = this;
                        if (self.timeline.progress() <= 0) {
                            var windowSize = $(window).width() * $(window).height();
                            if (self.animate.windowSize !== windowSize) {
                                self.animate();
                            }
                        }
                        self.timeline.play();
                    }, pause: function () {
                        var self = this;
                        self.timeline.pause();
                    }, restore: function () {
                        var self = this;
                        self.animate();
                        self.parent.that.removeClass('animation-ready');
                        self.parent.that.find('.prev, .next').invisible();
                    },
                }
            }, person: {
                EVENT_OPENED: 'team.person.opened', EVENT_CLOSED: 'team.person.closed', init: function (parent) {
                    var self = this, that = $('#team-person'), space = that.find('.items'), items = space.find('.item');
                    self.parent = parent;
                    self.that = that;
                    self.space = space;
                    self.items = items;
                    self.timelines = {};
                    self.order();
                    self.turnable();
                }, order: function () {
                    var self = this;
                    self.listing = {};
                    self.parent.people.items.children('a').each(function (index) {
                        var people = $(this), id = people.data('id'), page = index + 1,
                            person = self.items.filter('[data-id="' + id + '"]');
                        if (!person.length) {
                            people.remove();
                            person.remove();
                            return true;
                        }
                        self.listing[id] = {target: person.clone(), page: page};
                    });
                    self.items.remove();
                    $.each(self.listing, function (id, person) {
                        self.space.append(person.target);
                    });
                    self.items = self.space.find('.item');
                }, turnable: function () {
                    var self = this;
                    $.ui.loader.x42fc68082cbd6361.success(function () {
                        self.space.turn({
                            display: 'single',
                            backface: self.that.find('.inner .brief .title .chinese').css('color') || '#fff',
                            cornerSize: 250,
                            duration: 1500,
                            elevation: 600,
                            page: 1,
                            corners: (function () {
                                return sewii.env.support.touchable ? 'tl bl br l r' : 'tl bl tr br l r';
                            }()),
                            when: (function () {
                                self.helper = {
                                    page: {
                                        current: function () {
                                            return self.space.turn('page');
                                        }, prev: function () {
                                            var page = this.current() - 1;
                                            return self.space.turn('hasPage', page) ? page : -1;
                                        }, next: function () {
                                            var page = this.current() + 1;
                                            return self.space.turn('hasPage', page) ? page : -1;
                                        }, follow: function (point) {
                                            return self.helper.isLeftPoint(point) ? self.helper.page.prev() : self.helper.page.next();
                                        }
                                    }, isLeftPoint: function (point) {
                                        point = point || {};
                                        return (point.x < self.that.width() / 2) ? true : false;
                                    }, isRightPoint: function (point) {
                                        point = point || {};
                                        return !this.isLeftPoint() ? true : false;
                                    }, visible: function (page) {
                                        self.items.eq(page - 1).visible();
                                    }, invisible: function (page) {
                                        self.items.eq(page - 1).invisible();
                                    }, invisibleWithoutPage: function (page) {
                                        var index = page - 1, others = self.items.not(':eq(' + index + ')'),
                                            myself = self.items.eq(index);
                                        others.invisible();
                                        myself.visible();
                                    }, button: function () {
                                        var current = self.space.turn('page'), prev = current - 1, next = current + 1;
                                        if (prev === 0) {
                                            prev = -1;
                                        }
                                        self.that.find('.prev')[self.space.turn('hasPage', prev) ? 'visible' : 'invisible']();
                                        self.that.find('.next')[self.space.turn('hasPage', next) ? 'visible' : 'invisible']();
                                    }, zIndex: {
                                        elevate: function () {
                                            this.originalThatIndex = sewii.ifSet(this.originalThatIndex, self.that.index());
                                            self.space.css('overflow', 'visible');
                                            $('#layout').append(self.that);
                                        }, restore: function () {
                                            self.space.css('overflow', 'hidden');
                                            if (sewii.isSet(this.originalThatIndex)) {
                                                var index = this.originalThatIndex - 1;
                                                if (index === -1) self.that.prependTo('#team'); else self.parent.that.children().eq(index).after(self.that);
                                            }
                                        }
                                    }
                                };
                                $(document).mousedown(function (event) {
                                    if (!self.isBusy() && self.isOpened() && self.parent.isActive()) {
                                        if (!$(event.target).parents('#' + self.that.attr('id')).length) {
                                            self.helper.zIndex.restore();
                                        }
                                    }
                                });
                                return {
                                    pressed: function (event, point) {
                                        self.helper.visible(self.helper.page.follow(point));
                                        self.helper.zIndex.elevate();
                                    }, released: function (event, point) {
                                        self.helper.invisible(self.helper.page.follow(point));
                                        self.helper.zIndex.restore();
                                    }, turning: function (event, page, view) {
                                        self.helper.visible(page);
                                        self.helper.zIndex.elevate();
                                        self.helper.button();
                                        if (!self.isBusy() && self.isOpened() && self.parent.isActive()) {
                                        }
                                    }, turned: function (event, page, view) {
                                        self.helper.invisibleWithoutPage(page);
                                        self.helper.zIndex.restore();
                                        self.helper.button();
                                    }
                                };
                            }())
                        });
                        self.background();
                        self.resize();
                        self.next();
                        self.prev();
                        self.close();
                        self.mousewheel();
                        self.keyboard();
                        self.touch();
                    });
                }, background: function () {
                    var self = this;
                    self.items.each(function () {
                        var background = $('<div class="background"/>'), skew = $('<div class="skew"/>');
                        background.append(skew).appendTo(this);
                    });
                }, resize: function () {
                    var self = this;
                    $(window).resized({
                        callback: function () {
                            var clientWidth = self.that.width(), clientHeight = self.that.height();
                            self.that.toggleClass('long-size', clientWidth <= 1440);
                            self.that.toggleClass('medium-size', clientWidth <= 1366);
                            self.that.toggleClass('micro-size', clientWidth <= 800);
                            self.that.toggleClass('nano-size', clientHeight <= 600);
                            self.space.turn('size', clientWidth, clientHeight).turn('resize');
                            self.that.find('.scalable.background > img').fit();
                        }, delay: function () {
                            return !self.parent.isActive();
                        }, now: true,
                    });
                }, prev: function () {
                    var self = this, button = self.that.find('.prev'), boundClass = 'bound';
                    if (!button.hasClass(boundClass)) {
                        button.addClass(boundClass).attr('href', function (i, href) {
                            return $(this).data('href', href) && null;
                        }).click(function () {
                            if (self.isBusy()) return false;
                            if (!self.that.is('.peeled')) return false;
                            var page = self.space.turn('page'),
                                prev = self.parent.people.items.eq(page - 2).children('a');
                            if (prev.length && page > 1) {
                                var uri = prev.data('href');
                                uri && sewii.router.routing.go(uri);
                            }
                            return false;
                        });
                    } else button.click();
                }, next: function () {
                    var self = this, button = self.that.find('.next'), boundClass = 'bound';
                    if (!button.hasClass(boundClass)) {
                        button.addClass(boundClass).attr('href', function (i, href) {
                            return $(this).data('href', href) && null;
                        }).click(function () {
                            if (self.isBusy()) return false;
                            if (!self.that.is('.peeled')) return false;
                            var page = self.space.turn('page'), next = self.parent.people.items.eq(page).children('a');
                            if (next.length) {
                                var uri = next.data('href');
                                uri && sewii.router.routing.go(uri);
                            }
                            return false;
                        });
                    } else button.click();
                }, turn: function (to) {
                    var self = this;
                    to = /^\d+$/.test(to) ? to : self.page(to);
                    if (to !== -1 && self.space.turn('hasPage', to)) {
                        self.space.turn('page', to);
                        self.helper.button();
                    }
                }, open: function (id) {
                    var self = this, callee = arguments.callee, page = self.page(id), person = self.items.eq(page - 1);
                    if (self.that.is('.busy')) return; else self.that.addClass('busy opening');
                    if (self.space.turn('hasPage', page)) {
                        self.space.turn('disable', false);
                        self.space.turn('page', page);
                        self.space.turn('stop');
                        self.space.turn('disable', true);
                        self.helper.invisibleWithoutPage(page);
                        self.helper.button();
                    } else return;
                    $.ui.animator.tweener.set(person.find('.background'), {scale: 1});
                    $.ui.animator.tweener.set(person.find('.inner'), {scaleY: 1, opacity: 1});
                    $.ui.animator.tweener.set(self.that.find('.prev, .next, .close'), {opacity: 1});
                    self.that.removeClass('peeled');
                    self.timelines.open = new $.ui.animator.timeline().fromTo({
                        target: self.that,
                        duration: .8,
                        from: {scale: 1, left: '-100%', display: 'block'},
                        to: {ease: Expo.easeInOut, left: '0%',},
                        position: '-=.8'
                    }).fromTo({
                        target: person.find('.background .skew'),
                        duration: .6,
                        from: {width: 10, height: 0, marginLeft: -5, marginTop: 0, left: '50%', top: 0, skewX: 60,},
                        to: {ease: Expo.easeInOut, height: '100%',},
                        position: '-=.1'
                    }).to({
                        target: person.find('.background .skew'),
                        duration: .6,
                        params: {
                            ease: Expo.easeInOut,
                            width: '80%',
                            marginLeft: -(person.find('.background ').width() * .8 / 2),
                        },
                        position: '-=.1'
                    }).to({
                        target: person.find('.background .skew'),
                        duration: .8,
                        params: {ease: Expo.easeInOut, skewX: -60, marginLeft: 0, left: '15%',},
                        position: '-=.1'
                    }).staggerFrom({
                        target: (function () {
                            var elements = person.find('.inner .brief').children().get();
                            elements.push(person.find('.picture img'));
                            return elements;
                        }()),
                        duration: .7,
                        params: {
                            ease: Expo.easeOut,
                            marginLeft: -Math.max(self.that.width(), person.find('.inner').width())
                        },
                        stagger: .2,
                        position: '-=.4'
                    }).from({
                        target: self.that.find('.prev'),
                        duration: .8,
                        params: {ease: Circ.easeOut, opacity: 0, left: '-100%',},
                        position: '-=1'
                    }).from({
                        target: self.that.find('.next, .close'),
                        duration: .8,
                        params: {ease: Circ.easeOut, opacity: 0, right: '-100%',},
                        position: '-=.8'
                    }).addCallback({
                        callback: function () {
                            self.space.turn('disable', false);
                            self.that.addClass('opened');
                            self.that.removeClass('busy opening');
                            self.parent.that.addClass('opened');
                        }
                    }).addCallback({
                        callback: function () {
                            if (!self.isBusy()) {
                                var animate = self.space.turn('hasPage', page + 1) ? 'br' : 'bl';
                                self.space.turn('peel', animate);
                                self.that.addClass('peeled');
                            }
                            sewii.event.fire(self.EVENT_OPENED);
                        }, position: '+=.2'
                    });
                }, close: function (force) {
                    var self = this, button = self.that.find('.close'), boundClass = 'bound';
                    if (!button.hasClass(boundClass)) {
                        button.addClass(boundClass).attr('href', function (i, href) {
                            return $(this).data('href', href) && null;
                        }).click(function (event, params) {
                            params = params || {};
                            var page = self.space.turn('page'),
                                person = self.that.find('.page-wrapper[page="' + page + '"]').find('.item');
                            if (!params.force) {
                                if (!params.external && !self.that.is('.busy') && sewii.url.param('name')) {
                                    var uri = self.parent.getUri();
                                    uri && sewii.router.routing.go(uri);
                                    return false;
                                }
                                if (self.that.is('.busy')) return; else self.that.addClass('busy closing');
                            }
                            self.space.turn('disable', false).turn('stop').turn('peel', false).turn('stop').turn('disable', true);
                            self.helper.invisibleWithoutPage(page);
                            self.timelines.close = new $.ui.animator.timeline().to({
                                target: self.that.find('.prev, .next, .close'),
                                duration: 1,
                                params: {opacity: 0},
                                position: '+=.1'
                            }).to({
                                target: person.find('.background .skew'),
                                duration: .8,
                                params: {
                                    ease: Expo.easeInOut,
                                    width: person.find('.inner').width(),
                                    height: (function () {
                                        var inner = person.find('.inner'),
                                            pictureHeight = inner.find('.picture img').height(), birefHeight = 0;
                                        inner.find('.brief').children().each(function () {
                                            birefHeight += $(this).height();
                                        });
                                        var innerHeight = Math.max(pictureHeight, birefHeight) / 2;
                                        inner.data('innerHeight', innerHeight);
                                        return innerHeight + 'px';
                                    }()),
                                    left: '50%',
                                    top: '50%',
                                    marginLeft: -(person.find('.inner').width() / 2),
                                    marginTop: -(person.find('.inner').data('innerHeight') / 2),
                                    skewX: 0,
                                },
                                position: 0
                            }).to({
                                target: person.find('.inner'),
                                duration: .8,
                                params: {ease: Expo.easeInOut, scaleY: 0, opacity: 0},
                                position: '-=.2'
                            }).to({
                                target: person.find('.background'),
                                duration: .8,
                                params: {
                                    ease: Expo.easeInOut, scaleX: .8, scaleY: 0, onComplete: function () {
                                        self.that.hide();
                                        $.ui.animator.tweener.set(self.that.find('.prev, .next, .close'), {opacity: 1});
                                        $.ui.animator.tweener.set(person.find('.inner'), {scaleY: 1, opacity: 1});
                                        $.ui.animator.tweener.set(person.find('.background'), {scale: 1});
                                        $.ui.animator.tweener.set(person.find('.background .skew'), {
                                            left: '15%',
                                            top: 0,
                                            width: '80%',
                                            height: '100%',
                                            marginLeft: 0,
                                            marginTop: 0,
                                            skewX: -60,
                                        });
                                        self.that.removeClass('opened');
                                        self.that.removeClass('busy closing');
                                        self.parent.that.removeClass('opened');
                                        sewii.event.fire(self.EVENT_CLOSED);
                                    }
                                },
                                position: '-=.6'
                            });
                            if (params.force) {
                                $.isFunction(params.force) ? params.force() : self.force('close');
                            }
                            return false;
                        });
                        $(window).keydown(function (e) {
                            if (e.keyCode === 27) {
                                if (!self.isOpened()) return;
                                if (!self.parent.isActive()) return;
                                button.trigger('click');
                                return false;
                            }
                        });
                    } else button.trigger('click', {external: true, force: force});
                }, mousewheel: function () {
                    var self = this, balance = 4, prevTimes = 0, nextTimes = 0, timer = null;
                    $.ui.loader.xd3224c65496a99fc.success(function () {
                        self.that.mousewheel(function (event, delta) {
                            if (self.isBusy()) return;
                            if (!self.isOpened()) return;
                            var isPrev = delta >= 1, isNext = delta <= -1;
                            clearTimeout(timer);
                            timer = setTimeout(function () {
                                prevTimes = nextTimes = 0;
                            }, 60);
                            if (isPrev && prevTimes++ >= balance) {
                                prevTimes = 0;
                                self.prev();
                            }
                            if (isNext && nextTimes++ >= balance) {
                                nextTimes = 0;
                                self.next();
                            }
                            return false;
                        });
                    });
                }, keyboard: function () {
                    var self = this;
                    $(window).bind('directionKeyDown', function (e, info) {
                        if (self.isBusy()) return;
                        if (!self.isOpened()) return;
                        if (!self.parent.isActive()) return;
                        (info.isLeft || info.isUp) && self.prev();
                        (info.isRight || info.isDown) && self.next();
                    });
                }, touch: function () {
                    var self = this;
                    sewii.env.support.touchable && $.ui.loader.xaf85c8a24c4358b4.success(function () {
                        Hammer(self.that.get(0), {swipeVelocityX: .3,}).on('swipe', function (event) {
                            var next = event.gesture.direction === 'left', prev = event.gesture.direction === 'right';
                            prev && self.prev();
                            next && self.next();
                        });
                    });
                }, isOpening: function () {
                    var self = this;
                    return self.that.is('.opening');
                }, isOpened: function () {
                    var self = this;
                    return self.that.is('.opened');
                }, isClosing: function () {
                    var self = this;
                    return self.that.is('.closing');
                }, isClosed: function () {
                    var self = this;
                    return !self.isOpened();
                }, isBusy: function () {
                    var self = this;
                    return self.that.is('.busy');
                }, currently: function () {
                    var self = this, page = self.space.turn('page'),
                        current = self.parent.people.items.eq(page - 1).children('a');
                    return current.data('id');
                }, title: function (id) {
                    var self = this, current = self.items.filter('[data-id="' + id + '"]'),
                        name = current.find('.brief .name').text(), position = current.find('.brief .chinese').text();
                    return name && position ? name + ' / ' + position : null;
                }, page: function (id) {
                    var self = this;
                    return self.listing[id] ? self.listing[id].page : -1;
                }, force: function (action, callback, reset) {
                    var self = this, args = arguments, callee = args.callee;
                    switch (String(action).toLowerCase()) {
                        case'open':
                            if (self.timelines.open) {
                                callback && sewii.event.off(self.EVENT_OPENED).once(self.EVENT_OPENED, callback);
                                reset && self.timelines.open.play(0);
                                self.timelines.open.play(-.001, false);
                            }
                            break;
                        case'close':
                            if (self.timelines.close) {
                                callback && sewii.event.off(self.EVENT_CLOSED).once(self.EVENT_CLOSED, callback);
                                reset && self.timelines.close.play(0);
                                self.timelines.close.play(-.001, false);
                            } else self.close(function () {
                                callee.apply(self, Array.prototype.slice.call(args));
                            });
                            break;
                    }
                    return self;
                }, play: function () {
                    var self = this;
                    self.isOpening() && self.timelines.open && self.timelines.open.play();
                    self.isClosing() && self.timelines.close && self.timelines.close.play();
                }, pause: function () {
                    var self = this;
                    self.isOpening() && self.timelines.open && self.timelines.open.pause();
                    self.isClosing() && self.timelines.close && self.timelines.close.pause();
                    self.helper.zIndex.restore();
                }, restore: function () {
                    var self = this;
                    if (self.timelines.open) {
                        if (self.isOpened() || self.isBusy()) {
                            self.force('open', function () {
                                self.force('close');
                            });
                        }
                    }
                },
            }
        }, environment: {
            id: 'environment', init: function (parent, callback) {
                var self = this, that = $('#' + self.id), caption = that.find('.caption'),
                    pictures = that.find('.pictures'), scrollable = pictures.find('.scrollable'),
                    items = scrollable.find('.items');
                self.parent = parent;
                self.that = that;
                self.caption = caption;
                self.pictures = pictures;
                self.scrollable = scrollable;
                self.items = items;
                self.route();
                self.resize();
                self.that.visible();
                self.lightbox.init(self);
                self.draggable.init(self);
                self.animation.init(self);
                self.onInit(callback);
            }, isActive: function () {
                var self = this;
                return self.parent.isUnit('environment');
            }, getUri: function () {
                var self = this;
                return $.ui.pages.common.navigator.getUri(self.id);
            }, title: function () {
                var self = this;
                return self.that.children('.heading').text() || null;
            }, route: function () {
                var self = this;
                sewii.router.routing.on(self.id, function (event) {
                    $.ui.pages.common.navigator.go(event.route.unit, {enterTiming: .35, event: event});
                });
            }, onPreload: function (event) {
                var self = this, routing = event;
                $.ui.pages.common.preloader.load({
                    id: self.id, routing: routing, callback: function (event) {
                        var preloader = this, isInited = self.init.called, callback = function () {
                            isInited || preloader.hide();
                            sewii.callback(routing, 'callback');
                        };
                        if (!isInited) {
                            self.init.called = true;
                            self.init($.ui.pages, callback);
                            return;
                        }
                        callback();
                    }
                });
            }, onCome: function (event) {
                var self = this, id = sewii.stringify(event.route.photo), target = self.lightbox.plane(id),
                    isExixts = target.length >= 1;
                if (id && isExixts) {
                    self.animation.preview();
                }
            }, onEnter: function (event) {
                var self = this, id = sewii.stringify(event.route.photo), target = self.lightbox.plane(id),
                    isExixts = target.length >= 1, current = sewii.stringify(self.lightbox.curretly().data('id')),
                    trace = function (s) {
                        return;
                        console.log(event.route.unit, s);
                    };
                if (event.skip && event.isInternal) {
                    return trace('skip');
                }
                sewii.history.title(self.title(), -1);
                sewii.history.title(self.lightbox.title(id), -2);
                $.ui.tracker.sendPageView();
                self.animation.play();
                if (id && isExixts) {
                    if (self.lightbox.isOpening()) {
                        if (current === id) return trace(1);
                        self.lightbox.force('open', function () {
                            self.lightbox.seek(id);
                        }) && trace(2);
                    } else if (self.lightbox.isClosing()) {
                        self.lightbox.force('close', function () {
                            self.lightbox.open(id);
                        }) && trace(3);
                    } else if (self.lightbox.isOpened()) {
                        if (current === id) return trace(4);
                        self.lightbox.seek(id) || trace(5);
                    } else {
                        self.lightbox.open(id) || trace(6);
                    }
                } else {
                    if (self.lightbox.isOpening()) {
                        self.lightbox.force('open', function () {
                            self.lightbox.force('close') && trace(7);
                        });
                    } else if (self.lightbox.isClosing()) {
                        self.lightbox.doNothing || trace(8);
                    } else if (self.lightbox.isOpened()) {
                        self.lightbox.close() || trace(9);
                    }
                }
            }, onLeave: function (event) {
                var self = this;
                self.animation.pause();
            }, onExeunt: function (event) {
                var self = this;
                self.animation.restore();
            }, onInit: function (callback) {
                var self = this;
                callback && self.wait(function () {
                    $.ui.animator.requestAnimationFrame(function () {
                        callback && callback();
                    });
                });
            }, wait: function (callback) {
                var self = this,
                    loaders = [$.ui.loader.x3929d2bc13ed28a1, $.ui.loader.xc8633bedc25cf4b3, $.ui.loader.xcf21b1118fc03a3f, $.ui.loader.xd3224c65496a99fc, $.ui.loader.xaf85c8a24c4358b4];
                sewii.loader.response(loaders).success(function () {
                    callback && callback();
                });
            }, resize: function () {
                var self = this;
                $(window).resized({
                    callback: function () {
                        var clientWidth = $(this).width(), clientHeight = $(this).height();
                        self.that.toggleClass('super-size', clientWidth <= 1600);
                        self.that.toggleClass('medium-size', clientWidth <= 1440);
                        self.that.toggleClass('small-size', clientWidth <= 1280);
                        self.that.toggleClass('mini-size', clientWidth <= 1024);
                        self.that.toggleClass('micro-size', clientWidth <= 800);
                        self.that.toggleClass('nano-size', clientWidth <= 600);
                        self.pictures.find('> .inner').each(function (i, value) {
                            var inner = $(this), structure = self.that.find('.structure'),
                                heightRatio = self.scrollable.data('heightRatio');
                            if (!heightRatio) {
                                inner.css('height', inner.height());
                                self.scrollable.data('heightRatio', heightRatio = self.scrollable.height() / inner.height());
                            }
                            self.scrollable.height(0);
                            inner.css('height', 'auto');
                            self.scrollable.css('height', inner.height() * heightRatio);
                        });
                        self.pictures.find('.items').each(function () {
                            var me = this, parentHeight = $(me).parent().height(),
                                heightRatio = $(me).height() / parentHeight;
                            $(me).find('.item').each(function () {
                                var my = this,
                                    width = parseInt(clientWidth * self.draggable.itemWidthRatio * heightRatio),
                                    marginHorizontal = parseInt(clientWidth * self.draggable.itemSpacingRatio);
                                $(my).css({width: width, marginLeft: marginHorizontal, marginRight: marginHorizontal});
                                $(my).find('img').fit();
                            });
                        });
                        self.that.find('.scalable.background > img').fit();
                    }, delay: function () {
                        return !self.isActive();
                    }, now: true,
                });
            }, animation: {
                init: function (parent) {
                    var self = this, that = parent.that;
                    self.parent = parent;
                    $.ui.loader.x3929d2bc13ed28a1.success(function () {
                        self.animate();
                    });
                }, animate: function () {
                    var self = this, summary = new SplitText(self.parent.caption.find('.summary'), {type: 'chars'});
                    self.timeline = new $.ui.animator.timeline().addLabel({
                        label: 'showing',
                        position: 0
                    }).from({
                        target: self.parent.caption.find('.title'),
                        duration: 1,
                        params: {ease: Expo.easeInOut, left: '-100%'},
                        position: '-=.0'
                    }).from({
                        target: self.parent.scrollable,
                        duration: 2,
                        params: {
                            ease: Circ.easeOut, left: (function () {
                                var width = 0;
                                self.parent.scrollable.children().each(function () {
                                    width += $(this).width();
                                });
                                return -width * screen.width / $(window).width();
                            }()), onStart: function () {
                                self.parent.draggable.animate();
                            }
                        },
                        position: '-=.8'
                    }).staggerFrom({
                        target: summary.chars,
                        duration: .8,
                        params: {ease: Back.easeInOut, opacity: 0, scale: 5},
                        stagger: .020,
                        position: '-=1.5'
                    }).pause();
                }, preview: function () {
                    var self = this, currentTime = self.timeline.time(),
                        showingTime = self.timeline.getLabelTime('showing');
                    if (currentTime <= showingTime) {
                        self.timeline.pause(-.001);
                        self.parent.draggable.preview();
                    }
                }, play: function () {
                    var self = this;
                    self.timeline.play();
                    self.parent.draggable.play();
                    self.parent.lightbox.play();
                }, pause: function () {
                    var self = this;
                    self.timeline.pause();
                    self.parent.draggable.pause();
                    self.parent.lightbox.pause();
                }, restore: function () {
                    var self = this;
                    self.timeline.pause(0);
                    self.parent.draggable.restore();
                    self.parent.lightbox.restore();
                },
            }, draggable: {
                itemWidthRatio: .30, itemSpacingRatio: .08, init: function (parent) {
                    var self = this;
                    self.parent = parent;
                    self.timelines = {};
                    self.wait(function () {
                        self.bind();
                        self.resize();
                        self.click();
                        self.mousewheel();
                        self.keyboard();
                    });
                }, bind: function () {
                    var self = this;
                    self.frontGallery().addClass('grab');
                    self.parent.items.disableSelection().each(function (i) {
                        var me = $(this), draggable;
                        draggable = Draggable.create(me, {
                            type: 'left',
                            throwProps: true,
                            zIndexBoost: false,
                            force3D: true,
                            edgeResistance: .6,
                            bounds: {minX: self.minX(me), maxX: self.maxX(me),},
                            onClick: function (e) {
                                $(e.target).trigger('click');
                            },
                            onPress: function (e) {
                                $(e.target).trigger('mousedown', e.button);
                            },
                            onDragEnd: function (e) {
                                $(e.target).trigger('mousemove');
                                self.onDragEnd.call(this, e, self);
                            },
                            onDragStart: function (e) {
                                self.onDragStart.call(this, e, self);
                            },
                            onDrag: function (e) {
                                self.onDrag.call(this, e, self);
                            },
                            onThrowUpdate: function (e) {
                                this.vars.onDrag.call(this, e);
                            }
                        })[0];
                        draggable.setPosition = function (position, params) {
                            params = params || {};
                            var me = this, callee = arguments.callee, duration = sewii.ifSet(params.duration, 0),
                                easing = sewii.ifSet(params.easing, Circ.easeOut);
                            self.position(position);
                            self.timelines.to = new $.ui.animator.timeline().to({
                                target: me.target,
                                duration: duration,
                                params: {
                                    ease: easing, left: position, overwrite: true, onUpdate: function () {
                                        me.x = parseInt($(this.target).css('left')) || 0;
                                        me.vars.onDrag.call(me);
                                        params.onUpdate && params.onUpdate.call(this);
                                    }, onComplete: function () {
                                        me.update();
                                        params.onComplete && params.onComplete.call(this);
                                    }
                                }
                            });
                        };
                        draggable.disable();
                    });
                }, resize: function () {
                    var self = this;
                    $(window).resized({
                        callback: function () {
                            var frontGallery = self.frontGallery(), draggable = Draggable.get(frontGallery),
                                relativeX = self.clientWidth() * (draggable.ratioX || 0);
                            draggable.setPosition(relativeX);
                            draggable.applyBounds({minX: self.minX(frontGallery), maxX: self.maxX(frontGallery),});
                        }, delay: function () {
                            return !self.parent.isActive();
                        }, now: true,
                    });
                }, wait: function (callback) {
                    var self = this, loaders = [$.ui.loader.xc8633bedc25cf4b3, $.ui.loader.xcf21b1118fc03a3f];
                    sewii.loader.response(loaders).success(function () {
                        callback && callback();
                    });
                }, position: function (value) {
                    var self = this, callee = arguments.callee;
                    if (sewii.isSet(value)) {
                        callee.position = parseFloat(value);
                        return self;
                    }
                    return callee.position || 0;
                }, keyboard: function () {
                    var self = this;
                    $(window).bind('directionKeyDown', function (e, info) {
                        if (self.isBusy()) return;
                        if (self.parent.lightbox.isOpened()) return;
                        if (!self.parent.isActive()) return;
                        (info.isLeft || info.isUp) && self.forward();
                        (info.isRight || info.isDown) && self.backward();
                    });
                }, mousewheel: function () {
                    var self = this;
                    $.ui.loader.xd3224c65496a99fc.success(function () {
                        $(self.parent.scrollable).mousewheel(function (e, delta, force) {
                            if (!force && (self.isBusy() || self.parent.lightbox.isOpened())) return;
                            var frontGallery = self.frontGallery(), draggable = Draggable.get(frontGallery),
                                min = self.minX(frontGallery), max = self.maxX(frontGallery),
                                volume = parseInt(frontGallery.children().width() / 2),
                                position = self.position() + (delta || 0) * volume;
                            position = (position < min) ? min : position;
                            position = (position > max) ? max : position;
                            draggable.setPosition(position, {duration: 1});
                            return false;
                        });
                    });
                }, forward: function () {
                    var self = this;
                    $(self.parent.scrollable).trigger('mousewheel', [1, true]);
                }, backward: function () {
                    var self = this;
                    $(self.parent.scrollable).trigger('mousewheel', [-1, true]);
                }, click: function () {
                    var self = this, toListenClick = true;
                    (function () {
                        self.parent.items.find('.item a').attr('data-clickable', 'false').attr('href', function (i, href) {
                            return $(this).data('href', href) && null;
                        }).bind({
                            mousedown: function (e, button) {
                                toListenClick = e.button !== 2 && button !== 2;
                            }, mousemove: (function () {
                                var x, y;
                                return function (e) {
                                    if (e.clientX === x && e.clientY === y) return;
                                    x = e.clientX;
                                    y = e.clientY;
                                    toListenClick = false;
                                };
                            })(), click: function (e) {
                                var me = $(this), callee = arguments.callee, frontGallery = self.frontGallery(),
                                    backGallery = self.backGallery(), currentItems = me.parents('.items'),
                                    lastTriggeredTime = arguments.callee.lastTriggeredTime;
                                callee.lastTriggeredTime = e.timeStamp;
                                if (lastTriggeredTime) {
                                    var diff = e.timeStamp - lastTriggeredTime;
                                    if (diff <= 100) return false;
                                }
                                if (toListenClick) {
                                    if (currentItems.get(0) === frontGallery.get(0)) {
                                        if (self.parent.lightbox.isSupport()) {
                                            var uri = $(this).data('href');
                                            if (uri && !self.parent.lightbox.isBusy()) {
                                                sewii.router.routing.go(uri);
                                            }
                                        }
                                    }
                                    if (currentItems.get(0) === backGallery.get(0)) {
                                        backGallery.trigger('click');
                                    }
                                }
                                return false;
                            }
                        });
                        self.parent.items.bind({
                            mousedown: function (e, button) {
                                toListenClick = e.button !== 2 && button !== 2;
                            }, mousemove: (function () {
                                var x, y;
                                return function (e) {
                                    if (e.clientX === x && e.clientY === y) return;
                                    x = e.clientX;
                                    y = e.clientY;
                                    toListenClick = false;
                                };
                            })(), click: function (e, params) {
                                params = params || {};
                                var me = $(this), callee = arguments.callee, frontGallery = self.frontGallery(),
                                    backGallery = self.backGallery(),
                                    lastTriggeredTime = arguments.callee.lastTriggeredTime;
                                callee.lastTriggeredTime = e.timeStamp;
                                if (lastTriggeredTime) {
                                    var diff = e.timeStamp - lastTriggeredTime;
                                    if (diff <= 100) return false;
                                }
                                if ((toListenClick && me.is('.items')) || params.reset) {
                                    if (!params.reset) {
                                        if (self.parent.pictures.is('.busy')) return false; else self.parent.pictures.addClass('busy');
                                    }
                                    var speedIn = 1.2, speedOut = 1.0, outRatio = 1.5,
                                        frontDraggable = Draggable.get(frontGallery),
                                        backDraggable = Draggable.get(backGallery), frontX = frontGallery.offset().left,
                                        backX = backGallery.offset().left,
                                        heightRation = backGallery.height() / frontGallery.height(),
                                        heightPercent = 100 * heightRation, middlePercent = (100 - heightPercent) / 2;
                                    if (params.reset) {
                                        if (frontGallery.is('.gallery1')) {
                                            self.parent.pictures.removeClass('busy');
                                            return false;
                                        }
                                        speedIn = speedOut = 0;
                                    }
                                    frontDraggable.disable();
                                    self.timelines.frontOut = new $.ui.animator.timeline().to({
                                        target: frontGallery,
                                        duration: speedOut,
                                        params: {
                                            overwrite: true,
                                            ease: Expo.easeIn,
                                            left: '+=' + frontGallery.width() * outRatio,
                                            onComplete: function () {
                                                frontGallery.css({
                                                    height: heightPercent + '%',
                                                    top: middlePercent + '%',
                                                    zIndex: 1
                                                }).find('.item').css('width', self.clientWidth() * self.itemWidthRatio * heightRation).find('.scalable img').fit();
                                                self.timelines.frontIn = new $.ui.animator.timeline().to({
                                                    target: frontGallery,
                                                    duration: speedIn,
                                                    params: {
                                                        overwrite: true,
                                                        ease: Quart.easeOut,
                                                        left: backX,
                                                        onComplete: function () {
                                                            frontDraggable.applyBounds({
                                                                minX: self.minX(frontGallery),
                                                                maxX: self.maxX(frontGallery),
                                                            });
                                                            frontGallery.removeClass('grab');
                                                        },
                                                    }
                                                });
                                            },
                                        }
                                    });
                                    backDraggable.disable();
                                    self.timelines.backOut = new $.ui.animator.timeline().to({
                                        target: backGallery,
                                        duration: speedOut,
                                        params: {
                                            overwrite: true,
                                            ease: Expo.easeIn,
                                            left: '-=' + backGallery.width() * outRatio,
                                            onComplete: function () {
                                                backGallery.css({
                                                    height: '100%',
                                                    top: '0%',
                                                    zIndex: 2
                                                }).find('.item').css('width', self.clientWidth() * self.itemWidthRatio).find('.scalable img').fit();
                                                self.timelines.backIn = new $.ui.animator.timeline().to({
                                                    target: backGallery,
                                                    duration: speedIn,
                                                    params: {
                                                        overwrite: true,
                                                        ease: Quart.easeOut,
                                                        left: frontX,
                                                        onComplete: function () {
                                                            backDraggable.applyBounds({
                                                                minX: self.minX(backGallery),
                                                                maxX: self.maxX(backGallery),
                                                            }).enable();
                                                            backGallery.addClass('grab');
                                                            self.parent.pictures.removeClass('busy');
                                                        },
                                                    }
                                                });
                                            },
                                        }
                                    });
                                }
                                return false;
                            }
                        });
                    }());
                }, onDrag: function (e, self) {
                    var backGallery = self.backGallery(), syncX = this.x * .5 - (backGallery.width() * .15);
                    backGallery.css('left', syncX);
                    this.ratioX = this.x / self.clientWidth();
                }, onDragStart: function (e, self) {
                    $(this.target).addClass('grabbing');
                }, onDragEnd: function (e, self) {
                    $(this.target).removeClass('grabbing');
                    self.position(this.endX);
                }, backGallery: function () {
                    var self = this, gallery1 = self.gallery(1), gallery2 = self.gallery(2);
                    return self.frontGallery().is('.gallery1') ? gallery2 : gallery1;
                }, frontGallery: function () {
                    var self = this, gallery1 = self.gallery(1), gallery2 = self.gallery(2),
                        zIndexGallery1 = parseInt(gallery1.css('zIndex')),
                        zIndexGallery2 = parseInt(gallery2.css('zIndex'));
                    return (zIndexGallery1 >= zIndexGallery2) ? gallery1 : gallery2;
                }, gallery: function (number) {
                    return this.parent.items.filter('.gallery' + number);
                }, minX: function (gallery) {
                    return -gallery.width() + (this.itemWidth(gallery) / 2);
                }, maxX: function (gallery) {
                    return this.clientWidth() - (this.itemWidth(gallery) / 2);
                }, itemWidth: function (gallery) {
                    return gallery.width() / gallery.children().length;
                }, clientWidth: function () {
                    return $(window).width();
                }, isBusy: function () {
                    var self = this;
                    return self.parent.pictures.is('.busy');
                }, animate: function (duration) {
                    var self = this, frontGallery = self.frontGallery(), draggable = Draggable.get(frontGallery),
                        item = frontGallery.children().first(), width = item.width() || 0,
                        marginLeft = parseInt(item.css('marginLeft')) || 0, center = -((width + marginLeft) / 2),
                        left = self.minX(frontGallery);
                    Draggable.get(self.frontGallery()).enable();
                    draggable.setPosition(left);
                    draggable.setPosition(center, {duration: sewii.ifSet(duration, 1.2)});
                }, preview: function () {
                    var self = this;
                    self.animate(0);
                }, play: function () {
                    var self = this;
                    Draggable.get(self.frontGallery()).enable();
                    sewii.each(self.timelines, function (i, timeline) {
                        timeline.play();
                    });
                }, pause: function () {
                    var self = this;
                    Draggable.get(self.frontGallery()).disable();
                    Draggable.get(self.backGallery()).disable();
                    sewii.each(self.timelines, function (i, timeline) {
                        timeline.pause();
                    });
                }, restore: function () {
                    var self = this;
                    self.parent.items.trigger('click', {reset: true});
                },
            }, lightbox: {
                EVENT_OPENED: 'environment.lightbox.opened',
                EVENT_CLOSED: 'environment.lightbox.closed',
                init: function (parent) {
                    if (!this.isSupport()) return;
                    var self = this, widthRatio = .4, heightRatio = .5, perspective = 800, caliber = 360,
                        isHorizontal = true, that = parent.that.find('.lightbox'), space = that.find('.planes'),
                        planes = space.find('.plane'), clientWidth = $(window).width(),
                        clientHeight = $(window).height(), width = clientWidth * widthRatio,
                        height = clientHeight * heightRatio, rotation = isHorizontal ? 'rotationY' : 'rotationX',
                        panelSize = isHorizontal ? width : height, panelCount = planes.length,
                        theta = caliber / panelCount,
                        radius = Math.round((panelSize / 2) / Math.tan(Math.PI / panelCount));
                    if (self.isSupportMax3d()) {
                        new $.ui.animator.timeline().to({
                            target: that,
                            duration: 0,
                            params: {perspective: perspective,},
                        }).to({target: space, duration: 0, params: {transformStyle: 'preserve-3d',},});
                    } else if (self.isSupportMin3d()) {
                        new $.ui.animator.timeline().to({
                            target: space,
                            duration: 0,
                            params: {perspective: perspective,},
                        });
                    }
                    (function () {
                        (function () {
                            var listing = [];
                            parent.items.find('a[data-id]').each(function (index) {
                                var item = $(this), id = item.data('id'),
                                    plane = planes.filter('[data-id="' + id + '"]');
                                if (!plane.length) {
                                    item.remove();
                                    plane.remove();
                                    return true;
                                }
                                listing.push({id: id, target: plane.clone(),});
                            });
                            planes.remove();
                            $.each(listing, function (index, plane) {
                                space.append(plane.target);
                            });
                            planes = space.find('.plane');
                        }());
                        planes.each(function (i) {
                            $(this).prependTo($(this).parent());
                        });
                        planes = space.find('.plane');
                    }());
                    planes.each(function (i) {
                        var plane = this, angle = theta * i, params = {
                            width: width,
                            height: height,
                            marginLeft: -(width / 2),
                            marginTop: -(height / 2),
                            transformOrigin: '50%, 50%, ' + radius + 'px',
                            onComplete: function () {
                                $(this.target).find('img').fit();
                            }
                        };
                        params[rotation] = angle;
                        new $.ui.animator.timeline().to({target: plane, duration: 0, params: params,});
                    });
                    self.parent = parent;
                    self.that = that;
                    self.space = space;
                    self.planes = planes;
                    self.isHorizontal = isHorizontal, self.widthRatio = widthRatio;
                    self.heightRatio = heightRatio;
                    self.rotation = rotation;
                    self.caliber = caliber;
                    self.theta = theta;
                    self.radius = radius;
                    self.timelines = {};
                    self.rotate();
                    self.resize();
                    self.mousewheel();
                    self.keyboard();
                    self.touch();
                    self.click();
                    self.close();
                },
                rotate: function (steps, params) {
                    params = params || {};
                    var self = this, timeline = params.timeline || new $.ui.animator.timeline(),
                        timeline3d = new $.ui.animator.timeline().pause(), duration = sewii.ifSet(params.duration, .8),
                        position = sewii.ifSet(params.position, 0), transformOrigin = '50%, 50%, ' + self.radius + 'px',
                        from = sewii.ifSet($.extend({transformOrigin: transformOrigin}, params.from || {})),
                        to = $.extend({ease: Circ.easeOut, transformOrigin: transformOrigin}, params.to || {});
                    steps = $.isPlainObject(steps) ? steps : {to: steps};
                    steps.to = sewii.ifSet(steps.to, 0);
                    steps.from = sewii.ifSet(steps.from, 0);
                    if (arguments.length === 0) {
                        steps.to = -(self.planes.length * 10000000);
                        duration = 0;
                    }
                    steps.from && (from[self.rotation] = self.angle(self.theta * steps.from));
                    steps.to && (to[self.rotation] = self.angle(self.theta * steps.to));
                    if (self.isSupportMax3d()) {
                        return timeline.fromTo({
                            target: self.space,
                            duration: duration,
                            from: from,
                            to: to,
                            position: position
                        });
                    }
                    if (self.isSupportMin3d()) {
                        if (sewii.isSet(to[self.rotation]) || sewii.isSet(to.z) || sewii.isSet(from[self.rotation]) || sewii.isSet(from.z)) {
                            self.planes.each(function (i) {
                                var plane = this, angle = self.theta * i, params = {
                                    from: {transformOrigin: from.transformOrigin},
                                    to: {transformOrigin: to.transformOrigin,}
                                };
                                sewii.isSet(to.ease) && (params.to.ease = to.ease);
                                sewii.isSet(to.z) && (params.to.z = to.z);
                                sewii.isSet(to[self.rotation]) && (params.to[self.rotation] = angle + to[self.rotation]);
                                sewii.isSet(from.ease) && (params.from.ease = from.ease);
                                sewii.isSet(from.z) && (params.from.z = from.z);
                                sewii.isSet(from[self.rotation]) && (params.from[self.rotation] = angle + from[self.rotation]);
                                timeline3d.fromTo({
                                    target: plane,
                                    duration: duration,
                                    from: params.from,
                                    to: params.to,
                                    position: 0
                                });
                            });
                            self.timelines['3d'] = sewii.ifSet(self.timelines['3d'], []);
                            self.timelines['3d'].push(timeline3d);
                            delete to[self.rotation], to.z, from[self.rotation], from.z;
                        }
                        return timeline.fromTo({
                            target: self.space,
                            duration: duration,
                            from: from,
                            to: $.extend({}, to, {
                                onStart: function () {
                                    timeline3d.play();
                                    if (to.onStart) {
                                        to.onStart.call(this);
                                    }
                                }
                            }),
                            position: position
                        });
                    }
                },
                resize: function () {
                    var self = this;
                    $(window).resized({
                        callback: function () {
                            if (self.isBusy()) {
                                var me = this, callee = arguments.callee;
                                setTimeout(function () {
                                    callee.call(me);
                                }, 50);
                                return;
                            }
                            var clientWidth = $(window).width(), clientHeight = $(window).height(),
                                width = clientWidth * self.widthRatio, height = clientHeight * self.heightRatio,
                                panelSize = self.isHorizontal ? width : height, panelCount = self.planes.length,
                                theta = self.caliber / panelCount,
                                radius = Math.round((panelSize / 2) / Math.tan(Math.PI / panelCount)), params = {
                                    width: width,
                                    height: height,
                                    marginLeft: -(width / 2),
                                    marginTop: -(height / 2),
                                    transformOrigin: '50%, 50%, ' + radius + 'px',
                                };
                            self.radius = radius;
                            $.ui.animator.tweener.to({
                                target: self.space,
                                duration: 0,
                                params: {transformOrigin: params.transformOrigin},
                            });
                            self.planes.each(function (i) {
                                var me = $(this);
                                $.ui.animator.tweener.to({target: me, duration: 0, params: params,});
                                me.find('img').fit();
                            });
                        }, delay: function () {
                            return !self.parent.isActive();
                        }, now: false,
                    });
                },
                click: function () {
                    var self = this;
                    sewii.env.support.touchable || self.planes.click(function () {
                        var index = $(this).index(), currentIndex = self.index(),
                            lastIndex = self.planes.last().index();
                        if (self.isBusy()) return;
                        if (index - currentIndex === -1 || index - currentIndex === lastIndex) {
                            self.prev();
                        }
                        if (index - currentIndex === 1 || index - currentIndex === -lastIndex) {
                            self.next();
                        }
                        return false;
                    }).disableSelection();
                },
                mousewheel: function () {
                    var self = this;
                    $.ui.loader.xd3224c65496a99fc.success(function () {
                        $(self.that).mousewheel(function (e, delta) {
                            if (self.isBusy()) return;
                            delta >= 1 && self.prev();
                            delta <= -1 && self.next();
                            return false;
                        });
                    });
                },
                keyboard: function () {
                    var self = this;
                    $(window).bind('directionKeyDown', function (e, info) {
                        if (self.isBusy()) return;
                        if (!self.isOpened()) return;
                        if (!self.parent.isActive()) return;
                        (info.isLeft || info.isUp) && self.prev();
                        (info.isRight || info.isDown) && self.next();
                    });
                },
                touch: function () {
                    var self = this;
                    sewii.env.support.touchable && $.ui.loader.xaf85c8a24c4358b4.success(function () {
                        Hammer(self.that.get(0), {swipeVelocityX: .05,}).on('swipe', function (event) {
                            if (self.isBusy()) return;
                            if (!$(event.target).is('.plane')) return;
                            var next = event.gesture.direction === 'right', prev = event.gesture.direction === 'left',
                                velocity = Math.abs(Math.ceil(event.gesture.velocityX * 3));
                            prev && self.prev(velocity);
                            next && self.next(velocity);
                        });
                    });
                },
                prev: function (velocity) {
                    var self = this, index = (self.index() - (velocity || 1)) % self.planes.length,
                        id = self.planes.eq(index).data('id');
                    self.seek(id);
                    self.change(id);
                },
                next: function (velocity) {
                    var self = this, index = (self.index() + (velocity || 1)) % self.planes.length,
                        id = self.planes.eq(index).data('id');
                    self.seek(id);
                    self.change(id);
                },
                seek: function (id) {
                    var self = this, callee = arguments.callee, plane = self.planes.filter('[data-id="' + id + '"]'),
                        target = plane.index(), current = self.index(), angle = current - target,
                        all = self.planes.length;
                    if (Math.abs(angle) > all / 2) {
                        if (angle > 0) angle -= all; else if (angle < 0) angle += all;
                    }
                    self.rotate(angle);
                },
                change: function (id) {
                    var self = this, callee = arguments.callee;
                    clearTimeout(callee.timer);
                    callee.timer = setTimeout(function () {
                        var item = self.parent.items.find('a[data-id="' + id + '"]'), uri = item.data('href');
                        uri && sewii.router.routing.go(uri, {skip: true});
                    }, 300);
                },
                open: function (id) {
                    if (!this.isSupport()) return;
                    var self = this, timeline = new $.ui.animator.timeline(), index = self.plane(id).index(), angle = 0;
                    index = index > -1 ? index : 0;
                    angle = self.theta * -index;
                    if (self.that.is('.busy')) return; else self.that.addClass('busy opening');
                    self.timelines.open = timeline.fromTo({
                        target: self.that,
                        duration: 1.2,
                        from: {scale: 1, scaleX: .1, opacity: .5, display: 'block'},
                        to: {ease: Elastic.easeInOut, scaleX: 1, opacity: 1,},
                        position: '-=.0'
                    });
                    self.rotate({
                        from: -36000 / self.theta + self.index(),
                        to: -3600 / self.theta + self.index()
                    }, {
                        timeline: timeline,
                        position: '-=.0',
                        duration: 1,
                        from: {scale: 1, opacity: 0, z: -100000,},
                        to: {ease: Circ.easeOut, opacity: .5, z: -5000}
                    });
                    self.rotate({to: -360 / self.theta + self.index()}, {
                        timeline: timeline,
                        position: '-=.1',
                        duration: 1,
                        to: {ease: Circ.easeOut, opacity: .7, z: -2000}
                    });
                    self.rotate({
                        to: (function () {
                            var to = angle / self.theta + self.index(), all = self.planes.length;
                            if (Math.abs(to) < all / 2) {
                                to -= all;
                            }
                            return to;
                        }())
                    }, {
                        timeline: timeline,
                        position: '-=.1',
                        duration: 1,
                        to: {ease: Circ.easeOut, opacity: 1, z: 0,}
                    });
                    timeline.staggerFromTo({
                        target: (function () {
                            var img = self.planes.find('img'), prev = self.planes.eq((index - 1) % img.length).index(),
                                next = (index + 1) % img.length, priority = [next, index, prev],
                                order = [img.eq(next), img.eq(index), img.eq(prev)];
                            img.each(function (i) {
                                if ($.inArray(i, priority) === -1) {
                                    order.push(this);
                                }
                            });
                            return order;
                        }()), duration: .5, from: {
                            left: '-1000%', onStart: function () {
                                $(this.target).fit();
                            }
                        }, to: {ease: Expo.easeOut, left: '50%'}, stagger: .10, position: '-=.15'
                    }).addCallback({
                        callback: function () {
                            $.ui.animator.tweener.to({
                                target: self.planes.find('img'),
                                duration: 0,
                                params: {overwrite: true, left: '50%'}
                            });
                            self.that.removeClass('busy opening');
                            self.that.addClass('opened');
                            sewii.event.fire(self.EVENT_OPENED);
                        }, position: '-=1.3'
                    });
                },
                close: function (force) {
                    if (!this.isSupport()) return;
                    var self = this, button = self.that, boundClass = 'bound';
                    if (!button.hasClass(boundClass)) {
                        button.addClass(boundClass).click(function (event, params) {
                            params = params || {};
                            if (!params.force) {
                                if (!$(event.target).is(self.that)) return false;
                                if (!params.external && !self.that.is('.busy') && sewii.url.param('photo')) {
                                    var uri = self.parent.getUri();
                                    uri && sewii.router.routing.go(uri);
                                    return false;
                                }
                                if (self.that.is('.busy')) return false;
                                self.that.addClass('busy closing');
                            }
                            var timeline = new $.ui.animator.timeline();
                            self.timelines.close = timeline.staggerTo({
                                target: (function () {
                                    var img = self.planes.find('img'), index = self.index(),
                                        prevIndex = self.planes.eq((index - 1) % img.length).index(),
                                        nextIndex = (index + 1) % img.length, priority = [prevIndex, index, nextIndex],
                                        order = [img.eq(prevIndex), img.eq(index), img.eq(nextIndex)];
                                    img.each(function (i) {
                                        if ($.inArray(i, priority) === -1) {
                                            order.push(this);
                                        }
                                    });
                                    return order;
                                }()),
                                duration: .6,
                                params: {ease: Expo.easeOut, left: '-100%'},
                                stagger: .10,
                                position: '-=.0'
                            }).addCallback({
                                callback: function () {
                                    $.ui.animator.tweener.set({
                                        target: self.planes.find('img'),
                                        params: {left: '-100%'}
                                    });
                                }, position: '-=1.8'
                            });
                            self.rotate(360 / self.theta + self.index(), {
                                timeline: timeline,
                                position: '-=1.8',
                                to: {
                                    ease: Expo.easeInOut, onStart: function () {
                                        self.planes.find('img').hide();
                                    }
                                }
                            });
                            timeline.to({
                                target: self.space,
                                duration: 1,
                                params: {ease: Circ.easeOut, scale: .2, opacity: self.isSupportMax3d() ? .7 : 1},
                                position: '-=1'
                            }).to({
                                target: self.that,
                                duration: 1,
                                params: {
                                    ease: Circ.easeInOut, opacity: 0, scale: 0, onComplete: function () {
                                        self.that.removeClass('busy closing opened');
                                        sewii.event.fire(self.EVENT_CLOSED);
                                    }
                                },
                                position: '-=.6'
                            });
                            if (params.force) {
                                $.isFunction(params.force) ? params.force() : self.force('close');
                            }
                            return false;
                        });
                        $(window).keydown(function (e) {
                            if (e.keyCode === 27) {
                                if (!self.isOpened()) return;
                                if (!self.parent.isActive()) return;
                                button.trigger('click');
                                return false;
                            }
                        });
                    } else button.trigger('click', {external: true, force: force});
                },
                plane: function (id) {
                    var self = this;
                    self.planes = self.planes || $();
                    return self.planes.filter('[data-id="' + id + '"]');
                },
                title: function (id) {
                    var self = this, plane = self.plane(id);
                    return plane.length ? plane.find('img').attr('alt') : null;
                },
                curretly: function () {
                    var self = this;
                    self.planes = self.planes || $();
                    return self.planes.eq(self.index());
                },
                index: function () {
                    var self = this, angle = self.angle(), value = Math.round(angle / self.theta) % self.planes.length,
                        index = Math.abs(value);
                    return index;
                },
                angle: function (value) {
                    var self = this;
                    self.angled = sewii.ifSet(self.angled, 0);
                    if (sewii.isSet(value)) {
                        self.angled += value;
                    }
                    return self.angled;
                },
                isOpening: function () {
                    var self = this;
                    return self.that && self.that.is('.opening');
                },
                isOpened: function () {
                    var self = this;
                    return self.that && self.that.is('.opened');
                },
                isClosing: function () {
                    var self = this;
                    return self.that && self.that.is('.closing');
                },
                isClosed: function () {
                    var self = this;
                    return !self.isOpened();
                },
                isBusy: function () {
                    var self = this;
                    return self.that && self.that.is('.busy');
                },
                isSupportMax3d: function () {
                    if (sewii.env.browser.webkit) {
                        return false;
                    }
                    return sewii.env.support.$3dmax;
                },
                isSupportMin3d: function () {
                    if (sewii.env.browser.webkit) {
                        return true;
                    }
                    return sewii.env.support.$3dmin;
                },
                isSupport: function () {
                    var self = this;
                    return sewii.env.support.$3d;
                },
                force: function (action, callback, reset) {
                    if (!this.isSupport()) return;
                    var self = this, args = arguments, callee = args.callee;
                    switch (String(action).toLowerCase()) {
                        case'open':
                            if (self.timelines.open) {
                                callback && sewii.event.off(self.EVENT_OPENED).once(self.EVENT_OPENED, callback);
                                reset && self.timelines.open.play(0);
                                self.timelines.open.play(-.001, false);
                            }
                            break;
                        case'close':
                            if (self.timelines.close) {
                                callback && sewii.event.off(self.EVENT_CLOSED).once(self.EVENT_CLOSED, callback);
                                reset && self.timelines.close.play(0);
                                self.timelines.close.play(-.001, false);
                            } else self.close(function () {
                                callee.apply(self, Array.prototype.slice.call(args));
                            });
                            break;
                    }
                    return self;
                },
                play: function () {
                    if (!this.isSupport()) return;
                    var self = this;
                    self.isOpening() && self.timelines.open && self.timelines.open.play();
                    self.isClosing() && self.timelines.close && self.timelines.close.play();
                    self.timelines['3d'] && $.each(self.timelines['3d'], function (i, timeline) {
                        timeline.play();
                    });
                },
                pause: function () {
                    if (!this.isSupport()) return;
                    var self = this;
                    self.isOpening() && self.timelines.open && self.timelines.open.pause();
                    self.isClosing() && self.timelines.close && self.timelines.close.pause();
                    self.timelines['3d'] && $.each(self.timelines['3d'], function (i, timeline) {
                        timeline.pause();
                    });
                },
                restore: function () {
                    if (!this.isSupport()) return;
                    var self = this;
                    if (self.timelines.open) {
                        if (self.isOpened() || self.isBusy()) {
                            self.force('open', function () {
                                self.force('close');
                            });
                        }
                    }
                    delete self.timelines['3d'];
                }
            }
        }, works: {
            id: 'works', init: function (parent, callback) {
                var self = this, that = $('#' + self.id);
                self.parent = parent;
                self.that = that;
                self.route();
                self.water.init(self);
                self.list.init(self);
                self.detail.init(self);
                self.that.visible();
                self.onInit(callback);
            }, isActive: function () {
                var self = this;
                return self.parent.isUnit('works');
            }, getUri: function () {
                var self = this;
                return $.ui.pages.common.navigator.getUri(self.id);
            }, title: function () {
                var self = this;
                return self.that.children('.heading').text() || null;
            }, route: function () {
                var self = this;
                sewii.router.routing.on(self.id, function (event) {
                    $.ui.pages.common.navigator.go(event.route.unit, {enterTiming: .7, event: event});
                });
            }, onPreload: function (event) {
                var self = this, routing = event;
                $.ui.pages.common.preloader.load({
                    id: self.id, routing: routing, callback: function (event) {
                        var preloader = this, isInited = self.init.called, callback = function () {
                            isInited || preloader.hide();
                            sewii.callback(routing, 'callback');
                        };
                        if (!isInited) {
                            self.init.called = true;
                            self.init($.ui.pages, callback);
                            return;
                        }
                        callback();
                    }
                });
            }, onCome: function (event) {
                var self = this, id = event.route.detail, target = self.detail.item(id), isExixts = target.length > 0;
                self.list.previewed = false;
                if (id && isExixts) {
                    self.list.preview();
                }
            }, onEnter: function (event) {
                var self = this, id = event.route.detail, current = self.detail.item().children('.link').data('id'),
                    target = self.detail.item(id), isExixts = target.length > 0, trace = function (s) {
                        return;
                        console.log(event.route.unit, s);
                    };
                sewii.history.title(self.title(), -1);
                sewii.history.title(self.detail.title(id), -2);
                $.ui.tracker.sendPageView();
                self.list.play();
                self.detail.play();
                if (id && isExixts) {
                    if (self.detail.isOpening()) {
                        if (current === id) return trace(1);
                        self.detail.force('open', function () {
                            self.detail.open(id);
                        }) && trace(2);
                    } else if (self.detail.isClosing()) {
                        self.detail.force('close', function () {
                            self.detail.open(id);
                        }) && trace(3);
                    } else if (self.detail.isOpened()) {
                        if (current === id) return trace(4);
                        self.detail.open(id) || trace(5);
                    } else {
                        self.detail.open(id, event.isForce) || trace(6);
                    }
                } else {
                    if (self.detail.isOpening()) {
                        self.detail.close() || trace(7);
                    } else if (self.detail.isClosing()) {
                        self.detail.doNothing || trace(8);
                    } else if (self.detail.isOpened()) {
                        self.detail.close() || trace(9);
                    }
                }
            }, onLeave: function (event) {
                var self = this;
                self.list.pause();
                self.detail.pause();
            }, onExeunt: function (event) {
                var self = this;
                self.list.restore();
                self.detail.restore();
            }, onInit: function (callback) {
                var self = this;
                callback && self.wait(function () {
                    $.ui.animator.requestAnimationFrame(function () {
                        callback && callback();
                    });
                });
            }, wait: function (callback) {
                var self = this,
                    loaders = [$.ui.loader.xc8633bedc25cf4b3, $.ui.loader.xcf21b1118fc03a3f, $.ui.loader.x43563f9b64cce748, $.ui.loader.xd3224c65496a99fc, $.ui.loader.xaf85c8a24c4358b4];
                sewii.loader.response(loaders).success(function () {
                    callback && callback();
                });
            }, list: {
                init: function (parent) {
                    var self = this, that = parent.that.find('.list'), space = that.find('.items'),
                        items = space.find('> .item');
                    self.parent = parent;
                    self.that = that;
                    self.space = space;
                    self.items = items;
                    self.resize();
                    self.hover();
                    self.mousewheel();
                    self.keyboard();
                    self.draggable.init(self);
                    self.animation.init(self);
                }, wait: function (callback) {
                    var self = this, loaders = [$.ui.loader.xc8633bedc25cf4b3, $.ui.loader.xcf21b1118fc03a3f];
                    sewii.loader.response(loaders).success(function () {
                        callback && callback();
                    });
                }, resize: function () {
                    var self = this, itemWidthRatio = self.items.width() / self.space.width();
                    $(window).resized({
                        callback: function () {
                            var itemWidth = parseInt(self.items.height() * itemWidthRatio),
                                itemsWidth = itemWidth * self.items.length, position = self.position();
                            if (position) {
                                position *= itemsWidth / self.space.width();
                                self.draggable.move(position, {duration: 0, flip: false});
                            }
                            self.items.css({width: itemWidth});
                            self.space.css({width: itemsWidth});
                        }, delay: function () {
                            return !self.parent.isActive();
                        }, now: true,
                    });
                    (function () {
                        var originalLayoutWidth = 270;
                        self.items.find('.logo').each(function () {
                            var width = $(this).width() / originalLayoutWidth * 100;
                            $(this).css({width: width + '%',});
                        });
                    }());
                }, hover: function () {
                    var self = this;
                    if (!self.isSupportHover()) {
                        self.that.addClass('disabled');
                        return;
                    }
                    self.items.each(function () {
                        var overlay = $('<div class="overlay" />');
                        $(this).find('.link').append(overlay);
                    });
                    if ($.ui.helper.support.transition()) {
                        self.that.addClass('transition');
                    } else {
                        var speed = 500, config = (function () {
                            self.that.addClass('transition');
                            var item = self.items.first().addClass('hover'),
                                opacity = self.space.addClass('hover').find('.overlay').css('opacity'),
                                bottom = parseFloat(item.find('.label').css('bottom')) / item.height() * 100,
                                boxShadow = item.css('boxShadow'), scale = (function () {
                                    var transform = String(item.find('.thumb').css('transform')),
                                        matches = transform.split(/[\(\,\)]/), scale = parseFloat((matches && matches[1]));
                                    return scale;
                                }());
                            item.removeClass('hover');
                            self.space.removeClass('hover');
                            self.that.removeClass('transition');
                            var orginalBottom = parseFloat(item.find('.label').css('bottom')) / item.height() * 100;
                            return {
                                boxShadow: boxShadow || 'none',
                                opacity: opacity || '0',
                                scale: scale || '1',
                                bottom: bottom || '0',
                                orginalBottom: orginalBottom || '0'
                            };
                        }());
                        self.items.bind({
                            mouseover: function (event) {
                                if (self.that.hasClass('busy disabled')) return;
                                var me = $(this), delay = .04;
                                $.ui.animator.tweener.to({
                                    target: me,
                                    duration: .6,
                                    params: {delay: delay, boxShadow: config.boxShadow}
                                });
                                $.ui.animator.tweener.to({
                                    target: me.find('.thumb'),
                                    duration: .5,
                                    params: {delay: delay, scale: config.scale}
                                });
                                $.ui.animator.tweener.to({
                                    target: me.find('.label'),
                                    duration: .4,
                                    params: {delay: delay, bottom: config.bottom + '%'}
                                });
                                self.items.not(me).find('.overlay').stop().fadeTo(600, config.opacity);
                            }, mouseout: function (event) {
                                if (self.that.hasClass('busy disabled')) return;
                                var me = $(this);
                                $.ui.animator.tweener.to({
                                    target: me,
                                    duration: .6,
                                    params: {overwrite: 'all', boxShadow: config.boxShadow.replace(/\d+/g, '0')}
                                });
                                $.ui.animator.tweener.to({
                                    target: me.find('.thumb'),
                                    duration: .5,
                                    params: {overwrite: 'all', scale: 1}
                                });
                                $.ui.animator.tweener.to({
                                    target: me.find('.label'),
                                    duration: .4,
                                    params: {overwrite: 'all', bottom: config.orginalBottom + '%'}
                                });
                                self.items.find('.overlay').stop().fadeTo(100, 0);
                            }
                        });
                    }
                }, position: function (value) {
                    var self = this, callee = arguments.callee;
                    if (sewii.isSet(value)) {
                        callee.position = parseFloat(value);
                        return self;
                    }
                    var position = callee.position;
                    if (!sewii.isSet(callee.position)) {
                        var offset = self.space.offset() || {};
                        position = offset.left || 0;
                    }
                    return position;
                }, moving: function (draggable, flip) {
                    var self = this, callee = arguments.callee, position = draggable.x, items = [], targets = [],
                        duration = .3, opacity = .3, perspective = 600, z = 50, rotation = 120;
                    if (!sewii.env.support.$3d) return;
                    if (position === callee.lastPosition) return; else callee.lastPosition = position;
                    if (!callee.hasPerspective) {
                        $.ui.animator.tweener.set(self.items, {perspective: perspective});
                        callee.hasPerspective = true;
                    }
                    var boundsOut = -self.items.width(), boundsIn = self.that.width();
                    callee.times = sewii.ifSet(callee.times, 0) + 1;
                    callee.timelines = sewii.ifSet(callee.timelines, {});
                    flip = sewii.ifSet(flip, true);
                    sewii.each(items.length ? items : self.items, function (index) {
                        var item = items[index] || $(this), target = targets[index] || item.find('.link'),
                            position = item.offset().left;
                        if (position <= boundsOut) {
                            item.addClass('past');
                            item.removeClass('future');
                        } else if (position >= boundsIn) {
                            self.items.slice(index).addClass('future').removeClass('past');
                            return false;
                        } else {
                            if (flip && item.hasClass('future')) {
                                var timelineName = 'future' + index + callee.times;
                                callee.timelines[timelineName] = $.ui.animator.tweener.fromTo({
                                    target: target,
                                    duration: duration,
                                    from: {opacity: opacity, rotationY: rotation, z: -z},
                                    to: {
                                        opacity: 1, rotationY: 0, z: 0, onComplete: function () {
                                            delete callee.timelines[timelineName];
                                        }
                                    }
                                });
                            } else if (flip && item.hasClass('past')) {
                                var timelineName = 'past' + index + callee.times;
                                callee.timelines[timelineName] = $.ui.animator.tweener.fromTo({
                                    target: target,
                                    duration: duration,
                                    from: {opacity: opacity, rotationY: -rotation, z: -z},
                                    to: {
                                        ease: Circ.easeOut, opacity: 1, rotationY: 0, z: 0, onComplete: function () {
                                            delete callee.timelines[timelineName];
                                        }
                                    }
                                });
                            }
                            item.removeClass('past future');
                        }
                    }, flip ? 0 : 10);
                }, move: function (delta, quantity) {
                    var self = this, min = 0, max = -(self.space.width() - self.that.width()),
                        velocity = quantity || (self.items.width() * 1),
                        position = self.position() + (delta || 0) * velocity;
                    position = (position > min) ? min : position;
                    position = (position < max) ? max : position;
                    self.draggable.move(position);
                }, forward: function (quantity) {
                    var self = this;
                    self.move(1, quantity);
                }, backward: function (quantity) {
                    var self = this;
                    self.move(-1, quantity);
                }, keyboard: function () {
                    var self = this;
                    $(window).bind('directionKeyDown', function (e, info) {
                        if (self.isBusy()) return;
                        if (self.parent.detail.isOpened()) return;
                        if (!self.parent.isActive()) return;
                        (info.isLeft || info.isUp) && self.forward();
                        (info.isRight || info.isDown) && self.backward();
                    });
                }, mousewheel: function () {
                    var self = this;
                    $.ui.loader.xd3224c65496a99fc.success(function () {
                        $(self.that).mousewheel(function (e, delta) {
                            if (self.isBusy()) return;
                            if (self.parent.detail.isOpened()) return;
                            self.move(delta);
                            return false;
                        });
                    });
                }, seek: function (item, params) {
                    item = $(item);
                    params = params || {};
                    var self = this, flip = sewii.ifSet(params.flip, true), duration = sewii.ifSet(params.duration, .8),
                        ease = sewii.ifSet(params.ease, Expo.easeInOut);
                    if (item.length) {
                        var position = -item.position().left;
                        self.stop(function () {
                            self.draggable.move(position, {
                                duration: duration,
                                ease: ease,
                                flip: flip,
                                onUpdate: function () {
                                    params.onUpdate && params.onUpdate.call(this);
                                },
                                onComplete: function () {
                                    self.draggable.applyBounds(5);
                                    params.onComplete && params.onComplete.call(this);
                                }
                            });
                        });
                    }
                }, isSupportHover: function () {
                    var self = this;
                    return $.ui.helper.support.hightPerformance();
                }, isBusy: function () {
                    var self = this;
                    return self.that.is('.busy');
                }, preview: function () {
                    var self = this;
                    self.wait(function () {
                        var start = 0, currentTime = self.animation.timeline.time(),
                            showingTime = self.animation.timeline.getLabelTime('showing');
                        if (currentTime <= showingTime) {
                            self.animation.timeline.play('ending');
                            self.that.opaque();
                            self.position(start);
                            self.space.css('left', start);
                            self.items.removeClass('past future');
                            self.previewed = true;
                        }
                    });
                }, play: function () {
                    var self = this;
                    self.wait(function () {
                        self.animation.play();
                    });
                }, pause: function () {
                    var self = this;
                    self.wait(function () {
                        self.animation.pause();
                    });
                }, stop: function (callback) {
                    var self = this;
                    self.wait(function () {
                        if (self.animation.timeline.progress() < 1) {
                            self.animation.timeline.pause();
                            self.animation.timeline.seek(-.001);
                            self.animation.timeline.progress(1);
                            self.draggable.eachTimelines(function (i, timeline) {
                                timeline.pause();
                            });
                            self.draggable.eachMoveTimelines(function (i, timeline) {
                                timeline.progress(1);
                            });
                            self.items.removeClass('past future');
                            self.that.removeClass('masked busy');
                            return setTimeout(function () {
                                callback && callback();
                            }, 1);
                        }
                        callback && callback();
                    });
                }, restore: function () {
                    var self = this;
                    self.wait(function () {
                        self.animation.restore();
                    });
                }, draggable: {
                    init: function (parent) {
                        var self = this;
                        self.parent = parent;
                        self.bind();
                        self.click();
                        self.resize();
                    }, resize: function () {
                        var self = this;
                        $(window).resized({
                            callback: function () {
                                self.applyBounds(5, true);
                            }, delay: function () {
                                return !self.parent.parent.isActive();
                            }, now: false,
                        });
                    }, wait: function (callback) {
                        var self = this, loaders = [$.ui.loader.xc8633bedc25cf4b3, $.ui.loader.xcf21b1118fc03a3f];
                        sewii.loader.response(loaders).success(function () {
                            callback && callback();
                        });
                    }, bind: function () {
                        var self = this, links = self.parent.items.find('.link');
                        links.attr('data-clickable', 'false').addClass('grab');
                        self.wait(function () {
                            var draggables = Draggable.create(self.parent.space, {
                                type: 'left',
                                bounds: self.parent.that,
                                force3D: true,
                                zIndexBoost: false,
                                throwProps: true,
                                edgeResistance: .85,
                                onClick: function (e) {
                                    $(e.target).trigger('click');
                                },
                                onPress: function (e) {
                                    $(e.target).trigger('mousedown', e.button);
                                },
                                onDragEnd: function (e) {
                                    $(e.target).trigger('mousemove');
                                    self.parent.position(this.endX);
                                },
                                onDrag: function () {
                                    self.parent.moving(this);
                                    links.addClass('grabbing');
                                },
                                onRelease: function () {
                                    links.removeClass('grabbing');
                                },
                                onThrowUpdate: function () {
                                    self.applyBounds.disabled = true;
                                    self.parent.moving(this);
                                },
                                onThrowComplete: function () {
                                    self.applyBounds.disabled = false;
                                },
                            });
                            if (draggables.length) {
                                self.instance = draggables[0];
                                self.instance.setPosition = function (position, params) {
                                    params = params || {};
                                    var me = this, target = $(me.target), callee = arguments.callee,
                                        duration = sewii.ifSet(params.duration, 1),
                                        ease = sewii.ifSet(params.ease, Circ.easeOut);
                                    self.parent.position(position);
                                    callee.times = sewii.ifSet(callee.times, 0) + 1;
                                    self.timelines = sewii.ifSet(self.timelines, {});
                                    self.timelines[callee.times] = new $.ui.animator.timeline().to({
                                        target: target,
                                        duration: duration,
                                        params: {
                                            ease: ease, left: position, overwrite: true, onUpdate: function () {
                                                me.x = parseInt(target.css('left')) || 0;
                                                self.parent.moving(me, params.flip);
                                                params.onUpdate && params.onUpdate.call(this);
                                            }, onComplete: function () {
                                                delete self.timelines[callee.times];
                                                params.onComplete && params.onComplete.call(this);
                                            }
                                        }
                                    });
                                };
                            }
                        });
                    }, applyBounds: function (delay, force) {
                        var self = this, callee = arguments.callee;
                        if (self.instance && (force || !callee.disabled)) {
                            var toApply = function () {
                                var maxX = -(self.parent.space.width() - $(window).width());
                                if (self.instance.x < maxX) {
                                    self.instance.applyBounds({
                                        minX: 0,
                                        maxX: -self.parent.space.width() + self.parent.items.width(),
                                    });
                                } else {
                                    self.instance.applyBounds({minX: 0, maxX: maxX,});
                                }
                            };
                            delay || toApply();
                            delay && setTimeout(toApply, delay);
                        }
                    }, click: function () {
                        var self = this, toListenClick = true;
                        self.parent.items.find('.link').attr('href', function (i, href) {
                            return $(this).data('href', href) && null;
                        }).bind({
                            mousedown: function (e, button) {
                                toListenClick = e.button !== 2 && button !== 2;
                                self.applyBounds();
                            }, mousemove: (function () {
                                var x, y;
                                return function (e) {
                                    if (e.clientX === x && e.clientY === y) return;
                                    x = e.clientX;
                                    y = e.clientY;
                                    toListenClick = false;
                                };
                            })(), click: function (e) {
                                var me = $(this), callee = arguments.callee,
                                    lastTriggeredTime = callee.lastTriggeredTime;
                                callee.lastTriggeredTime = e.timeStamp;
                                if (lastTriggeredTime) {
                                    var diff = e.timeStamp - lastTriggeredTime;
                                    if (diff <= 100) return false;
                                }
                                if (toListenClick) {
                                    var uri = me.data('href');
                                    uri && sewii.router.routing.go(uri);
                                }
                                return false;
                            }
                        });
                    }, move: function () {
                        var self = this, args = Array.prototype.slice.call(arguments);
                        self.wait(function () {
                            self.instance && self.instance.setPosition.apply(self.instance, args);
                        });
                    }, enable: function () {
                        var self = this;
                        self.wait(function () {
                            self.instance.enable();
                        });
                    }, disable: function () {
                        var self = this;
                        self.wait(function () {
                            self.instance.disable();
                        });
                    }, play: function () {
                        var self = this;
                        self.eachTimelines(function (i, timeline) {
                            timeline.play();
                        });
                        self.eachMoveTimelines(function (i, timeline) {
                            timeline.play();
                        });
                        self.enable();
                    }, pause: function () {
                        var self = this;
                        self.eachTimelines(function (i, timeline) {
                            timeline.pause();
                        });
                        self.eachMoveTimelines(function (i, timeline) {
                            timeline.pause();
                        });
                        self.disable();
                    }, restore: function () {
                        var self = this;
                        self.eachTimelines(function (i, timeline) {
                            timeline.pause(0);
                            delete self.timelines[i];
                        });
                        self.eachMoveTimelines(function (i, timeline) {
                            timeline.pause(0);
                            delete self.parent.moving.timelines[i];
                        });
                        self.move(-self.parent.space.width(), {duration: 0, flip: false});
                    }, eachTimelines: function (callback) {
                        var self = this;
                        $.each(self.timelines || {}, callback);
                    }, eachMoveTimelines: function (callback) {
                        var self = this;
                        $.each(self.parent.moving.timelines || {}, callback);
                    }
                }, animation: {
                    init: function (parent) {
                        var self = this;
                        self.parent = parent;
                        self.timeline = new $.ui.animator.timeline();
                        self.timeline.addLabel({label: 'showing', position: 0}).from({
                            target: self.parent.that,
                            duration: .2,
                            params: {
                                opacity: 0, onStart: function () {
                                    self.parent.that.addClass('masked busy');
                                }
                            },
                        });
                        (function () {
                            var number = 35, duration = sewii.env.support.$3d ? 1.8 : 1, delay = duration / number,
                                items = self.parent.items.length <= number ? self.parent.items : new Array(number),
                                quantity = self.parent.space.width() / items.length;
                            sewii.each(items, function (index) {
                                self.timeline.to({
                                    target: self.parent.that,
                                    duration: delay,
                                    params: {
                                        onComplete: function () {
                                            setTimeout(function () {
                                                self.parent.forward(quantity);
                                            }, 0);
                                        }
                                    },
                                });
                            }, 0, {
                                complete: function () {
                                    self.timeline.addCallback({
                                        callback: function () {
                                            setTimeout(function () {
                                                if (self.canPlayWater()) {
                                                    self.parent.that.addClass('disabled');
                                                    self.parent.parent.water.start(function () {
                                                        if (self.parent.isSupportHover()) {
                                                            self.parent.that.removeClass('disabled');
                                                        }
                                                    });
                                                }
                                            });
                                        }, position: '-=' + (items.length - 1) * delay * .25
                                    });
                                    self.timeline.addCallback({
                                        callback: function () {
                                            self.parent.that.removeClass('masked busy');
                                        }
                                    });
                                    self.timeline.addLabel('ending');
                                }
                            });
                        }());
                        self.restore();
                        return self;
                    }, canPlayWater: function () {
                        var self = this;
                        return self.parent.parent.water.isSupport() && self.parent.parent.detail.that && !self.parent.parent.detail.isOpened() && !self.parent.parent.detail.isOpening();
                    }, play: function () {
                        var self = this;
                        self.timeline.play();
                        self.parent.draggable.play();
                        self.parent.parent.water.play();
                    }, pause: function () {
                        var self = this;
                        self.timeline.pause();
                        self.parent.draggable.pause();
                        self.parent.parent.water.pause();
                    }, restore: function () {
                        var self = this;
                        self.timeline.pause(0);
                        self.parent.draggable.restore();
                        self.parent.parent.water.restore();
                        self.parent.items.addClass('past').removeClass('future');
                    }
                }
            }, detail: {
                EVENT_OPENED: 'works.detail.opened', EVENT_CLOSED: 'works.detail.closed', init: function (parent) {
                    var self = this, that = parent.that.find('.detail'), info = that.find('.info'),
                        view = that.find('.view');
                    self.parent = parent;
                    self.that = that;
                    self.info = info;
                    self.view = view;
                    self.deferred = new self.deferrer();
                    self.progressbar.init(self);
                    self.viewer.init(self);
                    self.links.init(self);
                    self.build();
                    self.resize();
                    self.scrollbar();
                    self.keyboard();
                    self.touch();
                    self.prev();
                    self.next();
                    self.close();
                }, build: function () {
                    var self = this, aside = $('<div class="aside" />'), background = $('<ul class="background" />'),
                        triangle = $('<div class="triangle" />'), close = $('<a class="close button"/>'),
                        prev = $('<a class="prev button"/>'), next = $('<a class="next button"/>'),
                        line = $('<span class="line"/>'), shadow = $('<div class="shadow"></div>');
                    aside.prependTo(self.that);
                    self.aside = aside;
                    background.appendTo(self.info).append(function () {
                        for (var i = 1, all = 3, columns = ''; i <= all; i++) {
                            columns += '<li></li>';
                            if (i === all) return columns;
                        }
                    });
                    triangle.appendTo(self.info);
                    close.prependTo(self.info);
                    prev.prependTo(self.info);
                    next.prependTo(self.info);
                    line.clone().appendTo(close);
                    line.clone().appendTo(close);
                    line.clone().appendTo(prev);
                    line.clone().appendTo(prev);
                    line.clone().appendTo(next);
                    line.clone().appendTo(next);
                    shadow.clone().addClass('top').appendTo(self.aside);
                    shadow.clone().addClass('right').appendTo(self.aside);
                    shadow.clone().addClass('bottom').appendTo(self.aside);
                    shadow.clone().addClass('left').appendTo(self.aside);
                }, resize: function () {
                    var self = this, infoRatio = 0, infoMinWidth = parseInt(self.info.css('minWidth')) || 0;
                    self.that.css('width', infoMinWidth * 10);
                    infoRatio = self.info.width() / self.that.width();
                    self.that.css('width', '100%');
                    $(window).resized({
                        callback: function () {
                            var clientWidth = $(window).width(), asideWidth = self.parent.list.items.width(),
                                mainWidth = clientWidth - asideWidth,
                                infoWidth = Math.max(infoMinWidth, Math.round(clientWidth * infoRatio)),
                                viewWidth = mainWidth - infoWidth;
                            if (viewWidth < infoWidth) {
                                viewWidth += asideWidth;
                                asideWidth = 0;
                            }
                            self.aside.css('width', asideWidth);
                            self.info.css('width', infoWidth);
                            self.view.css('width', viewWidth);
                            self.that.css('minWidth', asideWidth + infoWidth);
                            (function () {
                                var positon = self.info.find('.scrollbox').position() || {},
                                    positonTop = positon.top || 0,
                                    height = self.info.find('> .inner').height() - positonTop;
                                self.info.find('.scrollbox').css('height', height);
                                setTimeout(function () {
                                    self.scrollbar.instance && self.scrollbar.instance.resize();
                                });
                            }());
                            self.that.toggleClass('mini-size', clientWidth <= 1280);
                            self.that.toggleClass('micro-size', clientWidth <= 800);
                        }, delay: function () {
                            return !self.parent.isActive();
                        }, now: true,
                    });
                }, scrollbar: function (target, resize) {
                    var self = this, callee = arguments.callee;
                    $.ui.loader.x43563f9b64cce748.success(function () {
                        if (callee.instance) {
                            callee.instance.destroy();
                        }
                        callee.instance = self.info.find(target || '.scrollbox').scrollbar({
                            resize: false,
                            hideScrollbar: true
                        }).data('plugin_scrollbar');
                        if (resize) {
                            clearTimeout(callee.timer);
                            callee.timer = setTimeout(function () {
                                callee.instance && callee.instance.resize();
                            }, 5);
                        }
                    });
                }, keyboard: function () {
                    var self = this;
                    $(window).bind('directionKeyDown', function (e, info) {
                        if (self.isBusy()) return;
                        if (!self.isOpened()) return;
                        if (!self.parent.isActive()) return;
                        (info.isRight) && self.next();
                        (info.isLeft) && self.prev();
                    });
                }, touch: function () {
                    var self = this;
                    sewii.env.support.touchable && $.ui.loader.xaf85c8a24c4358b4.success(function () {
                        Hammer(self.that.get(0), {
                            swipeVelocityX: .3,
                            swipeVelocityY: .3,
                        }).on('swipe', function (event) {
                            var next = event.gesture.direction === 'right', prev = event.gesture.direction === 'left';
                            prev && self.prev();
                            next && self.next();
                        });
                    });
                }, prev: function () {
                    var self = this, button = self.info.find('.prev'), boundClass = 'bound';
                    if (!button.hasClass(boundClass)) {
                        button.addClass(boundClass).attr('href', function (i, href) {
                            return $(this).data('href', href) && null;
                        }).click(function () {
                            if (self.isBusy()) return false;
                            var prev = self.item('prev'), uri = prev.find('.link').data('href');
                            if (prev.length && uri) {
                                sewii.router.routing.go(uri);
                            }
                            return false;
                        });
                    } else button.click();
                }, next: function () {
                    var self = this, button = self.info.find('.next'), boundClass = 'bound';
                    if (!button.hasClass(boundClass)) {
                        button.addClass(boundClass).attr('href', function (i, href) {
                            return $(this).data('href', href) && null;
                        }).click(function () {
                            if (self.isBusy()) return false;
                            var next = self.item('next'), uri = next.find('.link').data('href');
                            if (next.length && uri) {
                                sewii.router.routing.go(uri);
                            }
                            return false;
                        });
                    } else button.click();
                }, open: function (id, first) {
                    var self = this, callee = arguments.callee, data = null, action = {};
                    (function () {
                        if (!(action.first = first)) {
                            if (!(action.open = !self.that.is('.opened'))) {
                                var current = self.item(), target = self.item(id);
                                if (current.length && target.length) {
                                    action.prev = target.index() < current.index();
                                    action.next = target.index() > current.index();
                                }
                            }
                        }
                    }());
                    if (action.first) {
                        if ((id = self.id())) {
                            data = {};
                            data.id = id;
                            data.name = self.that.find('.header .name').html();
                            data.category = self.that.find('.header .category .type').html();
                            data.description = self.that.find('.description').html();
                            data.detail = self.view.find('> .inner > img').attr('src');
                            (data.color = {}) && $.each(self.that.get(0).attributes, function () {
                                if (this.name.indexOf('data-color') === 0) {
                                    var name = this.name.replace(/^data\-color\-/, '');
                                    data.color[name] = this.value;
                                }
                            });
                            data = sewii.json.encode(data);
                        } else return;
                    }
                    self.that.addClass('busy opening');
                    self.abort();
                    self.parent.list.that.addClass('masked');
                    callee.timelines = sewii.ifSet(callee.timelines, {});
                    var url = self.parent.list.items.find('.link[data-id="' + id + '"]').data('href'),
                        onDone = function (data) {
                            if (!(data = sewii.json.parse(data, null))) {
                                return onFailed(2);
                            }
                            self.view.data('ready', null);
                            if (!self.that.is('.opened')) {
                                self.that.addClass('opened');
                                self.parent.list.that.addClass('disabling');
                                self.parent.list.items.mouseout();
                                var onComplete = function () {
                                    var ready = self.view.data('ready');
                                    if (!ready) self.view.data('ready', true); else ready.player();
                                };
                                callee.timelines.opening = new $.ui.animator.timeline({delay: .4}).pause();
                                callee.timelines.opening.to({
                                    target: self.that,
                                    duration: 1.2,
                                    params: {ease: Expo.easeInOut, left: '0%',}
                                }).addCallback({
                                    callback: function () {
                                        if (action.first || self.parent.list.previewed) {
                                            self.parent.water.startWithoutDark();
                                        }
                                    }, position: '-=.3'
                                });
                                if (!sewii.env.support.$3d) {
                                    callee.timelines.opening.addCallback({callback: onComplete, position: '-=.6'});
                                } else {
                                    callee.timelines.opening.fromTo({
                                        target: self.view,
                                        duration: .7,
                                        from: {
                                            transformOrigin: '0% 50% 0',
                                            transformPerspective: 600,
                                            rotationY: 180,
                                            opacity: .5,
                                            z: 0,
                                        },
                                        to: {
                                            ease: Expo.easeInOut, rotationY: 0, opacity: 1, onComplete: function () {
                                                self.view.css('transform', 'initial');
                                            }
                                        },
                                        position: '-=.35'
                                    }).addCallback({callback: onComplete, position: '-=.1'});
                                }
                            }
                            var slidable = self.info.find('.slidable:last');
                            if (action.prev || action.next) {
                                var cloned = slidable.clone().insertAfter(slidable);
                                if (callee.timelines.changing) {
                                    callee.timelines.changing.progress(-.001, false);
                                    self.info.find('.slidable').not(slidable).not(cloned).remove();
                                }
                                cloned.find('.scrollbar').remove();
                                self.scrollbar(cloned.find('.scrollbox'), true);
                                slidable = cloned.css('left', (action.prev ? '-' : '+') + '100%');
                                callee.timelines.changing = new $.ui.animator.timeline();
                                callee.timelines.changing.to({
                                    target: self.info.find('.slidable'),
                                    duration: .8,
                                    params: {
                                        ease: Expo.easeInOut,
                                        left: (action.prev ? '+' : '-') + '=100%',
                                        overwrite: true,
                                        onComplete: function () {
                                            self.info.find('.slidable').not(cloned).remove();
                                        }
                                    }
                                }).pause();
                            }
                            callee.timer = sewii.timer.timeout(function () {
                                self.pan();
                                callee.timelines.changing && callee.timelines.changing.play();
                                callee.timelines.opening && callee.timelines.opening.play();
                                self.color.change(data.color, action);
                            }, action.first || action.open ? 5 : 200);
                            slidable.find('.header .demo').attr('href', data.link);
                            slidable.find('.header .category .type').html(data.category);
                            slidable.find('.header .name').html(data.name);
                            slidable.find('.description').html(data.description);
                            self.that.data('id', data.id);
                            self.button();
                            self.links.destroy();
                            self.viewer.init(self, data, action, function () {
                                self.that.removeClass('loading opening busy');
                                self.links.build();
                                self.preload();
                            });
                            (function () {
                                var namespace = 'works.detail.tracking11',
                                    memory = callee.memory || new sewii.memory('session', namespace),
                                    view = (memory.get('view') || 0) + 1;
                                $.ui.tracker.sendEvent('Works', 'View', data.id, view);
                                memory.set('view', view);
                                callee.memory = memory;
                            }());
                        }, onFailed = function (type) {
                            self.progressbar.done();
                            self.that.removeClass('loading opening busy');
                            action.open && self.parent.list.that.removeClass('masked disabling busy');
                            $.ui.helper.error.connection();
                        };
                    if (!data) {
                        self.that.addClass('loading');
                        self.progressbar.start();
                        self.request = $.get(self.getDataUri(id)).done(function (data) {
                            var doing = function () {
                                onDone(data);
                            };
                            if (self.isPaused()) {
                                return self.deferred.add(function () {
                                    doing();
                                });
                            }
                            doing();
                        }).fail(function () {
                            var doing = function () {
                                onFailed(1);
                            };
                            if (self.isPaused()) {
                                return self.deferred.add(function () {
                                    doing();
                                });
                            }
                            doing();
                        });
                    } else onDone(data);
                }, close: function (force) {
                    var self = this, callee = arguments.callee, button = self.info.find('.close'), boundClass = 'bound';
                    if (!button.hasClass(boundClass)) {
                        button.addClass(boundClass).attr('href', function (i, href) {
                            return $(this).data('href', href) && null;
                        }).click(function (event, params) {
                            params = params || {};
                            if (!params.force) {
                                if (!params.external && !self.that.is('.closing') && sewii.url.param('detail')) {
                                    var uri = self.parent.getUri();
                                    uri && sewii.router.routing.go(uri);
                                    return false;
                                }
                                if (self.that.is('.closing')) return; else self.that.addClass('busy closing');
                            }
                            self.abort();
                            self.pause();
                            self.progressbar.done();
                            self.that.removeClass('paused loading opening');
                            callee.timeline = new $.ui.animator.timeline().to({
                                target: self.that,
                                duration: 1.5,
                                params: {ease: Elastic.easeInOut, left: '-200%'}
                            }).addCallback({
                                callback: function () {
                                    self.restore(true);
                                    self.viewer.unbind(true);
                                    self.parent.list.draggable.enable();
                                }, position: 1
                            }).addCallback({
                                callback: function () {
                                    self.that.removeClass('closing opened busy');
                                    self.parent.list.that.removeClass('masked disabling busy');
                                    sewii.event.fire(self.EVENT_CLOSED);
                                }, position: 1.2
                            });
                            if (params.force) {
                                $.isFunction(params.force) ? params.force() : self.force('close');
                            }
                            return false;
                        });
                        $(window).keydown(function (e) {
                            if (e.keyCode === 27) {
                                if (!self.isOpened()) return;
                                if (!self.parent.isActive()) return;
                                button.trigger('click');
                                return false;
                            }
                        });
                    } else button.trigger('click', {external: true, force: force});
                }, preload: function () {
                    var self = this, callee = arguments.callee, current = self.item(), prev = self.item('prev'),
                        next = self.item('next'), items = [prev, next];
                    callee.request = callee.request || {};
                    callee.loaded = callee.loaded || {};
                    callee.loaded[current.find('.link').data('id')] = true;
                    sewii.each(items, function () {
                        var item = $(this), link = item.find('.link'), id = link.data('id');
                        if (!id || callee.loaded[id] || callee.request[id]) {
                            return true;
                        }
                        var href = link.data('href'), uri = sewii.router.convert.toDynamic(href),
                            params = sewii.url.query.construct(uri).toArray(), id = params['detail'],
                            requestUri = self.getDataUri(id);
                        if (id) {
                            callee.request[id] = $.getJSON(requestUri).done(function (data) {
                                $.loadImage(data.detail, {
                                    forceXhr: true, createBlob: false, load: function () {
                                        callee.loaded[id] = true;
                                    }
                                });
                            });
                        }
                    });
                }, pan: function (params) {
                    params = params || {};
                    var self = this, current = self.item();
                    current.length && setTimeout(function () {
                        self.parent.list.seek(current, {
                            ease: Expo.easeInOut,
                            duration: 1,
                            flip: false,
                            onUpdate: function () {
                                params.onUpdate && params.onUpdate.call(this);
                            },
                            onComplete: function () {
                                params.onComplete && params.onComplete.call(this);
                            }
                        });
                    }, 5);
                }, button: function () {
                    var self = this;
                    self.info.find('.prev')[self.item('prev').length ? 'fadeIn' : 'fadeOut']();
                    self.info.find('.next')[self.item('next').length ? 'fadeIn' : 'fadeOut']();
                }, title: function (id) {
                    var self = this, current = self.item(id), title = current.children('.heading').text();
                    return title || null;
                }, item: function (get) {
                    get = sewii.stringify(get).toLowerCase();
                    var self = this, isPrev = get === 'prev', isNext = get === 'next',
                        id = (arguments.length >= 1 && !isPrev && !isNext) ? get : self.id(),
                        link = self.parent.list.items.find('.link[data-id="' + id + '"]'), item = link.parents('.item');
                    if (isPrev) item = item.prev();
                    if (isNext) item = item.next();
                    return item;
                }, getDataUri: function (id) {
                    var self = this;
                    id = id || self.id();
                    return self.parent.getUri() + '/get/json/detail/' + id
                }, id: function () {
                    var self = this;
                    return $(self.that).data('id') || sewii.url.param('detail');
                }, isOpening: function () {
                    var self = this;
                    return self.that.is('.opening');
                }, isOpened: function () {
                    var self = this;
                    return self.that.is('.opened');
                }, isPaused: function () {
                    var self = this;
                    return self.that.is('.paused');
                }, isClosing: function () {
                    var self = this;
                    return self.that.is('.closing');
                }, isClosed: function () {
                    var self = this;
                    return !self.isOpened();
                }, isBusy: function () {
                    var self = this;
                    return self.that.is('.busy');
                }, abort: function () {
                    var self = this;
                    self.open.timer && self.open.timer.stop();
                    self.request && self.request.abort();
                    self.viewer.abort();
                }, force: function (action, callback, reset) {
                    var self = this, args = arguments, callee = args.callee;
                    switch (String(action).toLowerCase()) {
                        case'open':
                            callback && sewii.event.off(self.EVENT_OPENED).once(self.EVENT_OPENED, callback);
                            reset && self.open.timelines.opening.play(0);
                            self.open.timelines.opening && self.open.timelines.opening.play(-.001, true);
                            sewii.event.fire(self.EVENT_OPENED);
                            break;
                        case'close':
                            if (self.close.timeline) {
                                callback && sewii.event.off(self.EVENT_CLOSED).once(self.EVENT_CLOSED, callback);
                                reset && self.close.timeline.play(0);
                                self.close.timeline.play(-.001, false);
                            } else self.close(function () {
                                callee.apply(self, Array.prototype.slice.call(args));
                            });
                            break;
                    }
                    return self;
                }, play: function () {
                    var self = this;
                    if (!self.open.timelines) return;
                    self.that.removeClass('paused');
                    if (self.isOpening()) {
                        self.open.timer && self.open.timer.resume();
                        self.open.timelines.opening && self.open.timelines.opening.play();
                        self.open.timelines.changing && self.open.timelines.changing.play();
                        self.viewer.play();
                        self.color.play();
                        self.progressbar.play();
                        self.parent.list.draggable.play();
                        self.deferred.execute();
                    }
                    if (self.isClosing()) {
                        self.close.timeline && self.close.timeline.play();
                    }
                }, pause: function () {
                    var self = this;
                    if (!self.open.timelines) return;
                    self.that.addClass('paused');
                    self.open.timer && self.open.timer.pause();
                    self.open.timelines.opening && self.open.timelines.opening.pause();
                    self.open.timelines.changing && self.open.timelines.changing.pause();
                    self.close.timeline && self.close.timeline.pause();
                    self.viewer.pause();
                    self.color.pause();
                    self.progressbar.pause();
                    self.parent.list.draggable.pause();
                }, restore: function (ignoreForce) {
                    var self = this;
                    if (!self.open.timelines) return;
                    self.that.removeClass('paused');
                    self.open.timer && self.open.timer.stop();
                    self.open.timelines.changing && self.open.timelines.changing.progress() < 1 && self.open.timelines.changing.play(-.001, false);
                    if (self.open.timelines.opening) {
                        if (!ignoreForce && (self.isOpened() || self.isBusy())) {
                            self.force('open', function () {
                                self.force('close', null, true);
                            });
                        }
                    }
                    self.viewer.restore();
                    self.color.restore();
                    self.progressbar.restore();
                    self.deferred.empty();
                }, viewer: {
                    init: function (parent, data, action, callback) {
                        var self = this, inner = parent.view.find('>.inner');
                        self.parent = parent;
                        self.inner = inner;
                        self.build();
                        if (data && action) {
                            self.data = data;
                            self.action = action;
                            self.deferred = new self.parent.deferrer();
                        } else return;
                        self.load({
                            success: function () {
                                self.bind(function () {
                                    self.animate(callback);
                                });
                            }, error: function () {
                                callback && callback();
                                $.ui.helper.error.connection();
                            },
                        });
                    }, build: function () {
                        var self = this, callee = arguments.callee, loader = $('<div class="loader" />'),
                            shadow = $('<div class="shadow"></div>');
                        if (callee.built) return; else callee.built = true;
                        self.loader = loader;
                        self.loader.appendTo(self.parent.view);
                        shadow.clone().addClass('top').appendTo(self.parent.view);
                        shadow.clone().addClass('right').appendTo(self.parent.view);
                        shadow.clone().addClass('bottom').appendTo(self.parent.view);
                        shadow.clone().addClass('left').appendTo(self.parent.view);
                    }, bind: function (callback) {
                        var self = this;
                        self.unbind();
                        self.resize();
                        self.mousewheel();
                        self.keyboard();
                        self.skip();
                        self.contextmenu();
                        self.image.invisible();
                        self.inner.prepend(self.image);
                        self.lastContainer = self.container;
                        self.waitForLoad(function () {
                            var doing = function () {
                                self.sprite();
                                self.lastContainer = self.lastContainer || self.container;
                                self.container.opacity(.001);
                                self.draggable.init(self);
                                callback && callback();
                            };
                            if (self.parent.isPaused()) {
                                return self.deferred.add(function () {
                                    doing();
                                });
                            }
                            doing();
                        });
                    }, unbind: function (clearAll) {
                        var self = this;
                        self.abort();
                        self.position(0);
                        self.draggable.kill();
                        clearAll && self.inner.children().remove();
                        clearAll && self.releaseBlobUrl(self.image);
                    }, load: function (params) {
                        var self = this, onComplete = function () {
                            self.hideLoader();
                            self.parent.progressbar.done();
                            self.parent.that.removeClass('loading');
                        };
                        if (self.image) {
                            self.releaseBlobUrl(self.image);
                        }
                        if (!self.data.detail) {
                            onComplete() || sewii.callback(params, 'error');
                        }
                        var loadImageOptions = {
                            url: self.data.detail, load: function (e) {
                                var image = this;
                                if (self.parent.isPaused()) {
                                    return self.deferred.add(function () {
                                        loadImageOptions.load.call(image, e);
                                    });
                                }
                                self.image = $(image);
                                onComplete() || sewii.callback(params, 'success');
                            }, error: function (e) {
                                var image = this;
                                if (self.parent.isPaused()) {
                                    return self.deferred.add(function () {
                                        loadImageOptions.error.call(image, e);
                                    });
                                }
                                onComplete() || sewii.callback(params, 'error');
                            }, progress: function (e) {
                                if (self.parent.isPaused()) return;
                                self.parent.progressbar.to(e.progressed);
                            }
                        };
                        var image = self.inner.find('> img').get(0);
                        if (image) {
                            var preloader = $.ui.preloader('works1') || $.ui.preloader();
                            if (preloader && preloader.isLoaded(self.data.detail)) {
                                image.loaded = true;
                                return loadImageOptions.load.call(image);
                            }
                            $(image).remove();
                        }
                        var delay = 600;
                        if (self.action.first || self.action.open) {
                            delay = sewii.env.support.$3d ? 1800 : 1500;
                        }
                        self.showLoader(delay);
                        self.request = $.loadImage(loadImageOptions);
                    }, waitForLoad: function (callback) {
                        var self = this, image = self.image.get(0), onLoad = function () {
                            var callee = arguments.callee, getSize = function (dir) {
                                if (dir === 'width') return 'naturalWidth' in image ? image.naturalWidth : image.width;
                                if (dir === 'height') return 'naturalHeight' in image ? image.naturalHeight : image.height;
                            };
                            if (!getSize('width') || !getSize('height')) {
                                if ((callee.retry = (callee.retry || 0) + 1) <= 1000) {
                                    return (self.waitTimer = sewii.timer.timeout(callee, 5));
                                }
                            }
                            callback && callback();
                        };
                        if (image.loaded) onLoad(); else if (image.complete) onLoad(); else image.onload = onLoad;
                    }, position: function (value) {
                        var self = this, callee = arguments.callee;
                        if (sewii.isSet(value)) {
                            callee.position = parseFloat(value);
                            return self;
                        }
                        return callee.position;
                    }, moving: function (draggable) {
                        var self = this, callee = arguments.callee, position = draggable.y, tiles = [], targets = [];
                        if (sewii.env.os.android && sewii.env.browser.chrome) return;
                        if (!sewii.env.support.$3d) return;
                        if (position === callee.lastPosition) return; else callee.lastPosition = position;
                        var boundsOut = $('#header').height() - self.tiles.height(),
                            boundsIn = self.parent.view.height() + $('#footer').height();
                        callee.times = sewii.ifSet(callee.times, 0) + 1;
                        callee.timelines = sewii.ifSet(callee.timelines, {});
                        sewii.each(tiles.length ? tiles : self.tiles, function (index) {
                            var tile = tiles[index] || $(this), target = targets[index] || tile.find('.inner'),
                                position = tile.offset().top;
                            if (position <= boundsOut) {
                                tile.addClass('past');
                                tile.removeClass('future');
                            } else if (position >= boundsIn) {
                                self.tiles.slice(index).addClass('future').removeClass('past');
                                return false;
                            } else {
                                var duration = .45, perspective = 500, rotation = 90, z = 150;
                                if (tile.hasClass('future')) {
                                    var timelineName = 'future' + index + callee.times;
                                    callee.timelines[timelineName] = $.ui.animator.tweener.fromTo({
                                        target: target,
                                        duration: duration,
                                        from: {
                                            transformPerspective: perspective,
                                            visibility: 'hidden',
                                            rotationX: rotation,
                                            z: z
                                        },
                                        to: {
                                            ease: Circ.easeOut,
                                            visibility: 'visible',
                                            rotationX: 0,
                                            z: -0,
                                            onComplete: function () {
                                                delete callee.timelines[timelineName];
                                            }
                                        }
                                    });
                                } else if (tile.hasClass('past')) {
                                    var timelineName = 'past' + index + callee.times;
                                    callee.timelines[timelineName] = $.ui.animator.tweener.fromTo({
                                        target: target,
                                        duration: duration,
                                        from: {
                                            transformPerspective: perspective,
                                            visibility: 'hidden',
                                            rotationX: -rotation,
                                            z: z
                                        },
                                        to: {
                                            ease: Circ.easeOut,
                                            visibility: 'visible',
                                            rotationX: 0,
                                            z: -0,
                                            onComplete: function () {
                                                delete callee.timelines[timelineName];
                                            }
                                        }
                                    });
                                }
                                tile.removeClass('past future');
                            }
                        }, 0);
                    }, resize: function () {
                        var self = this, callee = arguments.callee;
                        if (callee.bound) return; else callee.bound = true;
                        $(window).resized({
                            callback: function () {
                                if (self.spriteInstance) {
                                    var originalHeight = self.container.height(), position = self.position();
                                    self.spriteInstance && self.spriteInstance.resize();
                                    self.openSpriteInstance && self.openSpriteInstance.resize();
                                    if (position) {
                                        position *= (position < 0) ? self.container.height() / originalHeight : originalHeight / self.container.height();
                                        self.draggable.move(position, {duration: 0});
                                    }
                                }
                            }, delay: function () {
                                return !self.parent.parent.isActive();
                            }, now: false,
                        });
                    }, keyboard: function () {
                        var self = this, callee = arguments.callee;
                        if (callee.bound) return; else callee.bound = true;
                        $(window).bind('directionKeyDown', function (e, info) {
                            if (self.parent.isBusy() || self.parent.isPaused()) return;
                            if (!self.parent.parent.isActive()) return;
                            (info.isUp) && self.backward();
                            (info.isDown) && self.forward();
                        });
                    }, mousewheel: function () {
                        var self = this, callee = arguments.callee;
                        if (callee.bound) return; else callee.bound = true;
                        $.ui.loader.xd3224c65496a99fc.success(function () {
                            $(self.parent.view).mousewheel(function (e, delta, force) {
                                if (!force && (self.parent.isBusy() || self.parent.isPaused())) return;
                                var min = 0, max = -(self.container.height() - self.parent.view.height()),
                                    volume = parseInt(self.tiles.height() / 1),
                                    position = self.position() + (delta || 0) * volume;
                                if (self.container.height() > self.inner.height()) {
                                    position = (position > min) ? min : position;
                                    position = (position < max) ? max : position;
                                } else {
                                    position = (position < min) ? min : position;
                                    position = (position > max) ? max : position;
                                }
                                self.draggable.move(position);
                                return false;
                            });
                        });
                    }, skip: function () {
                        var self = this, callee = arguments.callee, button = $(self.parent.view.find('.skip'));
                        if (callee.bound) return; else callee.bound = true;
                        button.click(function (event) {
                            var timeline = self.animate.timelines && self.animate.timelines['open-3d-in'];
                            if (timeline && timeline.isActive()) {
                                $.ui.animator.tweener.to({
                                    target: self.inner,
                                    duration: .5,
                                    params: {
                                        alpha: 0, onComplete: function () {
                                            timeline.play(-.001, false);
                                            $.ui.animator.tweener.to({
                                                target: self.inner,
                                                duration: 1,
                                                params: {alpha: 1}
                                            });
                                        }
                                    }
                                });
                            }
                            return false;
                        });
                        $(window).keydown(function (e) {
                            if (e.ctrlKey && e.keyCode === 67) {
                                if (!self.parent.isOpening()) return;
                                if (!self.parent.parent.isActive()) return;
                                button.trigger('click');
                                return false;
                            }
                        });
                    }, contextmenu: function () {
                        var self = this, callee = arguments.callee;
                        if (callee.bound) return; else callee.bound = true;
                        $(self.inner).bind('contextmenu', function (event) {
                            event.stopPropagation();
                            event.preventDefault();
                            return false;
                        });
                    }, forward: function () {
                        var self = this;
                        $(self.parent.view).trigger('mousewheel', [-1, true]);
                    }, backward: function () {
                        var self = this;
                        $(self.parent.view).trigger('mousewheel', [1, true]);
                    }, sprite: function () {
                        var self = this;
                        self.spriteInstance = self.image.spriteImage({
                            rows: 35,
                            cols: 1,
                            resize: false
                        }).data('plugin_spriteImage');
                        self.container = $(self.spriteInstance.container);
                        self.tiles = self.container.children();
                    }, releaseBlobUrl: function (image) {
                        var self = this;
                        setTimeout(function () {
                            if (image && $.ui.helper.support.xhr2()) {
                                var blobUrl = image.attr('src');
                                if (/^blob/i.test(blobUrl)) {
                                    window.URL.revokeObjectURL(blobUrl);
                                }
                            }
                        }, 1);
                    }, animate: function (callback) {
                        var self = this, callee = arguments.callee;
                        $.each(callee.timelines || {}, function (i, timeline) {
                            timeline.isActive() && timeline.play(-.001);
                        });
                        (self.action.first || self.action.open) && (function () {
                            var delayPlay = 1, onComplete = function () {
                                self.parent.view.removeClass('opening changing');
                                callback && callback();
                            };
                            self.parent.view.addClass('opening');
                            callee.timelines = sewii.ifSet(callee.timelines, {});
                            sewii.env.support.$3d ? $3d() : $2d();

                            function $2d() {
                                self.container.opaque();
                                self.parent.view.addClass('flat');
                                callee.timelines['open-2d-in'] = new $.ui.animator.timeline().pause();
                                callee.timelines['open-2d-in'].fromTo({
                                    target: self.inner,
                                    duration: 1,
                                    from: {left: -self.inner.width(),},
                                    to: {ease: Expo.easeInOut, left: 0,},
                                }).addCallback({
                                    callback: function () {
                                        self.parent.view.removeClass('flat');
                                        onComplete();
                                    }, position: '-=.3'
                                });
                            }

                            function $3d() {
                                callee.timer = sewii.timer.timeout(function () {
                                    var image = self.image.clone().appendTo(self.inner), spriteSize = 16,
                                        spriteInstance = image.spriteImage({
                                            rows: parseInt(image.height() / parseInt(self.inner.height() / Math.sqrt(spriteSize))),
                                            cols: parseInt(image.width() / parseInt(self.inner.width() / Math.sqrt(spriteSize))),
                                            resize: false
                                        }).data('plugin_spriteImage'), container = spriteInstance.container,
                                        tiles = container.children(),
                                        borderWidth = parseInt($(tiles).css('borderWidth')) || 0;
                                    self.openSpriteInstance = spriteInstance;
                                    self.container.invisible().opaque();
                                    borderWidth = borderWidth || 1;
                                    $.ui.animator.tweener.set(container, {x: -borderWidth, y: -borderWidth,});
                                    self.parent.view.addClass('solid');
                                    callee.timelines['open-3d-in'] = new $.ui.animator.timeline().pause();
                                    callee.timelines['open-3d-in'].fromTo({
                                        target: tiles,
                                        duration: .8,
                                        from: {
                                            transformOrigin: '50% 50% 0',
                                            transformPerspective: 1000,
                                            scale: 0,
                                            opacity: 0
                                        },
                                        to: {ease: Expo.easeInOut, scale: .95, opacity: 1},
                                    }).fromTo({
                                        target: self.inner,
                                        duration: .8,
                                        from: {
                                            transformOrigin: '50% 50% 0',
                                            transformPerspective: 1000,
                                            scale: 1,
                                            rotationY: 0,
                                            rotationX: 0,
                                        },
                                        to: {ease: Expo.easeInOut, rotationY: 20, rotationX: 20, scale: .7,},
                                        position: '-=.2'
                                    }).to({
                                        target: self.inner,
                                        duration: .8,
                                        to: {ease: Expo.easeInOut, rotationY: 160,},
                                        position: '-=.2'
                                    }).to({
                                        target: self.inner,
                                        duration: .8,
                                        to: {ease: Expo.easeInOut, rotationX: 160,},
                                        position: '-=.2'
                                    }).to({
                                        target: self.inner,
                                        duration: 1,
                                        to: {ease: Expo.easeInOut, scale: 1, rotationX: 0, rotationY: 0,},
                                        position: '-=.2'
                                    }).staggerTo({
                                        target: tiles.slice(0, spriteSize),
                                        duration: 1,
                                        to: {
                                            ease: Expo.easeInOut,
                                            rotationY: 360,
                                            scale: .975,
                                            borderColor: 'transparent',
                                        },
                                        stagger: .05,
                                        position: '-=.5'
                                    }).staggerTo({
                                        target: tiles.slice(0, spriteSize),
                                        duration: 1,
                                        to: {ease: Expo.easeInOut, rotationX: 360, scale: 1, boxShadow: '0',},
                                        stagger: .05,
                                        position: '-=1',
                                        onComplete: function () {
                                            self.releaseBlobUrl(image);
                                            self.container.visible();
                                            container.remove();
                                            onComplete();
                                        }
                                    });
                                }, 1);
                            }

                            (function () {
                                var ready = self.parent.view.data('ready'), player = function () {
                                    callee.timer = sewii.timer.timeout(function () {
                                        callee.timelines['open-3d-in'] && callee.timelines['open-3d-in'].play();
                                        callee.timelines['open-2d-in'] && callee.timelines['open-2d-in'].play();
                                    }, delayPlay);
                                };
                                if (ready) player(); else self.parent.view.data('ready', {player: player});
                            }());
                        }());
                        (self.action.prev || self.action.next) && (function () {
                            var delayPlay = 1, containerId = self.container.attr('id'), onComplete = function () {
                                if (self.container.parent().children().length) {
                                    self.container.nextAll().remove();
                                } else {
                                    self.container.show();
                                }
                                self.parent.view.removeClass('changing opening');
                                callback && callback();
                            };
                            self.parent.view.addClass('changing');
                            callee.timelines = sewii.ifSet(callee.timelines, {});
                            sewii.env.support.$3d ? $3d() : $2d();

                            function $2d() {
                                callee.timelines['change-2d-out'] = new $.ui.animator.timeline().pause();
                                callee.timelines['change-2d-out'].fromTo({
                                    target: self.container,
                                    duration: 1,
                                    from: {opacity: 0,},
                                    to: {opacity: 1,},
                                    position: 0
                                }).to({
                                    target: self.lastContainer,
                                    duration: .8,
                                    params: {
                                        ease: Expo.easeInOut,
                                        opacity: 0,
                                        left: (self.action.next ? '-' : '') + self.lastContainer.height(),
                                    },
                                    position: 0
                                }).addCallback({callback: onComplete, position: '-=.0'});
                            }

                            function $3d() {
                                var perspective = 1000, rotation = [50, 100, 300, 360], opacity = [.5, 1, .5, 1],
                                    r = self.inner.height() * .70, y = [r, r, -r, 0], z = [-600, -1200, -600, 0],
                                    duration = !sewii.env.device.mobile ? [.35, .25, .25, .35] : [.6, .4, .4, .6],
                                    easeIn = Circ.easeIn, easeOut = Circ.easeOut;
                                if (self.container.height() >= self.inner.height() && self.lastContainer.height() >= self.inner.height()) {
                                    self.parent.view.addClass('border');
                                }
                                callee.timelines['change-3d-out'] = new $.ui.animator.timeline().pause();
                                callee.timelines['change-3d-out'].set(self.inner, {
                                    transformOrigin: '50% 50% 0',
                                    transformPerspective: perspective,
                                    rotationX: 0,
                                    z: 0,
                                    y: 0
                                });
                                if (self.action.prev) {
                                    callee.timelines['change-3d-out'].to({
                                        target: self.inner,
                                        duration: duration[0],
                                        params: {
                                            ease: easeIn,
                                            opacity: opacity[0],
                                            rotationX: -rotation[0],
                                            z: z[0],
                                            y: y[0],
                                        }
                                    }).to({
                                        target: self.inner,
                                        duration: duration[1],
                                        params: {opacity: opacity[1], rotationX: -rotation[1], z: z[1], y: y[1],},
                                        position: '-=.05'
                                    }).addCallback({
                                        callback: function () {
                                            self.lastContainer.hide();
                                            self.container.opacity(1);
                                            callee.timelines['change-3d-in'] = new $.ui.animator.timeline().to({
                                                target: self.inner,
                                                duration: duration[2],
                                                params: {
                                                    opacity: opacity[2],
                                                    rotationX: -rotation[2],
                                                    z: z[2],
                                                    y: y[2],
                                                    onUpdate: function () {
                                                        if (!arguments.callee.done && this.progress() >= .3) {
                                                            arguments.callee.done = true;
                                                            onComplete();
                                                        }
                                                    },
                                                }
                                            }).to({
                                                target: self.inner,
                                                duration: duration[3],
                                                params: {
                                                    ease: easeOut,
                                                    opacity: opacity[3],
                                                    rotationX: -rotation[3],
                                                    z: z[3],
                                                    y: y[3],
                                                    onUpdate: function () {
                                                        if (!arguments.callee.done && this.progress() >= .8) {
                                                            arguments.callee.done = true;
                                                            self.parent.view.removeClass('border');
                                                        }
                                                    },
                                                }
                                            });
                                        }, position: '-=.1'
                                    });
                                } else {
                                    callee.timelines['change-3d-out'].to({
                                        target: self.inner,
                                        duration: duration[0],
                                        params: {
                                            ease: easeIn,
                                            opacity: opacity[0],
                                            rotationX: rotation[0],
                                            z: z[0],
                                            y: -y[0],
                                        }
                                    }).to({
                                        target: self.inner,
                                        duration: duration[1],
                                        params: {opacity: 0, rotationX: rotation[1], z: z[1], y: -y[1],},
                                        position: '-=.05'
                                    }).addCallback({
                                        callback: function () {
                                            self.lastContainer.hide();
                                            self.container.opacity(1);
                                            callee.timelines['change-3d-in'] = new $.ui.animator.timeline().to({
                                                target: self.inner,
                                                duration: duration[2],
                                                params: {
                                                    opacity: opacity[2],
                                                    rotationX: rotation[2],
                                                    z: z[2],
                                                    y: -y[2],
                                                    onUpdate: function () {
                                                        if (!arguments.callee.done && this.progress() >= .3) {
                                                            arguments.callee.done = true;
                                                            onComplete();
                                                        }
                                                    },
                                                }
                                            }).to({
                                                target: self.inner,
                                                duration: duration[3],
                                                params: {
                                                    ease: easeOut,
                                                    opacity: opacity[3],
                                                    rotationX: rotation[3],
                                                    z: z[3],
                                                    y: -y[3],
                                                    onUpdate: function () {
                                                        if (!arguments.callee.done && this.progress() >= .8) {
                                                            arguments.callee.done = true;
                                                            self.parent.view.removeClass('border');
                                                        }
                                                    },
                                                }
                                            });
                                        }, position: '-=.1'
                                    });
                                }
                            }

                            (function () {
                                callee.timer = sewii.timer.timeout(function () {
                                    callee.timelines['change-2d-out'] && callee.timelines['change-2d-out'].play();
                                    callee.timelines['change-3d-out'] && callee.timelines['change-3d-out'].play();
                                }, delayPlay);
                            }());
                        }());
                    }, showLoader: function (delay) {
                        var self = this;
                        self.loader.hide();
                        if (!self.inner.children().length) {
                            self.loader.addClass('transparent');
                        } else {
                            self.loader.removeClass('transparent');
                        }
                        self.loaderTimer = sewii.timer.timeout(function () {
                            self.loader.fadeIn(800);
                        }, sewii.ifSet(delay, 600));
                    }, hideLoader: function () {
                        var self = this;
                        self.loaderTimer && self.loaderTimer.stop();
                        self.loader.fadeOut(200);
                    }, abort: function () {
                        var self = this;
                        self.request && self.request.abort();
                        self.waitTimer && self.waitTimer.stop();
                        self.loaderTimer && self.loaderTimer.stop();
                        self.animate.timer && self.animate.timer.stop();
                    }, play: function () {
                        var self = this;
                        self.deferred && self.deferred.execute();
                        self.waitTimer && self.waitTimer.resume();
                        self.loaderTimer && self.loaderTimer.resume();
                        self.animate.timer && self.animate.timer.resume();
                        if (self.animate.timelines) {
                            $.each(self.animate.timelines, function (i, timeline) {
                                timeline.progress() > 0 && timeline.play();
                            });
                        }
                        if (self.moving.timelines) {
                            $.each(self.moving.timelines, function (i, timeline) {
                                timeline.play();
                            });
                        }
                        self.draggable.play();
                    }, pause: function () {
                        var self = this;
                        self.waitTimer && self.waitTimer.pause();
                        self.loaderTimer && self.loaderTimer.pause();
                        self.animate.timer && self.animate.timer.pause();
                        if (self.animate.timelines) {
                            $.each(self.animate.timelines, function (i, timeline) {
                                timeline.pause();
                            });
                        }
                        if (self.moving.timelines) {
                            $.each(self.moving.timelines, function (i, timeline) {
                                timeline.pause();
                            });
                        }
                        self.draggable.pause();
                    }, restore: function () {
                        var self = this;
                        self.deferred && self.deferred.empty();
                        self.waitTimer && self.waitTimer.stop();
                        self.loaderTimer && self.loaderTimer.stop();
                        self.animate.timer && self.animate.timer.stop();
                        if (self.animate.timelines) {
                            $.each(self.animate.timelines, function (i, timeline) {
                                if (timeline.progress() < 1) {
                                    timeline.play(-.001, false);
                                }
                            });
                        }
                    }, stop: function () {
                        var self = this;
                        self.pause();
                        self.restore();
                    }, draggable: {
                        init: function (parent) {
                            var self = this;
                            self.parent = parent;
                            self.bind();
                        }, wait: function (callback) {
                            var self = this, loaders = [$.ui.loader.xc8633bedc25cf4b3, $.ui.loader.xcf21b1118fc03a3f];
                            sewii.loader.response(loaders).success(function () {
                                callback && callback();
                            });
                        }, bind: function () {
                            var self = this;
                            self.parent.container.addClass('grab');
                            self.wait(function () {
                                var draggables = Draggable.create(self.parent.container, {
                                    type: 'top',
                                    bounds: self.parent.parent.view,
                                    force3D: false,
                                    zIndexBoost: false,
                                    throwProps: true,
                                    edgeResistance: .85,
                                    onDragEnd: function (e) {
                                        self.parent.position(this.endY);
                                    },
                                    onDrag: function () {
                                        self.parent.moving(this);
                                        self.parent.container.addClass('grabbing');
                                    },
                                    onRelease: function () {
                                        self.parent.container.removeClass('grabbing');
                                    },
                                    onThrowUpdate: function () {
                                        self.parent.moving(this);
                                    },
                                });
                                if (draggables.length) {
                                    self.instance = draggables[0];
                                    self.instance.setPosition = function (position, params) {
                                        params = params || {};
                                        var me = this, callee = arguments.callee,
                                            duration = sewii.ifSet(params.duration, 1),
                                            ease = sewii.ifSet(params.ease, Circ.easeOut);
                                        self.parent.position(position);
                                        callee.times = sewii.ifSet(callee.times, 0) + 1;
                                        self.timelines = sewii.ifSet(self.timelines, {});
                                        self.timelines[callee.times] = new $.ui.animator.timeline().to({
                                            target: me.target,
                                            duration: duration,
                                            params: {
                                                ease: ease,
                                                top: position,
                                                overwrite: 'all',
                                                onUpdate: function () {
                                                    me.y = parseInt($(this.target).css('top')) || 0;
                                                    self.parent.moving(me);
                                                },
                                                onComplete: function () {
                                                    delete self.timelines[callee.times];
                                                    params.complete && params.complete();
                                                }
                                            }
                                        });
                                    };
                                }
                            });
                        }, move: function () {
                            var self = this, args = Array.prototype.slice.call(arguments);
                            self.wait(function () {
                                self.instance.setPosition.apply(self.instance, args);
                            });
                        }, enable: function () {
                            var self = this;
                            self.wait(function () {
                                self.instance && self.instance.enable();
                            });
                        }, disable: function () {
                            var self = this;
                            self.wait(function () {
                                self.instance && self.instance.disable();
                            });
                        }, kill: function () {
                            var self = this;
                            self.wait(function () {
                                self.instance && self.instance.kill();
                            });
                        }, play: function () {
                            var self = this;
                            self.eachTimelines(function (i, timeline) {
                                timeline.play();
                            });
                            self.eachMoveTimelines(function (i, timeline) {
                                timeline.play();
                            });
                        }, pause: function () {
                            var self = this;
                            self.eachTimelines(function (i, timeline) {
                                timeline.pause();
                            });
                            self.eachMoveTimelines(function (i, timeline) {
                                timeline.pause();
                            });
                        }, eachTimelines: function (callback) {
                            var self = this;
                            $.each(self.timelines || {}, callback);
                        }, eachMoveTimelines: function (callback) {
                            var self = this;
                            if (self.parent && self.parent.moving) {
                                $.each(self.parent.moving.timelines || {}, callback);
                            }
                        }
                    },
                }, color: {
                    init: function (parent) {
                        var self = this;
                        self.parent = parent;
                        self.config = {};
                        self.config.box = self.parent.info.find('.background li').css('background-color') || '';
                        self.config.boxAlpha = ((self.toRgba(self.config.box) || {}).a) || 1;
                        self.config.text = self.parent.info.css('color') || '';
                        self.config.reverse = self.parent.info.find('.header .category .type').css('color') || '';
                        self.config.button = self.parent.info.find('.button').css('color') || '';
                        self.config.scrollbar = self.parent.info.find('.scrollbar .track').css('background-color') || '';
                        self.config.draggable = self.parent.info.find('.scrollbar .thumb').css('background-color') || '';
                        self.config.view = self.parent.view.css('background-color') || '';
                        self.config.viewAlpha = ((self.toRgba(self.config.box) || {}).a) || 1;
                        self.config.shadow = self.parent.that.find('.shadow').css('box-shadow') || '';
                        self.config.shadowAlpha = ((self.toRgba(self.config.shadow) || {}).a) || 1;
                    }, change: function (setting, action) {
                        var self = this, color;
                        self.config || self.init($.ui.pages.works.detail);
                        color = $.extend({}, self.config, setting);
                        sewii.each(color, function (name, value) {
                            if (!/^rgba?/i.test(value) && !/^#[a-f\d]+/.test(value)) {
                                value = self.config[name];
                            }
                            self.set(name, value, action);
                        }, 10);
                    }, set: function (name, value, action) {
                        var self = this, callee = arguments.callee, target = [], duration = 1, params = {},
                            releaseTimeline = function () {
                                if (self.timelines) {
                                    delete self.timelines[name];
                                }
                            };
                        self.timelines = sewii.ifSet(self.timelines, {});
                        if (self.timelines[name] && self.timelines[name].progress() < 1) {
                            self.timelines[name].progress(1, false);
                        }
                        switch (name) {
                            case'box':
                                if (!/^rgba/i.test(value)) {
                                    var rgba = self.toRgba(value, self.config.boxAlpha);
                                    value = self.toCss(rgba);
                                }
                                if (sewii.env.browser.msie <= 9) {
                                    callee.call(self, 'box-triangle', value);
                                    target = self.parent.info.find('.background').children().get();
                                    params.backgroundColor = value;
                                    duration = 1;
                                    break;
                                }
                                var backgrounds = self.parent.info.find('.background').children(),
                                    first = backgrounds.eq(0), second = backgrounds.eq(1), third = backgrounds.eq(2),
                                    start = first.css('background-color'), end = value,
                                    gradient = function (target, start, end) {
                                        target.css('background', '-webkit-linear-gradient(    left, ' + start + ', ' + end + ')');
                                        target.css('background', '   -moz-linear-gradient(to right, ' + start + ', ' + end + ')');
                                        target.css('background', '     -o-linear-gradient(to right, ' + start + ', ' + end + ')');
                                        target.css('background', '    -ms-linear-gradient(to right, ' + start + ', ' + end + ')');
                                        target.css('background', '        linear-gradient(to right, ' + start + ', ' + end + ')');
                                    };
                                gradient(second, start, end);
                                first.css('background', start);
                                third.css('background', end);
                                target = first;
                                duration = 1;
                                params.marginLeft = '-=200%';
                                params.onComplete = function () {
                                    releaseTimeline();
                                    first.insertAfter(third);
                                    second.insertAfter(third);
                                    backgrounds.css('margin', 0);
                                };
                                params.onUpdate = function () {
                                    if (arguments.callee.done) return;
                                    var progress = this.progress();
                                    if (action.prev && progress >= .0) {
                                        callee.call(self, 'box-triangle-gradient', value);
                                        arguments.callee.done = true;
                                    } else if (progress >= .4) {
                                        callee.call(self, 'box-triangle-gradient', value);
                                        arguments.callee.done = true;
                                    }
                                };
                                if (action.prev) {
                                    gradient(second, end, start);
                                    third.insertBefore(first);
                                    second.insertBefore(first);
                                    backgrounds.css('margin', 0);
                                    third.css('marginLeft', '-200%');
                                    target = third;
                                    params.marginLeft = '+=200%';
                                }
                                break;
                            case'box-triangle':
                                target.push(self.parent.info.find('.triangle'));
                                params.borderRightColor = value;
                                break;
                            case'box-triangle-gradient':
                                target.push(self.parent.info.find('.triangle'));
                                params.borderRightColor = value;
                                duration = .2;
                                break;
                            case'text':
                                target.push(self.parent.info);
                                params.color = value;
                                break;
                            case'reverse':
                                target.push(self.parent.info.find('.header .category .type'));
                                params.color = value;
                                break;
                            case'button':
                                target.push(self.parent.info.find('.button .line'));
                                params.backgroundColor = value;
                                break;
                            case'scrollbar':
                                target.push(self.parent.info.find('.scrollbar .track'));
                                params.backgroundColor = value;
                                break;
                            case'draggable':
                                target.push(self.parent.info.find('.scrollbar .thumb'));
                                params.backgroundColor = value;
                                break;
                            case'view':
                                if (!/^rgba/i.test(value)) {
                                    var rgba = self.toRgba(value, self.config.viewAlpha);
                                    value = self.toCss(rgba);
                                }
                                target.push(self.parent.view);
                                params.backgroundColor = value;
                                break;
                            case'shadow':
                                var rgba = self.toRgba(value, self.config.shadowAlpha), color = self.toCss(rgba);
                                target.push(self.parent.that.find('.shadow'));
                                params.boxShadow = self.config.shadow.replace(/rgba\([^\)]+\)|#[a-f\d]+/, color);
                                break;
                            default:
                                return;
                        }
                        self.timelines[name] = new $.ui.animator.timeline().to({
                            target: target,
                            duration: duration,
                            params: params,
                            onComplete: function () {
                                releaseTimeline();
                            }
                        });
                    }, toRgba: function (color, alpha) {
                        var self = this, value = null;
                        alpha = sewii.ifSet(alpha, 1);
                        if (/^#[a-f\d]+$/i.test(color)) {
                            value = (function (hex) {
                                var shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
                                hex = hex.replace(shorthandRegex, function (m, r, g, b) {
                                    return r + r + g + g + b + b;
                                });
                                var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
                                return result ? {
                                    r: parseInt(result[1], 16),
                                    g: parseInt(result[2], 16),
                                    b: parseInt(result[3], 16),
                                    a: alpha
                                } : null;
                            }(color));
                        } else if (/rgba?\s*\(/i.test(color)) {
                            var matches = color.match(/\(([^\)]+)\)/),
                                split = matches ? $.trim(matches[1]).split(/\s*,\s*/) : null;
                            value = {r: split[0], g: split[1], b: split[2], a: sewii.ifSet(split[3], alpha)};
                        }
                        return value;
                    }, toCss: function (rgba) {
                        var self = this, css = null;
                        if ($.isPlainObject(rgba)) {
                            css = 'rgba(' + rgba.r + ', ' + rgba.g + ', ' + rgba.b + ', ' + rgba.a + ')';
                            if (sewii.env.browser.msie <= 8) {
                                css = 'rgb(' + rgba.r + ', ' + rgba.g + ', ' + rgba.b + ')';
                            }
                        }
                        return css;
                    }, play: function () {
                        var self = this;
                        if (self.timelines) {
                            $.each(self.timelines, function (i, timeline) {
                                timeline.play();
                            });
                        }
                    }, pause: function () {
                        var self = this;
                        if (self.timelines) {
                            $.each(self.timelines, function (i, timeline) {
                                timeline.pause();
                            });
                        }
                    }, restore: function () {
                        var self = this;
                        if (self.timelines) {
                            $.each(self.timelines, function (i, timeline) {
                                if (timeline.progress() < 1) {
                                    timeline.play(-.001, false);
                                }
                            });
                        }
                    }, stop: function () {
                        var self = this;
                        self.pause();
                        self.restore();
                    },
                }, progressbar: {
                    init: function (parent) {
                        var self = this, progressbar = $('<div class="progressbar" />'), that = progressbar;
                        self.parent = parent;
                        self.that = that;
                        self.progressed = 0;
                        progressbar.appendTo(self.parent.parent.that);
                    }, to: function (progressed) {
                        var self = this;
                        progressed = parseFloat(progressed || 0);
                        self.progressed = progressed >= self.progressed ? progressed : self.progressed;
                        setTimeout(function () {
                            self.tweener = $.ui.animator.tweener.to({
                                target: self.that,
                                duration: .6,
                                params: {
                                    width: self.progressed + '%', overwrite: 'all', onComplete: function () {
                                        if (self.progressed >= 100) {
                                            self.hide();
                                        }
                                    }
                                }
                            });
                        }, 1);
                    }, show: function () {
                        var self = this;
                        self.that.show();
                    }, hide: function (reset) {
                        var self = this;
                        self.that.stop().fadeOut();
                    }, done: function () {
                        var self = this;
                        self.timer && self.timer.stop();
                        self.to(100);
                    }, start: function () {
                        var self = this, steps = [99.8, 95, 90, 80, 70, 60], step = 1;
                        self.restore();
                        (function animate() {
                            var callee = arguments.callee;
                            self.progressed += step;
                            for (var i = 0, max = steps.length; i <= max; i++) {
                                if (self.progressed >= steps[i]) {
                                    if (steps[i] >= steps[0]) {
                                        return self.progressed -= step;
                                    }
                                    self.progressed -= step * (steps[i] / 100);
                                    break;
                                }
                            }
                            if (self.progressed <= 100) {
                                self.to(self.progressed);
                                self.timer = sewii.timer.timeout(callee, 100);
                            }
                        }());
                    }, play: function () {
                        var self = this;
                        self.timer && self.timer.resume();
                        self.tweener && self.tweener.play();
                    }, pause: function () {
                        var self = this;
                        self.timer && self.timer.pause();
                        self.tweener && self.tweener.pause();
                    }, restore: function () {
                        var self = this;
                        self.timer && self.timer.stop();
                        self.tweener && self.tweener.pause();
                        self.that.stop().css({width: self.progressed = 0, opacity: 1, display: 'block'});
                    },
                }, links: {
                    init: function (parent) {
                        var self = this;
                        self.parent = parent;
                    }, build: function () {
                        var self = this, callee = arguments.callee, liker = $('<fb/>', {
                            href: sewii.url.query.modify().toUrl(),
                            layout: 'button',
                            action: 'like',
                            share: false,
                            show_faces: false,
                        }).htmlOuter().replace(/(\<\/?)fb/g, '$1fb:like');
                        self.demo();
                        self.parent.info.find('.links').transparent();
                        liker = self.parent.info.find('.links').find('.like').last().html(liker).get(0);
                        $.ui.facebook.onInited(function () {
                            callee.times = (callee.times || 0) + 1;
                            if (callee.times === 1) {
                                FB.Event.subscribe('edge.create', function (href, widget) {
                                    var id = sewii.url.param('detail');
                                    $.ui.tracker.sendSocial('Facebook', 'Like', id);
                                    $.ui.tracker.sendEvent('Facebook', 'Like', id);
                                });
                            }
                            FB.getLoginStatus(function (response) {
                                if (response.status !== 'connected' && response.status !== 'not_authorized') {
                                }
                            });
                            FB.XFBML.parse(liker, function () {
                                $.ui.animator.tweener.to({
                                    target: self.parent.info.find('.links'),
                                    duration: .3,
                                    params: {alpha: 1, visibility: 'visible',}
                                });
                            });
                        });
                    }, destroy: function () {
                        var self = this;
                        $.ui.animator.tweener.to({
                            target: self.parent.info.find('.links'),
                            duration: .1,
                            params: {
                                alpha: 0, visibility: 'hidden', onComplete: function () {
                                    $(this.target).find('.like').children().remove();
                                }
                            }
                        });
                    }, demo: function () {
                        var self = this;
                        self.parent.info.find('.demo').attr('href', function (i, href) {
                            href ? $(this).show() : $(this).hide();
                            return $(this).data('href', href) && null;
                        }).unbind('click').click(function () {
                            var uri = $(this).data('href'), id = sewii.url.param('detail'), target = '_blank';
                            if (uri) {
                                $.ui.tracker.sendEvent('Works', 'Link', id);
                                sewii.helper.open(uri, {target: target});
                            }
                            return false;
                        });
                    },
                }, deferrer: function () {
                    var queue = [];
                    this.add = function (callback) {
                        queue.push(callback);
                    };
                    this.execute = function () {
                        sewii.each(queue, function (i, callback) {
                            sewii.isFunction(callback) && callback();
                        });
                        this.empty();
                    };
                    this.empty = function () {
                        queue = [];
                    };
                },
            }, water: {
                init: function (parent) {
                    var self = this;
                    if (!self.isSupport()) return;
                    self.parent = parent;
                    self.blackFillStyle = 'rgba(0, 0, 0, 0)';
                    self.whiteFillStyle = 'rgba(255, 255, 255, .01)';
                    self.bulid();
                    self.resize();
                    self.mouseMove();
                    self.keyDown();
                }, bulid: function () {
                    var self = this;
                    self.quality = 4;
                    self.width = Math.floor($(window).width() / self.quality);
                    self.height = Math.floor($(window).height() / self.quality);
                    self.canvas = $('<canvas class="water"/>').invisible().appendTo(self.parent.that).get(0);
                    self.canvas.width = self.width;
                    self.canvas.height = self.height;
                    self.context = self.canvas.getContext('2d');
                    self.context.fillStyle = self.whiteFillStyle;
                    self.context.fillRect(0, 0, self.width, self.height);
                    self.imageData = self.context.getImageData(0, 0, self.width, self.height);
                    self.buffer1 = [];
                    self.buffer2 = [];
                    for (var i = 0; i < self.width * self.height; i++) {
                        self.buffer1[i] = 0;
                        self.buffer2[i] = i > self.width && i < (self.width * self.height) - self.width && Math.random() > 0.995 ? 255 : 0;
                    }
                }, resize: function () {
                    var self = this;
                    $(window).resized({
                        callback: function () {
                            if (self.started && self.process && self.canvas && self.context) {
                                self.width = Math.floor($(window).width() / self.quality);
                                self.height = Math.floor($(window).height() / self.quality);
                                self.canvas.width = self.width;
                                self.canvas.height = self.height;
                                self.context.fillStyle = self.whiteFillStyle;
                                self.context.fillRect(0, 0, self.width, self.height);
                                self.imageData = self.context.getImageData(0, 0, self.width, self.height);
                                self.buffer1 = [];
                                self.buffer2 = [];
                                for (var i = 0; i < self.width * self.height; i++) {
                                    self.buffer1[i] = self.buffer2[i] = 0;
                                }
                            }
                        }, delay: 100, now: false,
                    });
                }, start: function (callback) {
                    var self = this;
                    if (!self.isSupport()) return;
                    self.started = true;
                    self.onPlayEnd = callback;
                    self.loop.times = 0;
                    self.opaque();
                    self.play();
                }, startWithoutDark: function (callback) {
                    var self = this;
                    self.start(callback);
                    $(self.canvas).addClass('without');
                }, play: function () {
                    var self = this;
                    if (!self.isSupport()) return;
                    if (!self.started) return;
                    self.callback.timer && self.callback.timer.resume();
                    $.ui.animator.requestAnimationFrame(function () {
                        self.loop((self.process = Math.random()));
                        $(self.canvas).visible().show();
                    });
                }, pause: function () {
                    var self = this;
                    if (!self.isSupport()) return;
                    if (!self.started) return;
                    self.process = null;
                    self.callback.timer && self.callback.timer.pause();
                    $(self.canvas).stop(true);
                }, stop: function () {
                    var self = this;
                    if (!self.isSupport()) return;
                    if (!self.started) return;
                    self.started = false;
                    self.process = null;
                    self.onPlayEnd = null;
                }, restore: function () {
                    var self = this;
                    if (!self.isSupport()) return;
                    if (!self.canvas) return;
                    self.stop();
                    self.destroy();
                    self.bulid();
                }, destroy: function () {
                    var self = this;
                    if (self.canvas) {
                        self.callback.timer && self.callback.timer.stop();
                        self.context.clearRect(0, 0, self.width, self.height);
                        $(self.canvas).stop(true).remove();
                        delete self.canvas, self.context, self.imageData, self.buffer1, self.buffer2;
                    }
                }, mouseMove: function () {
                    var self = this, callee = arguments.callee;
                    $(window).mousemove(function (e) {
                        if (!self.parent.isActive()) return;
                        self.mouseX = Math.floor(e.clientX / self.quality);
                        self.mouseY = Math.floor(e.clientY / self.quality);
                        callee.moved = true;
                    });
                }, keyDown: function (e) {
                    var self = this;
                    $(window).keydown(function (e) {
                        if (!self.parent.isActive()) return;
                        self.emit(Math.floor(Math.random() * self.width), Math.floor(Math.random() * self.height));
                    });
                }, emit: function (x, y) {
                    var self = this;
                    if (!self.mouseMove.moved) return;
                    self.buffer1[x + (y * self.width)] = 255;
                }, loop: function (process) {
                    var self = this, callee = arguments.callee, min = 0, max = 255, temp;
                    self.emit(self.mouseX, self.mouseY);
                    for (var i = self.width; i < (self.width * self.height) - self.width; i++) {
                        self.buffer2[i] = ((self.buffer1[i - 1]
                            + self.buffer1[i + 1]
                            + self.buffer1[i - self.width]
                            + self.buffer1[i + self.width]) >> 1) - self.buffer2[i];
                        self.buffer2[i] -= self.buffer2[i] >> 20;
                        self.imageData.data[(i * 4) + 3] = self.buffer2[i] > max ? max : self.buffer2[i] < min ? min : self.buffer2[i];
                    }
                    temp = self.buffer1;
                    self.buffer1 = self.buffer2;
                    self.buffer2 = temp;
                    self.context.putImageData(self.imageData, 0, 0);
                    if (self.process && self.process === process) {
                        $.ui.animator.requestAnimationFrame(function () {
                            callee.call(self, process);
                        });
                    }
                    (function () {
                        callee.times = sewii.ifSet(callee.times, 0) + 1;
                        var dissolveTimes = 150;
                        if (self.isSupportOverlay()) {
                            if (callee.times >= dissolveTimes) {
                                self.onDissolve();
                                self.callback();
                            }
                        } else if (callee.times >= dissolveTimes) {
                            self.onDissolve();
                            $(self.canvas).fadeOut({
                                duration: 600, progress: function (animation, progress) {
                                    if (!arguments.callee.done && progress >= .5) {
                                        arguments.callee.done = true;
                                        self.callback();
                                    }
                                }, complete: function () {
                                    self.stop();
                                }
                            });
                        }
                    }());
                }, callback: function () {
                    var self = this, callee = arguments.callee;
                    if (self.onPlayEnd) {
                        var onPlayEnd = self.onPlayEnd, delay = 600;
                        delete self.onPlayEnd;
                        callee.timer = sewii.timer.timeout(function () {
                            onPlayEnd();
                        }, delay);
                    }
                }, onDissolve: function () {
                    var self = this;
                    self.transparent();
                }, opaque: function () {
                    var self = this;
                    $(self.canvas).addClass('dark');
                }, transparent: function () {
                    var self = this;
                    $(self.canvas).removeClass('dark');
                }, isSupport: function () {
                    var self = this;
                    return false;
                    if (sewii.env.browser.safari < 6 || sewii.env.browser.msie < 10 || sewii.env.device.mobile) {
                        return false;
                    }
                    return $.ui.helper.support.hightPerformance();
                }, isSupportOverlay: function () {
                    var self = this, callee = arguments.callee;
                    if (sewii.isSet(callee.cache)) return callee.cache;
                    return (callee.cache = 'pointerEvents' in document.documentElement.style && !(sewii.env.browser.msie <= 10) && !(sewii.env.device.mobile) && 1);
                }
            }
        }, contact: {
            id: 'contact', init: function (parent, callback) {
                var self = this, that = $('#' + self.id), navigation = that.find('> .navigation'),
                    boxes = that.find('> .box');
                self.parent = parent;
                self.that = that.visible();
                self.navigation = navigation;
                self.boxes = boxes;
                self.route();
                self.resize();
                self.hover();
                self.click();
                self.open();
                self.close();
                self.blur();
                self.keyboard();
                self.map.init(self);
                self.draggable.init(self);
                self.placeholder.init(self);
                self.mail.init(self);
                self.onInit(callback);
            }, isActive: function () {
                var self = this;
                return self.parent.isUnit('contact');
            }, getUri: function () {
                var self = this;
                return $.ui.pages.common.navigator.getUri(self.id);
            }, title: function () {
                var self = this;
                return self.that.children('.heading').text() || null;
            }, route: function () {
                var self = this;
                sewii.router.routing.on(self.id, function (event) {
                    $.ui.pages.common.navigator.go(event.route.unit, {
                        enterTiming: .65,
                        cancelable: false,
                        event: event
                    });
                });
            }, onPreload: function (event) {
                var self = this, routing = event;
                $.ui.pages.common.preloader.load({
                    id: self.id, routing: routing, callback: function (event) {
                        var preloader = this, isInited = self.init.called, callback = function () {
                            isInited || preloader.hide();
                            sewii.callback(routing, 'callback');
                        };
                        if (!isInited) {
                            self.init.called = true;
                            self.init($.ui.pages, callback);
                            return;
                        }
                        callback();
                    }
                });
            }, onCome: function (event) {
                var self = this, id = self.getId();
                if (!self.map.play.called) {
                    self.map.setStyle();
                    self.map.animation.restore();
                }
            }, onEnter: function (event) {
                var self = this, callee = arguments.callee, id = self.getId(),
                    target = self.navigation.find('a[data-id="' + id + '"]'), current = self.currently(),
                    box = self.boxes.filter('[data-id="' + id + '"]'), isExists = target.length && box.length,
                    hasSetId = !!self.getId(false),
                    subTitle = hasSetId ? box.children('.heading').text() || null : null;
                sewii.history.title(self.title(), -1);
                sewii.history.title(subTitle, -2);
                $.ui.tracker.sendPageView();
                if (!self.map.play.called) {
                    self.map.play();
                } else if (isExists) {
                    if (self.map.animation.isPlaying()) {
                        var currentPlaying = self.map.animation.getCurrentPlaying();
                        if (currentPlaying) self.close(currentPlaying);
                    }
                    if (current !== id || self.isClosed()) {
                        self.open(id);
                    }
                }
                callee.id = id;
            }, onLeave: function (event) {
                var self = this;
                self.mail.pause();
                self.map.pause();
                self.open.pause && self.open.pause();
                self.close.pause && self.close.pause();
            }, onExeunt: function (event) {
                var self = this;
                self.mail.restore();
                self.map.restore();
                self.open.restore && self.open.restore();
                self.close.restore && self.close.restore();
            }, onInit: function (callback) {
                var self = this;
                callback && self.wait(function () {
                    var onDone = function () {
                        $.ui.animator.requestAnimationFrame(function () {
                            callback && callback();
                        });
                    };
                    if (!self.map.done.isCalled) {
                        return sewii.event.once(self.map.EVENT_API_DONE, function () {
                            onDone();
                        });
                    }
                    onDone();
                });
            }, wait: function (callback) {
                var self = this,
                    loaders = [$.ui.loader.maps, $.ui.loader.xc8633bedc25cf4b3, $.ui.loader.xcf21b1118fc03a3f, $.ui.loader.x43563f9b64cce748, $.ui.loader.x2540ecbd720b3994, $.ui.loader.xa33c75f3b2b160e2, $.ui.loader.x04e645a6cb5bd628];
                sewii.loader.response(loaders).success(function () {
                    callback && callback();
                });
            }, resize: function () {
                var self = this;
                $(window).resized({
                    callback: function () {
                        var clientWidth = $(window).width(), clientHeight = $(window).height(),
                            headerHeight = $('#header').height(), footHeight = $('#footer').height(),
                            viewportHeight = clientHeight - headerHeight - footHeight, basetViewportHeight = 980,
                            smallViewportHeight = 800;
                        self.boxes.find('input[type="text"], textarea, select, > .view, .routes .list').css('height', function () {
                            var ratio = $(this).data('ratio') || ($(this).height() / (basetViewportHeight - headerHeight - footHeight));
                            $(this).data('ratio', ratio);
                            if ($(this).is(':input')) {
                                clientHeight <= smallViewportHeight && (ratio *= .75);
                            }
                            return viewportHeight * ratio;
                        });
                        self.that.toggleClass('medium-size', clientWidth <= 1366);
                        self.that.toggleClass('small-size', clientWidth <= 1280);
                        self.that.toggleClass('mini-size', clientWidth <= 1024);
                        self.that.toggleClass('micro-size', clientWidth <= 800);
                        self.that.toggleClass('nano-size', clientWidth <= 600);
                        self.boxes.toggleClass('mini-size', clientHeight <= 700);
                        self.boxes.toggleClass('micro-size', clientHeight <= 600);
                        self.boxes.toggleClass('nano-size', clientHeight <= 500);
                    }, delay: function () {
                        return !self.isActive();
                    }, now: true,
                });
            }, hover: function () {
                var self = this;
                self.navigation.find('a').bind({
                    mouseenter: function () {
                        var me = $(this);
                        $.ui.sound.play('effect-07');
                    }
                });
            }, click: function () {
                var self = this;
                self.navigation.find('a').attr('href', function (i, href) {
                    return $(this).data('href', href) && null;
                }).click(function (event, params) {
                    var me = $(this), uri = me.data('href'), id = me.data('id');
                    if (!me.hasClass('selected')) {
                        if (self.isClosed() && self.currently() === id) {
                            self.open(id);
                        } else {
                            uri && sewii.router.routing.go(uri);
                        }
                    }
                    return false;
                });
            }, open: function (id) {
                var self = this, callee = arguments.callee;
                if (!sewii.isSet(id)) {
                    return self.boxes.each(function () {
                        var box = $(this);
                        callee.left = (box.position().left / $(window).width() * 100) + '%';
                        callee.top = (box.position().top / $(window).height() * 100) + '%';
                        box.data('left', callee.left).data('top', callee.top);
                    });
                }
                var items = self.navigation.find('a'), item = items.filter('[data-id="' + id + '"]'),
                    box = self.boxes.filter('[data-id="' + id + '"]'),
                    offsetTop = self.navigation.height() + item.height() / 2;
                items.removeClass('selected');
                item.addClass('selected');
                items.mouseleave();
                callee.timelines = callee.timelines || {};
                callee.timelines['in'] = new $.ui.animator.timeline().fromTo({
                    target: box,
                    duration: 1,
                    from: {
                        opacity: 0,
                        scale: sewii.env.os.android ? 1 : 0,
                        left: item.offset().left,
                        top: item.offset().top - offsetTop,
                        visibility: 'visible',
                        onStart: function () {
                            switch (id) {
                                case'mail':
                                    self.mail.reset();
                                    break;
                                case'street':
                                    self.map.street.hide();
                                    break;
                                case'route':
                                    self.map.route.hide();
                                    break;
                            }
                        }
                    },
                    to: {
                        ease: Circ.easeInOut,
                        opacity: 1,
                        scale: 1,
                        left: callee.left,
                        top: callee.top,
                        onComplete: function () {
                            switch (id) {
                                case'street':
                                    self.map.street.show();
                                    break;
                                case'route':
                                    self.map.route.show();
                                    break;
                            }
                        }
                    },
                }).addCallback({
                    callback: function () {
                        self.map.setStyle(id);
                        $.ui.sound.play('effect-10');
                    }, position: '-=.6'
                });
                items.not(item).each(function () {
                    var itemId = $(this).data('id');
                    callee.timelines['out'] = callee.timelines['out'] || {};
                    callee.timelines['out'][itemId] = $.ui.animator.tweener.to({
                        target: self.boxes.filter('[data-id="' + itemId + '"]'),
                        duration: 1,
                        params: {ease: Circ.easeInOut, opacity: 0, scale: 0, x: 0, y: 0, left: '-15%'},
                    });
                });
                callee.pause = function () {
                    callee.timelines['in'].pause();
                    sewii.each(callee.timelines['out'], function (i, timeline) {
                        timeline.pause();
                    });
                };
                callee.restore = function () {
                    callee.timelines['in'].pause(0);
                    sewii.each(callee.timelines['out'], function (i, timeline) {
                        timeline.pause(0);
                    });
                };
            }, close: function (id) {
                var self = this, callee = arguments.callee, button = self.boxes.find('.close'), boundClass = 'bound';
                if (!button.hasClass(boundClass)) {
                    button.addClass(boundClass).attr('href', function (i, href) {
                        return $(this).data('href', href) && null;
                    }).click(function () {
                        var me = $(this), box = me.parents('.box'), id = box.data('id'),
                            items = self.navigation.find('a'), item = items.filter('[data-id="' + id + '"]'),
                            offsetTop = self.navigation.height() + item.height() / 2;
                        callee.timeline = new $.ui.animator.timeline().to({
                            target: box,
                            duration: 1,
                            params: {
                                overwrite: true,
                                ease: Circ.easeInOut,
                                opacity: 0,
                                scale: 0,
                                x: 0,
                                y: 0,
                                left: item.offset().left,
                                top: item.offset().top - offsetTop
                            },
                        }).addCallback({
                            callback: function () {
                                item.removeClass('selected').mouseleave();
                            }, position: '-=.3'
                        });
                        callee.pause = function () {
                            callee.timeline.pause();
                        };
                        callee.restore = function () {
                            callee.timeline.pause(0);
                        };
                        return false;
                    });
                    $(window).keydown(function (e) {
                        if (e.keyCode === 27) {
                            if (!self.isActive()) return;
                            if (!self.isOpened()) return;
                            if (self.map.animation.isPlaying()) return;
                            var current = self.currently();
                            callee.call(self, current);
                            return false;
                        }
                    });
                } else self.boxes.filter('[data-id="' + id + '"]').find('.close').trigger('click');
            }, keyboard: function () {
                var self = this, callee = arguments.callee;
                $(window).bind('directionKeyDown', function (e, info) {
                    if (!self.isActive()) return;
                    if (self.map.animation.isPlaying()) return;
                    var links = self.navigation.find('a'),
                        current = links.filter('[data-id="' + self.currently() + '"]'), target = null;
                    if (current.length && links.length > 1) {
                        if (info.isLeft || info.isUp) {
                            var target = current.parent().prev();
                            if (!target.length) target = links.last().parent();
                        }
                        if (info.isRight || info.isDown) {
                            var target = current.parent().next();
                            if (!target.length) target = links.first().parent();
                        }
                        if (target && target.length) {
                            clearTimeout(callee.timer);
                            callee.timer = setTimeout(function () {
                                target.children('a').mouseenter().click();
                            }, 100);
                        }
                    }
                });
            }, blur: function () {
                var self = this, map = self.that.find('.map');
                return;
                if (!sewii.env.browser.webkit) return;
                if (!$.ui.helper.support.hightPerformance()) return;
                map.css({webkitTransition: '-webkit-filter 1s'});
                var mousedown = false;
                self.boxes.find('input[type="text"], textarea, select').mousedown(function () {
                    mousedown = true;
                });
                self.boxes.find(':input').focusin(function () {
                    if (mousedown) {
                        map.css({webkitFilter: 'blur(5px)'});
                        mousedown = false;
                    }
                }).focusout(function () {
                    var blur = map.css('webkitFilter');
                    if (/blur/.test(blur)) {
                        map.css({webkitFilter: 'blur(0px)'});
                    }
                });
            }, isOpened: function () {
                var self = this, items = self.navigation.find('a');
                return !!items.filter('.selected').length;
            }, isClosed: function () {
                var self = this;
                return !self.isOpened();
            }, getId: function (defaultly) {
                defaultly = sewii.ifSet(defaultly, true);
                var self = this, id = sewii.url.param('us'), nav = self.navigation.find('a[data-id="' + id + '"]');
                if (defaultly && !nav.length) {
                    id = self.navigation.find('a:first').data('id');
                }
                return id;
            }, currently: function () {
                var self = this;
                return self.onEnter.id || self.navigation.find('a.selected').data('id') || self.getId();
            }, draggable: {
                init: function (parent) {
                    var self = this;
                    self.parent = parent;
                    self.wait(function () {
                        self.bind();
                    });
                }, bind: function () {
                    var self = this;
                    self.parent.boxes.disableSelection().each(function (i) {
                        var me = $(this), draggable;
                        me.addClass('grab');
                        draggable = Draggable.create(me, {
                            type: 'x,y',
                            throwProps: true,
                            zIndexBoost: false,
                            force3D: true,
                            edgeResistance: .5,
                            bounds: self.parent.that,
                            onDragStart: function () {
                                $(this.target).addClass('grabbing');
                            },
                            onDragEnd: function () {
                                $(this.target).removeClass('grabbing');
                            },
                        })[0];
                    });
                }, wait: function (callback) {
                    var self = this, loaders = [$.ui.loader.xc8633bedc25cf4b3, $.ui.loader.xcf21b1118fc03a3f];
                    sewii.loader.response(loaders).success(function () {
                        callback && callback();
                    });
                },
            }, placeholder: {
                init: function (parent) {
                    var self = this, that = parent.boxes;
                    self.parent = parent;
                    self.that = that;
                    self.patch();
                }, patch: function () {
                    var self = this;
                    if (!self.isSupport()) {
                        self.that.find('[placeholder]').focus(function () {
                            self.invisible(this);
                        }).blur(function () {
                            self.visible(this);
                        }).blur();
                    }
                }, invisible: function (inputs) {
                    var self = this;
                    self.isSupport() || $(inputs).each(function () {
                        var input = $(this);
                        if (input.val() === input.attr('placeholder')) {
                            input.removeClass('placeholder').val('');
                        }
                    });
                }, visible: function (inputs) {
                    var self = this;
                    self.isSupport() || $(inputs).each(function () {
                        var input = $(this);
                        if (input.val() === '' || input.val() === input.attr('placeholder')) {
                            input.addClass('placeholder').val(input.attr('placeholder'));
                        }
                    });
                }, clear: function (inputs) {
                    var self = this;
                    self.isSupport() || $(inputs).each(function () {
                        var input = $(this);
                        if (input.val() === input.attr('placeholder')) {
                            input.val('');
                        }
                    });
                }, isSupport: function () {
                    var self = this;
                    return 'placeholder' in document.createElement('input');
                }
            }, mail: {
                init: function (parent) {
                    var self = this, that = parent.boxes.filter('[data-id="mail"]'), form = that.find('form');
                    self.parent = parent;
                    self.that = that;
                    self.form = form;
                    self.bind();
                    self.mailto();
                    self.reset();
                }, wait: function (callback) {
                    var self = this, loaders = [$.ui.loader.xa33c75f3b2b160e2, $.ui.loader.x04e645a6cb5bd628];
                    sewii.loader.response(loaders).success(function () {
                        callback && callback();
                    });
                }, bind: function () {
                    var self = this;
                    self.wait(function () {
                        self.form.submit(function () {
                            self.parent.placeholder.clear(self.form.find('[placeholder]'));
                        }).validate({
                            rules: {
                                name: {required: true,},
                                email: {required: true, email: true},
                                phone: {required: true,},
                                subject: {required: true,},
                                message: {required: true,},
                            }, invalidHandler: function (error, element) {
                                self.parent.placeholder.visible(self.form.find('[placeholder]'));
                            }, submitHandler: function (form) {
                                (function () {
                                    var $form = $(form), value = (new Date()).getTime(),
                                        name = '-validator-contact-mail-',
                                        input = $form.find('input[name="' + name + '"]');
                                    (input.length) ? input.val(value) : $form.append($('<input />', {
                                        type: 'hidden',
                                        name: name,
                                        value: value
                                    }));
                                }());
                                $(form).ajaxSubmit({
                                    success: function (data) {
                                        if (/^200/.test(data)) {
                                            $.ui.tracker.sendEvent('Contact', 'Submit', 'Success');
                                            self.notify('success');
                                            $(form).resetForm();
                                            return false;
                                        }
                                        this.error();
                                    }, error: function () {
                                        $.ui.tracker.sendEvent('Contact', 'Submit', 'Error');
                                        self.notify('error');
                                    }, complete: function () {
                                        $.overlay.hide();
                                    }, beforeSend: function () {
                                        $.overlay.show();
                                    }
                                });
                            }
                        });
                        self.that.find('form .submit').each(function (i, selector) {
                            var $button = $(this),
                                cloned = $button.htmlOuter().replace(/type="?button"?/ig, 'type="submit"');
                            $button.replaceWith(cloned);
                        });
                        self.that.find(':input').bind('keydown', function (e) {
                            if (e.ctrlKey && e.keyCode === 65) {
                                $(this).focus().select();
                            }
                        });
                    });
                }, mailto: function () {
                    var self = this;
                    self.that.find('.email dd').text(function (i, value) {
                        value = String(value).replace('(at)', '@');
                        $(this).click(function () {
                            sewii.helper.redirect('mailto:' + value);
                            $.ui.tracker.sendEvent('Mailto', 'Click', value);
                            return false;
                        });
                        return value;
                    });
                }, notify: function (type) {
                    var self = this, message = self.that.find('.message');
                    message.stop(true).slideUp(200).filter('.' + type).slideDown(600, $.ui.animator.easing('easeOutBack'));
                }, reset: function () {
                    var self = this;
                    self.that.find('.message').hide();
                }, play: function () {
                    var self = this;
                }, pause: function () {
                    var self = this;
                }, restore: function () {
                    var self = this, validator = self.form.data('validator');
                    if (validator) {
                        validator.resetForm();
                        validator.elements().each(function () {
                            var element = $(this), errorClass = validator.settings.errorClass,
                                validClass = validator.settings.validClass;
                            element.removeClass(errorClass).addClass(validClass);
                            if (element.rules().remote) {
                                element.data('previousValue', null);
                            }
                        });
                    }
                    self.reset();
                }
            }, map: {
                EVENT_API_CALLBACK: 'contact.map.api.callback',
                EVENT_API_DONE: 'contact.map.api.done',
                init: function (parent) {
                    var self = this, that = parent.that.find('.map'), inner = that.find('> .inner'),
                        canvas = inner.find('> .canvas'), markers = canvas.find('.marker');
                    self.parent = parent;
                    self.that = that;
                    self.inner = inner;
                    self.markers = markers.clone();
                    self.canvas = canvas.empty();
                    self.timelines = {};
                    self.ready($.proxy(self.build, self));
                },
                load: function (callback) {
                    var self = this, url = 'https://maps.googleapis.com/maps/api/js',
                        key = 'AIzaSyBClXvuVD8lQ6IfmG32ZcuURsbctjuxtF0', version = '3.exp',
                        callback = '$.ui.pages.contact.map.callback', args = '?v=' + sewii.url.encode(version)
                        + '&key=' + sewii.url.encode(key)
                        + '&callback=' + sewii.url.encode(callback);
                    return sewii.loader.include.path(url + args);
                },
                build: function () {
                    var self = this, place = new google.maps.LatLng(24.1699719, 120.6833493),
                        center = new google.maps.LatLng(24.1759719, 120.6773493), zoom = 15, mapOptions = {
                            center: center,
                            mapTypeId: location.hostname,
                            overviewMapControl: false,
                            rotateControl: false,
                            streetViewControl: false,
                            panControl: false,
                            zoomControl: false,
                            navigationControl: false,
                            mapTypeControl: false,
                            scaleControl: false,
                            scrollwheel: true,
                            zoom: zoom,
                            minZoom: 8
                        };
                    self.place = place;
                    self.center = center;
                    self.zoom = zoom;
                    self.id = mapOptions.mapTypeId;
                    self.map = new google.maps.Map(self.canvas.get(0), mapOptions);
                    google.maps.event.addListenerOnce(self.map, 'tilesloaded', function () {
                        self.done();
                    });
                    google.maps.event.addListener(self.map, 'zoom_changed', function () {
                        self.zoomChanged();
                    });
                    google.maps.event.addListener(self.map, 'center_changed', function () {
                        self.centerChanged();
                    });
                    (function () {
                        self.marker = new google.maps.Marker({
                            position: place,
                            map: self.map,
                            title: self.markers.filter('.marker.building').attr('alt'),
                            draggable: true,
                            zIndex: 1
                        });
                        google.maps.event.addListener(self.marker, 'drag', function (e) {
                            self.fixCirclePosition();
                            return false;
                        });
                    }());
                    (function () {
                        self.circle = new google.maps.Marker({
                            position: place,
                            map: self.map,
                            draggable: true,
                            zIndex: 0
                        });
                        google.maps.event.addListener(self.circle, 'drag', function (e) {
                            self.fixMarkerPosition();
                            return false;
                        });
                    }());
                    self.setStyle();
                    self.resize();
                    self.animation.init(self);
                    self.street.init(self);
                    self.route.init(self);
                },
                callback: function () {
                    var self = this, callee = arguments.callee;
                    callee.isCalled = true;
                    sewii.event.fire(self.EVENT_API_CALLBACK, self);
                },
                ready: function (callback) {
                    var self = this, callee = arguments.callee;
                    if (!self.callback.isCalled) {
                        return sewii.event.once(self.EVENT_API_CALLBACK, function () {
                            callee.call(self, callback);
                        });
                    }
                    callback && callback();
                },
                done: function () {
                    var self = this, callee = arguments.callee;
                    self.fixCirclePosition();
                    self.watermarker();
                    self.inner.opaque();
                    callee.isCalled = true;
                    sewii.event.fire(self.EVENT_API_DONE, self);
                },
                resize: function () {
                    var self = this;
                    $(window).resized({
                        callback: function () {
                            if (self.map) {
                                google.maps.event.trigger(self.map, "resize");
                                self.map.setCenter(self.center);
                            }
                        }, delay: function () {
                            return !self.parent.isActive();
                        }, now: false
                    });
                },
                zoomChanged: function () {
                    var self = this;
                    if (!self.parent.isActive()) return;
                    self.fixCirclePosition();
                },
                centerChanged: function () {
                    var self = this;
                    if (!self.parent.isActive()) return;
                    self.fixCirclePosition();
                    self.animateMarker();
                    self.route.animateMarker();
                },
                setStyle: function (id) {
                    var self = this, callee = arguments.callee, profiles = {
                        mail: {
                            background: '#581200',
                            map: [{"stylers": [{"invert_lightness": true}, {"weight": 0}, {"gamma": 0}, {"saturation": 0}, {"lightness": 5}, {"hue": "#d64016"}]}],
                            icon: {
                                marker: self.markers.filter('.marker.marker-orange').attr('src'),
                                circle: self.markers.filter('.marker.circle-orange').attr('src')
                            },
                        },
                        route: {
                            background: '#1b2414',
                            map: [{"stylers": [{"invert_lightness": true}, {"weight": 0}, {"gamma": 0}, {"saturation": 0}, {"lightness": 5}, {"hue": "#2d6405"}]}],
                            icon: {
                                marker: self.markers.filter('.marker.marker-green').attr('src'),
                                circle: self.markers.filter('.marker.circle-green').attr('src')
                            }
                        },
                        street: {
                            background: '#141f24',
                            map: [{"stylers": [{"invert_lightness": true}, {"weight": 0}, {"gamma": 0}, {"saturation": 0}, {"lightness": 5}, {"hue": "#0a99d6"}]}],
                            icon: {
                                marker: self.markers.filter('.marker.marker-blue').attr('src'),
                                circle: self.markers.filter('.marker.circle-blue').attr('src')
                            }
                        }
                    };
                    switch ($.ui.pages.getNamespace()) {
                        case'minmax':
                            $.extend(true, profiles, {
                                mail: {
                                    background: '#210208',
                                    map: [{"stylers": [{"invert_lightness": true}, {"weight": 0}, {"gamma": 0}, {"saturation": 0}, {"lightness": 8}, {"hue": "#bc0024"}]}],
                                }
                            });
                            break;
                    }
                    id = id || self.parent.getId();
                    self.that.removeClass('mail route street').addClass(id);
                    var profile = profiles[id];
                    if (self.map && profile) {
                        self.canvas.importantCss('background-color', profile.background);
                        var styledMapType = new google.maps.StyledMapType(profile.map);
                        self.map.mapTypes.set(self.id, styledMapType);
                        if (sewii.env.browser.firefox || sewii.env.browser.webkit) {
                            callee.callTimes = sewii.ifSet(callee.callTimes, 0) + 1;
                            if (callee.callTimes > 1) {
                                var delay = 100;
                                self.getMarker(self.marker, function (marker) {
                                    setTimeout(function () {
                                        marker.find('img').attr('src', profile.icon.marker);
                                        self.marker.getIcon = function () {
                                            return profile.icon.marker;
                                        };
                                    }, delay);
                                });
                                self.getMarker(self.circle, function (circle) {
                                    setTimeout(function () {
                                        circle.find('img').attr('src', profile.icon.circle);
                                        self.circle.getIcon = function () {
                                            return profile.icon.circle;
                                        };
                                    }, delay);
                                });
                                return;
                            }
                        }
                        self.marker.setIcon(profile.icon.marker);
                        self.circle.setIcon(profile.icon.circle);
                    }
                },
                watermarker: function (action) {
                    var self = this, watermarker = self.that.find('.watermarker');
                    action === 1 && watermarker.visible();
                    action === 0 && watermarker.invisible();
                    if (!watermarker.hasClass('build')) {
                        watermarker.addClass('build').text(new Array(300).join(' ' + watermarker.text() + ' ')).css({transform: 'rotate(35deg)'}).show();
                    }
                },
                fixCirclePosition: function () {
                    var self = this;
                    self.getMarker(self.circle, function (circle) {
                        self.getMarker(self.marker, function (marker) {
                            setTimeout(function () {
                                var radius = circle.height() / 2, left = parseInt(marker.css('left')) - radius / 2 - 20,
                                    top = parseInt(marker.css('top')) - radius / 2 - 20;
                                circle.importantCss('left', left + 'px').importantCss('top', top + 'px').visible();
                                if (!self.marker.getVisible()) {
                                    circle.invisible();
                                }
                            }, 0);
                        });
                    });
                },
                fixMarkerPosition: function () {
                    var self = this;
                    self.getMarker(self.circle, function (circle) {
                        self.getMarker(self.marker, function (marker) {
                            setTimeout(function () {
                                var left = parseInt(circle.css('left'))
                                    + circle.width() / 2 - marker.width() / 2, top = parseInt(circle.css('top'))
                                    + circle.height() / 2 - marker.height() / 2;
                                marker.importantCss('left', left + 'px').importantCss('top', top + 'px').visible();
                                if (!self.circle.getVisible()) {
                                    marker.invisible();
                                }
                            }, 0);
                        });
                    });
                },
                animateMarker: function (action) {
                    var self = this, callee = arguments.callee;
                    if (action === -1) {
                        callee.paused = true;
                        self.timelines.jump && self.timelines.jump.pause(0);
                        self.timelines.rotate && self.timelines.rotate.pause(0);
                    } else if (action === 0) {
                        callee.paused = true;
                        self.timelines.jump && self.timelines.jump.pause();
                        self.timelines.rotate && self.timelines.rotate.pause();
                    } else {
                        action === 1 && (callee.paused = false);
                        callee.paused || self.timelines.jump && self.timelines.jump.play();
                        callee.paused || self.timelines.rotate && self.timelines.rotate.play();
                    }
                    self.getMarker(self.marker, function (marker) {
                        if (marker.hasClass('animated')) return;
                        marker.addClass('animated');
                        (function () {
                            if (self.timelines.jump) {
                                self.timelines.jump.pause().kill();
                                delete self.timelines.jump;
                            }
                            if (self.timelines.rotate) {
                                self.timelines.rotate.pause().kill();
                                delete self.timelines.rotate;
                            }
                        }());
                        self.timelines.jump = new $.ui.animator.timeline({yoyo: true, repeat: -1}).to({
                            target: marker,
                            duration: 1,
                            params: {y: 8}
                        });
                        if (sewii.env.browser.safari) return;
                        self.timelines.rotate = new $.ui.animator.timeline({
                            repeat: -1,
                            repeatDelay: 10,
                            delay: 5
                        }).fromTo({
                            target: marker,
                            duration: 1,
                            from: {transformPerspective: 800, ransformOrigin: '50% 50% 0', rotationY: 0},
                            to: {ease: Circ.easeInOut, transformOrigin: '50% 50% 0', rotationY: 360},
                            position: '-=1'
                        });
                    });
                },
                getMarker: function (marker, callback, retry, speed, times) {
                    var self = this;
                    if (marker) {
                        retry = sewii.ifSet(retry, 20);
                        speed = sewii.ifSet(speed, 5);
                        times = sewii.ifSet(times, 1);
                        var icon = marker.getIcon(),
                            element = $('#contact .map .canvas img[src="' + icon + '"]:first').parent(),
                            callee = arguments.callee;
                        if (element.length) {
                            callback && callback(element);
                        } else if (times < retry) {
                            setTimeout(function () {
                                callee.call(self, marker, callback, retry, speed, times + 1);
                            }, speed);
                        }
                    }
                },
                getAside: function () {
                    var clientWidth = $(window).width();
                    if (clientWidth >= 1920) {
                        return new google.maps.LatLng(24.1895219, 120.7581913);
                    }
                    if (clientWidth >= 1600) {
                        return new google.maps.LatLng(24.1895219, 120.7441913);
                    }
                    if (clientWidth >= 1440) {
                        return new google.maps.LatLng(24.1895219, 120.7381913);
                    }
                    if (clientWidth >= 1366) {
                        return new google.maps.LatLng(24.1895219, 120.7351913);
                    }
                    if (clientWidth >= 1280) {
                        return new google.maps.LatLng(24.1895219, 120.7321913);
                    }
                    if (clientWidth >= 1024) {
                        return new google.maps.LatLng(24.1895219, 120.7211913);
                    }
                    return new google.maps.LatLng(24.1895219, 120.7101913);
                },
                toAside: function (aside) {
                    var self = this;
                    self.map.setCenter(aside || self.getAside());
                },
                panTo: function (position, callback) {
                    var self = this, layer = self.canvas.find('> div:first > .gm-style:first > div:first > div:first'),
                        lastState = null, equalTimes = 0, checkTimes = 5, checkDelay = 20;
                    self.map.panTo(position);
                    callback && (function () {
                        var currentState = layer.attr('style');
                        if (equalTimes >= checkTimes) return callback();
                        equalTimes = (lastState === currentState) ? equalTimes + 1 : 0;
                        lastState = currentState;
                        setTimeout(arguments.callee, checkDelay);
                    }());
                },
                panToCenter: function (callback) {
                    var self = this;
                    self.panTo(self.center, callback);
                },
                clearObjects: function () {
                    var self = this;
                    self.canvas.find('.gm-style-iw').parent().hide();
                    self.canvas.children('.gm-style').slice(1).hide();
                },
                reset: function () {
                    var self = this;
                    self.clearObjects();
                    self.toAside(self.center);
                    self.map.setZoom(self.zoom);
                    self.marker.setPosition(self.place);
                    self.circle.setPosition(self.place);
                    self.fixCirclePosition();
                    self.watermarker(1);
                },
                play: function () {
                    var self = this;
                    arguments.callee.called = true;
                    if (!self.done.isCalled) {
                        return sewii.event.off(self.EVENT_API_DONE).once(self.EVENT_API_DONE, function () {
                            self.animation.play();
                        });
                    }
                    self.animation.play();
                },
                pause: function () {
                    var self = this;
                    self.route.pause();
                    self.street.pause();
                    self.animation.pause();
                    self.watermarker(0);
                },
                restore: function () {
                    var self = this;
                    self.play.called = false;
                    self.route.restore();
                    self.street.restore();
                    self.animation.restore();
                    self.reset();
                },
                route: {
                    init: function (parent) {
                        var self = this, that = parent.parent.boxes.filter('[data-id="route"]'),
                            ways = that.find('.ways'), routes = that.find('.routes'), list = routes.find('.list');
                        self.parent = parent;
                        self.that = that;
                        self.ways = ways;
                        self.routes = routes;
                        self.list = list.attr('data-clickable', 'true');
                        self.timelines = {};
                        self.resize();
                        self.scrollbar();
                        self.region();
                        self.geocode();
                        self.geolocation();
                        self.restart();
                        self.reset();
                    }, isActive: function () {
                        var self = this;
                        return self.parent.parent.isActive() && self.parent.parent.currently() === self.that.data('id');
                    }, resize: function () {
                        var self = this;
                        $(window).resized({
                            callback: function () {
                                self.scrollbarInstance && self.scrollbarInstance.resize();
                            }, delay: function () {
                                return !self.parent.parent.isActive();
                            }, now: false
                        });
                    }, scrollbar: function () {
                        var self = this;
                        $.ui.loader.x43563f9b64cce748.success(function () {
                            self.scrollbarInstance = self.list.find('.scrollbox').scrollbar({resize: false}).data('plugin_scrollbar');
                        });
                    }, region: function () {
                        var self = this;
                        $.ui.loader.x2540ecbd720b3994.success(function () {
                            $.regionalize.init({
                                cityFirstOption: {text: self.ways.find('select[name="city"] option').text()},
                                regionFirstOption: {text: self.ways.find('select[name="region"] option').text()},
                            });
                            self.ways.find('select[name="region"]').change(function () {
                                self.ways.find('input[name="address"]').focus();
                            });
                        });
                    }, geocode: function () {
                        var self = this, button = self.ways.find('.geocode'), boundClass = 'bound';
                        if (!button.hasClass(boundClass)) {
                            button.addClass(boundClass).click(function () {
                                self.parent.parent.placeholder.clear(self.ways.find('[placeholder]'));
                                var me = $(this), city = self.ways.find('select[name="city"]').val(),
                                    region = self.ways.find('select[name="region"]').val(),
                                    address = self.ways.find('input[name="address"]').val(),
                                    place = city + region + address;
                                if (!place) {
                                    self.ways.find('select[name="city"]').focus();
                                    self.parent.parent.placeholder.visible(self.ways.find('[placeholder]'));
                                    return false;
                                }
                                var geocoder = new google.maps.Geocoder();
                                me.attr('disabled', 'disabled');
                                geocoder.geocode({address: place}, function (results, status) {
                                    if (self.isActive()) {
                                        if (results && status === google.maps.GeocoderStatus.OK) {
                                            var geometry = results[0].geometry, location = geometry.location;
                                            self.start(location.lat(), location.lng());
                                            $.ui.tracker.sendEvent('Contact', 'GeoCode', place);
                                        } else self.warn('place');
                                    }
                                    me.removeAttr('disabled');
                                });
                                return false;
                            });
                        } else button.click();
                    }, geolocation: function () {
                        var self = this, button = self.ways.find('.geolocation'), boundClass = 'bound';
                        if (!button.hasClass(boundClass)) {
                            button.addClass(boundClass).click(function () {
                                var me = $(this);
                                if (!sewii.env.support.geolocation) {
                                    self.warn('support');
                                    return false;
                                }
                                me.attr('disabled', 'disabled');
                                navigator.geolocation.getCurrentPosition(function (position) {
                                    if (self.isActive()) {
                                        self.start(position.coords.latitude, position.coords.longitude);
                                        $.ui.tracker.sendEvent('Contact', 'GeoLocation', position.coords.latitude + ' / ' + position.coords.longitude);
                                    }
                                    me.removeAttr('disabled');
                                }, function (error) {
                                    if (self.isActive()) {
                                        self.warn('failed', error.message);
                                    }
                                    me.removeAttr('disabled');
                                });
                                return false;
                            });
                        } else button.click();
                    }, animateMarker: function (action) {
                        var self = this, callee = arguments.callee;
                        if (action === -1) {
                            callee.paused = true;
                            self.timelines.user && self.timelines.user.pause(0);
                        } else if (action === 0) {
                            callee.paused = true;
                            self.timelines.user && self.timelines.user.pause();
                        } else {
                            action === 1 && (callee.paused = false);
                            callee.paused || self.timelines.user && self.timelines.user.play();
                        }
                        self.parent.getMarker(self.marker, function (marker) {
                            if (marker.hasClass('animated')) return;
                            marker.addClass('animated');
                            if (self.timelines.user) {
                                self.timelines.user.pause().kill();
                                delete self.timelines.user;
                            }
                            self.timelines.user = new $.ui.animator.timeline({
                                yoyo: true,
                                repeat: -1
                            }).to({target: marker, duration: 1, params: {scale: 1.05}});
                        });
                    }, warn: function (type, title) {
                        var self = this, message = self.ways.find('.message');
                        message.stop(true).slideUp(200).filter('.' + type).attr('title', title).slideDown(600, $.ui.animator.easing('easeOutBack'));
                    }, show: function () {
                        var self = this;
                        if (self.routes.is(':visible')) {
                            self.list.children().fadeIn();
                        }
                    }, hide: function () {
                        var self = this;
                        if (self.routes.is(':visible')) {
                            self.list.children().hide();
                        } else self.clear();
                    }, start: function (lat, lng) {
                        var self = this;
                        if (!lat || !lng) return;
                        if (!self.panel) {
                            self.panel = self.list.find('.scrollable');
                            self.directionsService = new google.maps.DirectionsService();
                            self.directionsDisplay = new google.maps.DirectionsRenderer({
                                suppressMarkers: true,
                                polylineOptions: {strokeColor: '#ffa200', strokeOpacity: .8, strokeWeight: 8}
                            });
                        }
                        self.panel.empty();
                        self.directionsService.route({
                            origin: new google.maps.LatLng(lat, lng),
                            destination: self.parent.place,
                            travelMode: google.maps.TravelMode.DRIVING
                        }, function (response, status) {
                            if (!self.isActive()) return;
                            if (status === google.maps.DirectionsStatus.OK) {
                                self.directionsDisplay.setPanel(self.panel.get(0));
                                self.directionsDisplay.setMap(self.parent.map);
                                self.directionsDisplay.setDirections(response);
                                var leg = response.routes[0].legs[0];
                                if (!self.marker) {
                                    self.marker = new google.maps.Marker({
                                        map: self.parent.map,
                                        position: leg.start_location,
                                        draggable: true,
                                        title: self.parent.markers.filter('.marker.marker-user').attr('alt'),
                                        icon: self.parent.markers.filter('.marker.marker-user').attr('src')
                                    });
                                }
                                self.marker.setVisible(true);
                                self.marker.setPosition(leg.start_location);
                                self.parent.marker.setPosition(self.parent.place);
                            }
                            self.scrollbarInstance && self.scrollbarInstance.update();
                        });
                        self.ways.slideUp(300, function () {
                            self.list.children().hide();
                            self.list.find('.scrollbar').invisible();
                            self.routes.slideDown(600, $.ui.animator.easing('easeOutBack'), function () {
                                self.list.children().fadeIn(600, function () {
                                    self.scrollbarInstance && self.scrollbarInstance.update();
                                    self.list.find('.scrollbar').visible();
                                    self.panel.find('.adp-text:last').text(function (i, text) {
                                        return String(text).replace(/530/, '532');
                                    });
                                });
                                self.animateMarker(1);
                            });
                        });
                    }, restart: function (force) {
                        var self = this, button = self.routes.find('.restart'), boundClass = 'bound',
                            speed = sewii.isSet(force) ? 0 : 300;
                        if (!button.hasClass(boundClass)) {
                            button.addClass(boundClass).click(function () {
                                var me = $(this);
                                self.routes.slideUp(speed, function () {
                                    self.reset();
                                    force && self.parent.toAside(self.parent.center);
                                    force || self.parent.map.panTo(self.parent.center);
                                    self.parent.marker.setPosition(self.parent.place);
                                    self.ways.slideDown(speed, $.ui.animator.easing('easeOutBack'));
                                });
                                return false;
                            });
                        } else button.click();
                    }, reset: function () {
                        var self = this;
                        self.clear();
                        self.panel && self.panel.empty();
                        self.directionsDisplay && self.directionsDisplay.setMap(null);
                        self.marker && self.marker.setVisible(false);
                        self.animateMarker(-1);
                    }, clear: function () {
                        var self = this;
                        self.ways.find('input[name="address"]').val('');
                        self.ways.find('select[name="city"]').val('').addClass('blur').change();
                        self.ways.find('.error').hide();
                        self.parent.parent.placeholder.visible(self.ways.find('[placeholder]'));
                    }, pause: function () {
                        var self = this;
                        self.ways.stop();
                        self.routes.stop();
                        self.list.children().stop();
                        self.ways.find('.message').stop();
                        self.animateMarker(0);
                    }, restore: function () {
                        var self = this;
                        self.restart(true);
                    }
                },
                street: {
                    init: function (parent) {
                        var self = this, that = parent.parent.boxes.filter('[data-id="street"]'),
                            view = that.find('.view');
                        self.parent = parent;
                        self.that = that;
                        self.view = view.attr('data-clickable', 'true');
                        self.build();
                    }, build: function () {
                        var self = this;
                        self.pano = new google.maps.StreetViewPanorama(self.view.get(0), {
                            panControl: true,
                            zoomControl: false,
                            enableCloseButton: false,
                            addressControl: false,
                            linksControl: false
                        });
                        self.resize();
                        self.reset();
                        self.hide();
                    }, resize: function () {
                        var self = this;
                        $(window).resized({
                            callback: function () {
                                google.maps.event.trigger(self.pano, 'resize');
                            }, delay: function () {
                                return !self.parent.parent.isActive();
                            }, now: false
                        });
                    }, reset: function () {
                        var self = this, latlng = new google.maps.LatLng(24.1702696, 120.6852871);
                        self.pano.setPosition(latlng);
                        self.pano.setPov({heading: 140, pitch: 15, zoom: 1.3});
                    }, show: function () {
                        var self = this;
                        self.pano.setVisible(true);
                        self.view.find('>.gm-style').hide().fadeIn(1000);
                        $.ui.tracker.sendEvent('Contact', 'StreetView');
                    }, hide: function () {
                        var self = this;
                        self.pano.setVisible(false);
                    }, pause: function () {
                        var self = this;
                        self.view.find('>.gm-style').stop();
                    }, restore: function () {
                        var self = this;
                        self.reset();
                        self.hide();
                    },
                },
                animation: {
                    init: function (parent) {
                        var self = this;
                        self.parent = parent;
                        self.timeline = new $.ui.animator.timeline().pause();
                        self.animate();
                    }, animate: function () {
                        var self = this, callee = arguments.callee, id = self.parent.parent.getId(),
                            navigation = self.parent.parent.navigation, items = self.parent.parent.navigation.find('a'),
                            item = items.filter('[data-id="' + id + '"]'),
                            box = self.parent.parent.boxes.filter('[data-id="' + id + '"]'), token = Math.random(),
                            tweens = {};
                        items.removeClass('selected');
                        item.addClass('selected');
                        items.mouseleave();
                        callee.id = id;
                        callee.token = token;
                        self.timeline.clear();
                        (function () {
                            tweens.navigation = {
                                method: 'fromTo',
                                args: {
                                    target: navigation,
                                    duration: .8,
                                    from: {right: -navigation.width() * 1.5, display: 'block',},
                                    to: {ease: Expo.easeOut, right: 0},
                                    position: 0
                                }
                            };
                            tweens.box = {
                                method: 'fromTo',
                                args: {
                                    target: box,
                                    duration: .8,
                                    from: {
                                        visibility: 'visible',
                                        left: '-100%',
                                        top: box.data('top'),
                                        opacity: 1,
                                        scale: 1,
                                        x: 0,
                                        y: 0
                                    },
                                    to: {
                                        ease: Expo.easeOut, left: box.data('left'), onComplete: function () {
                                            switch (id) {
                                                case'street':
                                                    self.parent.street.show();
                                                    break;
                                            }
                                        }
                                    },
                                    position: .1
                                }
                            };
                            $.each(tweens, function (name, tween) {
                                self.timeline[tween.method].call(self.timeline, tween.args);
                            });
                        }());
                        self.parent.getMarker(self.parent.circle, function (circle) {
                            self.parent.getMarker(self.parent.marker, function (marker) {
                                if (callee.token !== token) return;
                                if (self.timeline.progress() > 0) return;
                                if (!self.parent.parent.isActive()) return;
                                self.timeline.clear().fromTo({
                                    target: circle,
                                    duration: 1,
                                    from: {scale: 8, opacity: 0},
                                    to: {ease: Back.easeOut, scale: 1.3, opacity: 1},
                                    position: '-=0'
                                }).to({
                                    target: circle,
                                    duration: 3,
                                    to: {ease: Elastic.easeOut, scale: 1,},
                                    position: '-=.3'
                                }).fromTo({
                                    target: marker,
                                    duration: 1.5,
                                    from: {opacity: 0, scaleY: 0, marginTop: 100},
                                    to: {ease: Expo.easeInOut, opacity: 1, scaleY: 1, marginTop: 0},
                                    position: .2
                                }).addCallback({
                                    callback: function () {
                                        self.parent.animateMarker(1);
                                    }, position: '-=2'
                                });
                                $.each(tweens, function (name, tween) {
                                    switch (name) {
                                        case'navigation':
                                            tween.args.position = '-=2.4';
                                        case'box':
                                            tween.args.position = '-=2.5';
                                        default:
                                            self.timeline[tween.method].call(self.timeline, tween.args);
                                            break;
                                    }
                                });
                            });
                        });
                    }, panIn: function (callback) {
                        var self = this;
                        if (!self.isSupportPanIn()) {
                            var notSupportCallbackDelay = 200;
                            return (self.panTimer = sewii.timer.timeout(function () {
                                callback();
                            }, notSupportCallbackDelay));
                        }
                        self.panTimer = sewii.timer.timeout(function () {
                            self.parent.panToCenter(callback);
                        }, 50);
                    }, toAside: function () {
                        var self = this;
                        self.isSupportPanIn() && self.parent.toAside();
                    }, isSupportPanIn: function () {
                        var self = this;
                        if (sewii.env.browser.msie <= 8) {
                            return false;
                        }
                        return $.ui.helper.support.hightPerformance();
                    }, toInvisibleBoxes: function () {
                        var self = this;
                        self.parent.parent.boxes.invisible();
                    }, getCurrentPlaying: function () {
                        var self = this;
                        return self.animate.id || null;
                    }, isPlaying: function () {
                        var self = this;
                        return Boolean(self.timeline && self.timeline.progress() < 1);
                    }, play: function () {
                        var self = this;
                        self.fadeshow = $.ui.animator.tweener.fromTo({
                            target: self.parent.inner,
                            duration: .5,
                            from: {alpha: 0},
                            to: {
                                alpha: 1, onUpdate: function () {
                                    if (!arguments.callee.done && this.progress() >= .5) {
                                        arguments.callee.done = true;
                                        self.panIn(function () {
                                            if (!self.parent.parent.isActive()) return;
                                            self.timeline && self.timeline.play();
                                        });
                                    }
                                }
                            }
                        });
                    }, pause: function () {
                        var self = this;
                        self.panTimer && self.panTimer.pause();
                        self.timeline && self.timeline.pause();
                        self.fadeshow && self.fadeshow.pause();
                        self.parent.inner.stop();
                        self.parent.animateMarker(0);
                    }, restore: function () {
                        var self = this;
                        self.panTimer && self.panTimer.stop();
                        self.timeline && self.timeline.pause(0);
                        self.fadeshow && self.fadeshow.pause(0);
                        self.parent.animateMarker(-1);
                        self.parent.inner.transparent();
                        self.toInvisibleBoxes();
                        self.toAside();
                        self.animate();
                    }
                }
            }
        },
    }
}).init();