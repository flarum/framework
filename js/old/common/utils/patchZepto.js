import jump from 'jump.js';
import Tooltip from 'tooltip.js';

// add $.fn.tooltip
$.fn.tooltip = function (option) {
  return this.each(function () {
    const $this = $(this);
    let data = $this.data('bs.tooltip');
    const options = typeof option === 'object' && option || {};

    if ($this.attr('title')) {
      options.title = $this.attr('title');
      $this.removeAttr('title');
      $this.attr('data-original-title', options.title);
    }

    if (option === 'destroy') option = 'dispose';

    if (!data && ['dispose', 'hide'].includes(option)) return;

    if (!data) $this.data('bs.tooltip', (data = new Tooltip(this, options)));
    if (typeof option === 'string' && data[option]) data[option]();
  });
};

// add $.fn.outerWidth and $.fn.outerHeight
['width', 'height'].forEach(function(dimension) {
  const Dimension = dimension.replace(/./, function (m) {
    return m[0].toUpperCase()
  });

  $.fn[`outer${Dimension}`] = function(margin) {
    const elem = this;

    if (elem) {
      const sides = {'width': ['left', 'right'], 'height': ['top', 'bottom']};
      let size = elem[dimension]();

      sides[dimension].forEach(function(side) {
        if (margin) size += parseInt(elem.css('margin-' + side), 10);
      });

      return size;
    } else {
      return null;
    }
  };
});

// allow use of $(':input')
$.expr[':']['input'] = function() {
  if (('disabled' in this) || ['INPUT', 'SELECT', 'TEXTAREA', 'BUTTON'].includes(this.tagName)) return this;
};

// add $().hover() method
$.fn.hover = function(hover, leave) {
  return this
    .on('mouseenter', hover)
    .on('mouseleave', leave || hover);
};

// add animated scroll
$.fn.animatedScrollTop = function (to, duration = $.fx.speeds._default, callback) {
  if (typeof to === 'number') to -= (window.scrollY || window.pageYOffset);

  jump(to, {
    duration: $.fx.speeds[duration] || duration,
    callback
  });

  return this;
};

// required for compatibility with jquery plugins
// ex: bootstrap plugins
$.fn.extend = $.extend.bind($);

/**
 * Enable special events on Zepto
 * @license Original Copyright 2013 Enideo. Released under dual MIT and GPL licenses.
 */
$.event.special = $.event.special || {};

const bindBeforeSpecialEvents = $.fn.bind;

$.fn.bind = function(eventName, data, callback) {
  const el = this;

  if (!callback){
    callback = data;
    data = null;
  }

  $.each(eventName.split(/\s/), function(i, eventName) {
    eventName = eventName.split(/\./)[0];

    if(eventName in $.event.special){
      let specialEvent = $.event.special[eventName];

      /// init enable special events on Zepto
      if(!specialEvent._init) {
        specialEvent._init = true;

        /// intercept and replace the special event handler to add functionality
        specialEvent.originalHandler = specialEvent.handler;
        specialEvent.handler = function(){

          /// make event argument writable, like on jQuery
          const args = Array.prototype.slice.call(arguments);

          args[0] = $.extend({},args[0]);

          /// define the event handle, $.event.dispatch is only for newer versions of jQuery
          $.event.handle = function(){

            /// make context of trigger the event element
            const args = Array.prototype.slice.call(arguments);
            const event = args[0];
            const $target = $(event.target);

            $target.trigger.apply( $target, arguments );
          };

          specialEvent.originalHandler.apply(this,args);
        }
      }

      /// setup special events on Zepto
      specialEvent.setup.apply(el, [data]);
    }
  });

  return bindBeforeSpecialEvents.apply(this, [eventName, callback]);
};
