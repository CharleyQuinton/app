(function(window, $) {

$.getSassURL = function(rootURL, scssFilePath, params) {
	return rootURL + wgAssetsManagerQuery.
		replace('%1$s', 'sass').
		replace('%2$s', scssFilePath).
		replace('%3$s', encodeURIComponent($.param(params || window.sassParams))).
		replace('%4$d', wgStyleVersion);
};

$.getSassCommonURL = function(scssFilePath, params) {
	return $.getSassURL(wgCdnRootUrl, scssFilePath, params);
};

$.getSassLocalURL = function(scssFilePath, params) {
	return $.getSassURL(wgServer, scssFilePath, params);
};

$.getAssetManagerGroupUrl = function( groups, params ) {
	if ( typeof groups == 'string' ) {
		groups = [ groups ];
	}

	return wgAssetsManagerQuery .
		replace( '%1$s', 'groups' ) .
		replace( '%2$s', groups.join( ',' ) ) .
		replace( '%3$s', params ? encodeURIComponent( $.param( params ) ) : '-' ) .
		replace( '%4$d', wgStyleVersion );
};

//see http://jamazon.co.uk/web/2008/07/21/jquerygetscript-does-not-cache
$.ajaxSetup({cache: true});

// replace stock function for getting rid of response-speed related issues in Firefox
// @see http://stackoverflow.com/questions/1130921/is-the-callback-on-jquerys-getscript-unreliable-or-am-i-doing-something-wrong
$.getScript = function(url, callback, failureFn) {
	return $.ajax({
		type: "GET",
		url: url,
		success: function(xhr) {
			if (typeof callback == 'function') {
				try {
					callback();
				}
				catch(e) {
					// TODO: is this fallback still needed? consider using promise pattern
					eval(xhr);
					callback();
					$().log('eval() fallback applied for ' + url, 'getScript');
				}
			}
		},
		error: (typeof failureFn == 'function' ? failureFn : $.noop),
		dataType: 'script'
	});
};

$.fn.log = function (msg, group) {
	Wikia.log(msg, Wikia.log.levels.info, group);
	return this;
};

$.fn.exists = function() {
	return this.length > 0;
};

// show modal dialog with content fetched via AJAX request
$.fn.getModal = function(url, id, options) {
	// get modal content via AJAX
	$.get(url, function(html) {
		$("body").append(html);

		// fire callbackBefore if provided
		if (typeof options == 'object' && typeof options.callbackBefore == 'function') {
			options.callbackBefore();
		}

		// makeModal() if requested
		if (typeof id == 'string') {
			$(id).makeModal(options);
			$().log('getModal: ' + id + ' modal made');
		}

		// fire callback if provided
		if (typeof options == 'object' && typeof options.callback == 'function') {
			options.callback();
		}
	});
};

// show modal popup with static title and content provided
$.showModal = function(title, content, options) {
	options = (typeof options != 'object') ? {} : options;

	var header = $('<h1>').text(title),
		dialog = $('<div>').html(content).prepend(header).appendTo('body'),
		wrapper;

	// fire callbackBefore if provided
	if (typeof options.callbackBefore == 'function') {
		options.callbackBefore();
	}

    wrapper = dialog.makeModal(options);

	// fire callback if provided
	if (typeof options.callback == 'function') {
		// pass modal wrapper so it can be used in callback
		options.callback(wrapper);
	}

    return wrapper;
};

// show modal version of confirm()
$.confirm = function(options) {
	// init options
	options = (typeof options != 'object') ? {} : options;
	options.id = 'WikiaConfirm';

	var html = '';

	if(options.title) {
		html += '<h1>' + options.title + '</h1>';
	}

	html += '<p>' + (options.content || '') + '</p>' +
		'<div class="neutral modalToolbar">' +
		'<button id="WikiaConfirmCancel" class="wikia-button secondary">' + (options.cancelMsg || 'Cancel') + '</button>' +
		'<button id="WikiaConfirmOk" class="wikia-button">' + (options.okMsg || 'Ok') + '</button>' +
		'</div>';

	var dialog = $('<div>').
		appendTo("body").
		html(html).
		attr('title', options.title || '');

	// fire callbackBefore if provided
	if (typeof options.callbackBefore == 'function') {
		options.callbackBefore();
	}

	// handle clicks on Ok
	$('#WikiaConfirmOk').click(function() {
		 $('#WikiaConfirm').closeModal();

		 // try to call callback when Ok is pressed
		 if (typeof options.onOk == 'function') {
			 options.onOk();
		 }
	});

	// handle clicks on Cancel
	$('#WikiaConfirmCancel').click(function() {
		$('#WikiaConfirm').closeModal();
	});

	dialog.makeModal(options);

	// fire callback if provided
	if (typeof options.callback == 'function') {
		options.callback();
	}
};

/* example of usage
$.showCustomModal('title', '<b>content</b>',
	{buttons: [
		{id:'ok', defaultButton:true, message:'OK', handler:function(){alert('ok');}},
		{id:'cancel', message:'Cancel', handler:function(){alert('cancel');}}
	]}
);
*/
// show modal popup with title, content and set ot buttons
$.showCustomModal = function(title, content, options) {
	options = (typeof options != 'object') ? {} : options;

	var buttons = '';
	if (options.buttons) {
		buttons = $('<div class="neutral modalToolbar"></div>');
		for (var buttonNo = 0; buttonNo < options.buttons.length; buttonNo++) {
			var button = '<a id="' + options.buttons[buttonNo].id + '" class="wikia-button' + (options.buttons[buttonNo].defaultButton ? '' : ' secondary') + '">' + options.buttons[buttonNo].message + '</a>';
			$(button).bind('click', options.buttons[buttonNo].handler).appendTo(buttons);
		}
	}

	var dialog = $('<div>').html(content).attr('title', title).append(buttons);

	$("body").append(dialog);

	// fire callbackBefore if provided
	if (typeof options.callbackBefore == 'function') {
		options.callbackBefore();
	}

	var modal = dialog.makeModal(options);

	// fire callback if provided
	if (typeof options.callback == 'function') {
		options.callback(modal);
	}

	return modal;
};

// send POST request and parse returned JSON
$.postJSON = function(u, d, callback) {
	return $.post(u, d, callback, "json");
};

//see http://jquery-howto.blogspot.com/2009/09/get-url-parameters-values-with-jquery.html
// deprecated - use querystring AMD module
// @see /resources/wikia/modules/querystring.js
$.extend({
	_urlVars: null,
	getUrlVars: function() {
		if($._urlVars === null){
			var hash,
			hashes = window.location.search.slice(window.location.search.indexOf('?') + 1).split('&');
			$._urlVars = {};
			for (var i = 0, j = hashes.length; i < j; i++) {
				hash = hashes[i].split('=');
				$._urlVars[hash[0]] = hash[1];
			}
		}
		return $._urlVars;
	},
	getUrlVar: function(name) {
		return $.getUrlVars()[name];
	}
});

// see http://www.texotela.co.uk/code/jquery/reverse/
$.fn.reverse = function() {
	return this.pushStack(this.get().reverse(), arguments);
};

$.fn.isChrome = function() {
	if ( $.browser.webkit && !$.browser.opera && !$.browser.msie && !$.browser.mozilla ) {
		var userAgent = navigator.userAgent.toLowerCase();
		if ( userAgent.indexOf("chrome") >  -1 ) {
			return true;
		}
	}
	return false;
};

// https://github.com/Modernizr/Modernizr/issues/84
$.fn.isTouchscreen = function() {
	return ('ontouchstart' in window);
}

/**
 * Tests whether first element in current collection is a child of node matching selector provided
 *
 * @return boolean
 * @param string a $ selector
 *
 * @author Macbre
 */
$.fn.hasParent = function(selector) {
	// use just the first element from current collection
	return this.first().parent().closest(selector).exists();
}

/**
 * @author Marcin Maciejewski <marcin@wikia-inc.com>
 *
 * Plugin for easy creating Ajax Loading visualization.
 * after using it selected elements content will apply proper css class
 * and in the middle of it throbber will be displayed.
 *
 * TODO: convert to AMD module (see mobile's "throbber" module)
 */
$.fn.startThrobbing = function() {
	return this.append('<div class="wikiaThrobber"></div>');
};

$.fn.stopThrobbing = function() {
	return this.find('.wikiaThrobber').remove();
};
$.stopThrobbing = function() {
	$('.wikiaThrobber').remove();
}
$.preloadThrobber = function() {
	var img = new Image();
	img.src = stylepath + '/common/images/ajax.gif';
};

/*
	Generate URL to thumbnail from different URL to thumbnail :)
	New URL has different parameters (fixed width and height)

	TODO: Remove it, no code uses this function
 */
$.thumbUrl2ThumbUrl = function( url, type, width, height ) {
	return Wikia.Thumbnailer.getThumbURL(url, type, width, height);
}

$.htmlentities = function ( s ) {
	return String(s).replace(/\&/g,'&'+'amp;').replace(/</g,'&'+'lt;')
    	.replace(/>/g,'&'+'gt;').replace(/\'/g,'&'+'apos;').replace(/\"/g,'&'+'quot;');
};

$.createClass = function (sc,o) {
	var constructor = o.constructor;
	if (typeof constructor != 'function' || constructor == Object.prototype.constructor) {
		constructor = function(){sc.apply(this,arguments);};
	}
	var bc = constructor;
	var f = function() {};
	f.prototype = sc.prototype || {};
	bc.prototype = new f();

	// macbre: support static members
	if (typeof o.statics == 'object') {
		bc = $.extend(bc, o.statics);
		delete o.statics;
	}

	for (var m in o) {
		bc.prototype[m] = o[m];
	}

	bc.prototype.constructor = bc;
	bc.superclass = sc.prototype;

	return bc;
};

$.proxyBind = function (fn,thisObject,baseArgs) {
	return function() {
		var args = baseArgs.slice(0).concat(Array.prototype.call(arguments,0));
		return fn.apply(thisObject,args);
	}
};

var Observable = $.createClass(Object, {
	constructor: function() {
		Observable.superclass.constructor.apply(this,arguments);
		this.events = {};
	},

	bind: function(e,cb,scope) {
		if (typeof e == 'object') {
			scope = cb;
			for (var i in e) {
				if (i !== 'scope') {
					this.bind(i,e[i],e.scope||scope);
				}
			}
		} else if ($.isArray(cb)) {
			for (var i=0;i<cb.length;i++) {
				this.bind(e,cb[i],scope);
			}
		} else {
			scope = scope || this;
			this.events[e] = this.events[e] || [];
			this.events[e].push({
				fn: cb,
				scope: scope
			});
		}
		return true;
	},

	unbind: function(e,cb,scope) {
		if (typeof e == 'object') {
			scope = cb;
			var ret = false;
			for (var i in e) {
				if (i !== 'scope') {
					ret = this.unbind(i,e[i],e.scope||scope) || ret;
				}
			}
			return ret;
		} else if ($.isArray(cb)) {
			var ret = false;
			for (var i=0;i<cb.length;i++) {
				ret = this.unbind(e,cb[i],scope) || ret;
			}
			return ret;
		} else {
			if (!this.events[e]) {
				return false;
			}
			scope = scope || this;
			for (var i in this.events[e]) {
				if (this.events[e][i].fn == cb && this.events[e][i].scope == scope) {
					delete this.events[e][i];
					return true;
				}
			}
			return false;
		}
	},

	on: function(e,cb) {
		this.bind.apply(this,arguments);
	},

	un: function(e,cb) {
		this.unbind.apply(this,arguments);
	},

	relayEvents: function(o,e,te) {
		te = te || e;
		o.bind(e,function() {
			var a = [te].concat(arguments);
			this.fire.apply(this,a);
		},this);
	},

	fire: function(e) {
		var a = Array.prototype.slice.call(arguments,1);
		if (!this.events[e])
			return true;
		var ee = this.events[e];
		for (var i=0;i<ee.length;i++) {
			if (typeof ee[i].fn == 'function') {
				var scope = ee[i].scope || this;
				if (ee[i].fn.apply(scope,a) === false) {
					return false;
				}
			}
		}
		return true;
	},

	proxy: function(func) {
		return $.proxy(func, this);
	},

	debugEvents: function( list ) {
		var fns = list ? list : ['bind','unbind','fire','relayEvents'];
		for (var i=0;i<fns.length;i++) {
			(function(fn){
				if (typeof this['nodebug-'+fn] == 'undefined') {
					var f = this['nodebug-'+fn] = this[fn];
					this[fn] = function() {
						window.console && console.log && console.log(this,fn,arguments);
						return f.apply(this,arguments);
					}
				}
			}).call(this,fns[i]);
		}
	}
});

var GlobalTriggers = (function(){
	var GlobalTriggersClass = $.createClass(Observable,{

		fired: null,

		constructor: function() {
			GlobalTriggersClass.superclass.constructor.apply(this);
			this.fired = {};
		},

		bind: function(e,cb,scope) {
			GlobalTriggersClass.superclass.bind.apply(this,arguments);
			if (typeof e == 'object' || $.isArray(cb)) {
				return;
			}

			if (typeof this.fired[e] != 'undefined') {
				var a = this.fired[e].slice(0);
				setTimeout(function(){
					for (i=0;i<a.length;i++) {
						cb.apply(scope||window,a[i]);
					}
				},10);
			}
		},

		fire: function(e) {
			var a = Array.prototype.slice.call(arguments,1);
			this.fired[e] = this.fired[e] || [];
			this.fired[e].push(a);
			GlobalTriggersClass.superclass.fire.apply(this,arguments);
		}

	});
	return new GlobalTriggersClass();
})();

var Timer = $.createClass(Object,{
	callback: null,
	timeout: 1000,
	timer: null,

	constructor: function ( callback, timeout ) {
		this.callback = callback;
		this.timeout = (typeof timeout == 'number') ? timeout : this.timeout;
	},

	run: function () {
		this.callback.apply(window);
	},

	start: function ( timeout ) {
		this.stop();
		timeout = (typeof timeout == 'number') ? timeout : this.timeout;
		this.timer = setTimeout(this.callback,timeout);
	},

	stop: function () {
		if (this.timer != null) {
			clearTimeout(this.timer);
			this.timer = null;
		}
	}
});

$.extend(Timer, {
	create: function( callback, timeout ) {
		var timer = new Timer(callback,timeout);
		return timer;
	},

	once: function ( callback, timeout ) {
		var timer = Timer.create(callback,timeout);
		timer.start();
		return timer;
	}
});

$.openPopup = function(url, name, moduleName, width, height) {
	if (wgUserName) {
		window.open(
			url,
			name,
			'width='+width+',height='+height+',menubar=no,status=no,location=no,toolbar=no,scrollbars=no,resizable=yes'
		);
	}
	else {
		showComboAjaxForPlaceHolder(false, "", function() {
			AjaxLogin.doSuccess = function() {
				$('.modalWrapper').children().not('.close').not('.modalContent').not('h1').remove();

				$.nirvana.sendRequest({
					controller: moduleName,
					method: 'AnonLoginSuccess',
					format: 'html',
					type: 'get',
					callback: function(html) {
						$('.modalContent').html(html);
					}
				});
			}
		}, false);
	}
}

// Anything that needs DOM ready should go in here
$(function() {
	// page loading time: onDomReady
	if (typeof wgNow != 'undefined') {
		var loadTime = (new Date()).getTime() - wgNow.getTime();
		$().log('DOM ready after ' + loadTime + ' ms', window.skin);
	}

	//beacon_id cookie
	if ( window.beacon_id ) {
		$.cookies.set( 'wikia_beacon_id', window.beacon_id, { path: wgCookiePath, domain: wgCookieDomain });
	}

	// For selenium tests
	window.wgWikiaDOMReady = true;

	// Dynamic timestamps
	if ( typeof $.fn.timeago != 'undefined' ) {
		$( '.timeago' ).timeago();
	}
});

var $window = $(window);

// page loading time: onLoad
$window.bind('load', function() {
	if (typeof wgNow != 'undefined') {
		var loadTime = (new Date()).getTime() - wgNow.getTime();
		$().log('window onload after ' + loadTime + ' ms', window.skin);
	}
});

// The 'scrollstop' event will fire 100ms after a user stops scrolling.
// This event should be used in place of scroll whenever possible.
// See: http://ejohn.org/blog/learning-from-twitter/
$window.on( 'scroll', $.debounce( 100, function( event ) {
	$window.trigger( 'scrollstop', [ event ] );
}));

// These functions are deprecated, but we will keep aliases around for old code and user scripts
$.toJSON = JSON.stringify; /* JSlint ignore */
$.evalJSON = $.secureEvalJSON = JSON.parse; /* JSlint ignore */

// Exports
window.GlobalTriggers = GlobalTriggers;
window.Observable = Observable;
window.Timer = Timer;

})(window, jQuery);
