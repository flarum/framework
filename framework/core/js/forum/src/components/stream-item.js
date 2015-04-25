import Component from 'flarum/component';
import classList from 'flarum/utils/class-list';
import LoadingIndicator from 'flarum/components/loading-indicator';

export default class StreamItem extends Component {
  /**

   */
  constructor(props) {
    super(props);

    this.element = m.prop();
  }

  /**

   */
  view() {
    var component = this;
    var item = this.props.item;

    var gap = !item.post;
    var direction = item.direction;
    var loading = item.loading;
    var count = item.end - item.start + 1;
    var classes = { item: true, gap, loading, direction };

    var attributes = {
      className: classList(classes),
      config: this.element,
      'data-start': item.start,
      'data-end': item.end
    };
    if (!gap) {
      attributes['data-time'] = item.post.time();
      attributes['data-number'] = item.post.number();
    } else {
      attributes['config'] = (element) => {
        this.element(element);
        element.instance = this;
      };
      attributes['onclick'] = this.load.bind(this);
      attributes['onmouseenter'] = function(e) {
        if (!item.loading) {
          var $this = $(this);
          var up = e.clientY > $this.offset().top - $(document).scrollTop() + $this.outerHeight(true) / 2;
          $this.removeClass('up down').addClass(item.direction = up ? 'up' : 'down');
        }
        m.redraw.strategy('none');
      };
    }

    var content;
    if (gap) {
      content = m('span', loading ? LoadingIndicator.component() : count+' more post'+(count !== 1 ? 's' : ''));
    } else {
      var PostComponent = app.postComponentRegistry[item.post.contentType()];
      if (PostComponent) {
        content = PostComponent.component({post: item.post, ondelete: this.props.ondelete});
      }
    }

    return m('div', attributes, content);
  }

  /**

   */
  load() {
    var item = this.props.item;

    // If this item is not a gap, or if we're already loading its posts,
    // then we don't need to do anything.
    if (item.post || item.loading) {
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
    var siblingFunc = item.direction === 'up' ? 'nextAll' : 'prevAll';
    var anchor = this.$()[siblingFunc]('.item:first');

    // Tell the controller that we want to load the range of posts that this
    // gap represents. We also specify which direction we want to load the
    // posts from.
    this.props.loadRange(item.start, item.end, item.direction === 'up').then(function() {
      // Immediately after the posts have been loaded (but before they
      // have been rendered,) we want to grab the distance from the top of
      // the viewport to the top of the anchor element.
      if (anchor.length) {
        var scrollOffset = anchor.offset().top - $(document).scrollTop();
      }

      m.redraw(true);

      // After they have been rendered, we scroll back to a position
      // so that the distance from the top of the viewport to the top
      // of the anchor element is the same as before. If there is no
      // anchor (i.e. this gap is terminal,) then we'll scroll to the
      // bottom of the document.
      $('body').scrollTop(anchor.length ? anchor.offset().top - scrollOffset : $('body').height());
    });

    m.redraw();
  }
}
