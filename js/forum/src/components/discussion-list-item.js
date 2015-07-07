import Component from 'flarum/component';
import avatar from 'flarum/helpers/avatar';
import listItems from 'flarum/helpers/list-items';
import highlight from 'flarum/helpers/highlight';
import icon from 'flarum/helpers/icon';
import humanTime from 'flarum/utils/human-time';
import ItemList from 'flarum/utils/item-list';
import abbreviateNumber from 'flarum/utils/abbreviate-number';
import DropdownButton from 'flarum/components/dropdown-button';
import TerminalPost from 'flarum/components/terminal-post';
import PostPreview from 'flarum/components/post-preview';
import SubtreeRetainer from 'flarum/utils/subtree-retainer';
import slidable from 'flarum/utils/slidable';

export default class DiscussionListItem extends Component {
  constructor(props) {
    super(props);

    this.subtree = new SubtreeRetainer(
      () => this.props.discussion.freshness,
      () => app.session.user() && app.session.user().readTime(),
      () => this.active()
    );
  }

  active() {
    return m.route.param('id') === this.props.discussion.id();
  }

  view() {
    var discussion = this.props.discussion;

    var startUser = discussion.startUser();
    var isUnread = discussion.isUnread();
    var displayUnread = this.props.countType !== 'replies' && isUnread;
    var jumpTo = Math.min(discussion.lastPostNumber(), (discussion.readNumber() || 0) + 1);
    var relevantPosts = this.props.q ? discussion.relevantPosts() : '';
    var controls = discussion.controls(this).toArray();

    return this.subtree.retain() || m('div.discussion-list-item', {className: this.active() ? 'active' : ''}, [
      controls.length ? DropdownButton.component({
        icon: 'ellipsis-v',
        items: controls,
        className: 'contextual-controls',
        buttonClass: 'btn btn-default btn-naked btn-icon btn-sm slidable-underneath slidable-underneath-right',
        menuClass: 'pull-right'
      }) : '',

      m('a.slidable-underneath.slidable-underneath-left.elastic', {
        className: discussion.isUnread() ? '' : 'disabled',
        onclick: this.markAsRead.bind(this)
      }, icon('check icon')),

      m('div.slidable-slider.discussion-summary', {className: isUnread ? 'unread' : ''}, [

        m((startUser ? 'a' : 'span')+'.author', {
          href: startUser ? app.route.user(startUser) : undefined,
          config: function(element, isInitialized, context) {
            $(element).tooltip({ placement: 'right' });
            m.route.apply(this, arguments);
          },
          title: 'Started by '+(startUser ? startUser.username() : '[deleted]')+' '+humanTime(discussion.startTime())
        }, [
          avatar(startUser, {title: ''})
        ]),

        m('ul.badges', listItems(discussion.badges().toArray())),

        m('a.main', {href: app.route.discussion(discussion, jumpTo), config: m.route}, [
          m('h3.title', highlight(discussion.title(), this.props.q)),
          m('ul.info', listItems(this.infoItems().toArray()))
        ]),

        m('span.count', {onclick: this.markAsRead.bind(this), title: displayUnread ? 'Mark as Read' : ''}, [
          abbreviateNumber(discussion[displayUnread ? 'unreadCount' : 'repliesCount']())
        ]),

        (relevantPosts && relevantPosts.length)
          ? m('div.relevant-posts', relevantPosts.map(post => PostPreview.component({post, highlight: this.props.q})))
          : ''
      ])
    ]);
  }

  markAsRead() {
    var discussion = this.props.discussion;

    if (discussion.isUnread()) {
      discussion.save({ readNumber: discussion.lastPostNumber() });
      m.redraw();
    }
  }

  /**
    Build an item list of info for a discussion listing. By default this is
    just the first/last post indicator.

    @return {ItemList}
   */
  infoItems() {
    var items = new ItemList();

    items.add('terminalPost',
      TerminalPost.component({
        discussion: this.props.discussion,
        lastPost: this.props.terminalPostType !== 'start'
      })
    );

    return items;
  }

  config(element, isInitialized, context) {
    if (isInitialized) return;

    if ('ontouchstart' in window) {
      this.$().addClass('slidable');

      var slidableInstance = slidable(element);

      this.$('.contextual-controls').on('hidden.bs.dropdown', function() {
        slidableInstance.reset();
      });
    }
  }
};
