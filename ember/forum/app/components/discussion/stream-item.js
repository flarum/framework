import Ember from 'ember';

var $ = Ember.$;

/**
  A stream 'item' represents one item in the post stream - this may be a
  single post, or it may represent a gap of many posts which have not been
  loaded.
 */
export default Ember.Component.extend({
  classNames: ['item'],
  classNameBindings: ['gap', 'loading', 'direction'],
  attributeBindings: [
    'start:data-start',
    'end:data-end',
    'time:data-time',
    'number:data-number'
  ],

  start: Ember.computed.alias('item.indexStart'),
  end: Ember.computed.alias('item.indexEnd'),
  number: Ember.computed.alias('item.content.number'),
  loading: Ember.computed.alias('item.loading'),
  direction: Ember.computed.alias('item.direction'),
  gap: Ember.computed.not('item.content'),

  time: Ember.computed('item.content.time', function() {
    var time = this.get('item.content.time');
    return time ? time.toString() : null;
  }),

  count: Ember.computed('start', 'end', function() {
    return this.get('end') - this.get('start') + 1;
  }),

  loadingChanged: Ember.observer('loading', function() {
    this.rerender();
  }),

  render: function(buffer) {
    if (this.get('item.content')) {
      return this._super(buffer);
    }

    buffer.push('<span>');
    if (this.get('loading')) {
      buffer.push('&nbsp;');
    } else {
      buffer.push(this.get('count')+' more post'+(this.get('count') !== 1 ? 's' : ''));
    }
    buffer.push('</span>');
  },

  didInsertElement: function() {
    if (!this.get('gap')) {
      return;
    }

    if (this.get('loading')) {
      var view = this;
      Ember.run.scheduleOnce('afterRender', function() {
        view.$().spin('small');
      });
    } else {
      var self = this;
      this.$().hover(function(e) {
        if (! self.get('loading')) {
          var up = e.clientY > $(this).offset().top - $(document).scrollTop() + $(this).outerHeight(true) / 2;
          self.set('direction', up ? 'up' : 'down');
        }
      });
    }
  },

  load: function(relativeIndex) {
    // If this item is not a gap, or if we're already loading its posts,
    // then we don't need to do anything.
    if (! this.get('gap') || this.get('loading')) {
      return false;
    }

    // If new posts are being loaded in an upwards direction, then when
    // they are rendered, the rest of the posts will be pushed down the
    // page. If loaded in a downwards direction from the end of a
    // discussion, the terminal gap will disappear and the page will
    // scroll up a bit before the new posts are rendered. In order to
    // maintain the current scroll position relative to the content
    // before/after the gap, we need to find item directly after the gap
    // and use it as an anchor.
    var siblingFunc = this.get('direction') === 'up' ? 'nextAll' : 'prevAll';
    var anchor = this.$()[siblingFunc]('.item:first');

    // Immediately after the posts have been loaded (but before they
    // have been rendered,) we want to grab the distance from the top of
    // the viewport to the top of the anchor element.
    this.get('stream').one('postsLoaded', function() {
      if (anchor.length) {
        var scrollOffset = anchor.offset().top - $(document).scrollTop();
      }

      // After they have been rendered, we scroll back to a position
      // so that the distance from the top of the viewport to the top
      // of the anchor element is the same as before. If there is no
      // anchor (i.e. this gap is terminal,) then we'll scroll to the
      // bottom of the document.
      Ember.run.scheduleOnce('afterRender', function() {
        $('body').scrollTop(anchor.length ? anchor.offset().top - scrollOffset : $('body').height());
      });
    });

    // Tell the controller that we want to load the range of posts that this
    // gap represents. We also specify which direction we want to load the
    // posts from.
    this.sendAction(
      'loadRange',
      this.get('start') + (relativeIndex || 0),
      this.get('end'),
      this.get('direction') === 'up'
    );
  },

  click: function() {
    this.load();
  }
});
