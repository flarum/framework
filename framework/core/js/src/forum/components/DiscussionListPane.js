import app from '../../forum/app';
import DiscussionList from './DiscussionList';
import Component from '../../common/Component';
import DiscussionPage from './DiscussionPage';

const hotEdge = (e) => {
  if (e.pageX < 10) app.pane.show();
};

/**
 * The `DiscussionListPane` component displays the list of previously viewed
 * discussions in a panel that can be displayed by moving the mouse to the left
 * edge of the screen, where it can also be pinned in place.
 *
 * ### Attrs
 *
 * - `state` A DiscussionListState object that represents the discussion lists's state.
 */
export default class DiscussionListPane extends Component {
  view() {
    if (!this.attrs.state.hasItems()) {
      return;
    }

    return <aside className="DiscussionListPane">{this.enoughSpace() && <DiscussionList state={this.attrs.state} />}</aside>;
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    document.addEventListener('mousemove', hotEdge);

    const list = vnode.dom;
    if (!list) return;

    // When the mouse enters and leaves the discussions pane, we want to show
    // and hide the pane respectively. We also create a 10px 'hot edge' on the
    // left of the screen to activate the pane.
    const pane = app.pane;
    list.addEventListener('mouseenter', pane.show.bind(pane));
    list.addEventListener('mouseleave', pane.onmouseleave.bind(pane));

    // When coming from another discussion, scroll to the previous position
    // to prevent the discussion list jumping around.
    if (app.previous.matches(DiscussionPage)) {
      const top = app.cache.discussionListPaneScrollTop || 0;
      list.scrollTo({ top });
    } else {
      // If the discussion we are viewing is listed in the discussion list, then
      // we will make sure it is visible in the viewport – if it is not we will
      // scroll the list down to it.
      const discussion = list.querySelector('.DiscussionListItem.active');
      if (discussion) {
        const scrollTop = document.documentElement.scrollTop;
        const listRect = list.getBoundingClientRect();
        const listTop = listRect.top + scrollTop;
        const listBottom = listTop + listRect.height;
        const discussionRect = discussion.getBoundingClientRect();
        const discussionTop = discussionRect.top + scrollTop;
        const discussionBottom = discussionTop + discussionRect.height;

        if (discussionTop < listTop || discussionBottom > listBottom) {
          list.scrollTo({ top: list.scrollTop - listTop + discussionTop });
        }
      }
    }
  }

  onremove(vnode) {
    if (vnode.dom) app.cache.discussionListPaneScrollTop = vnode.dom.scrollTop;
    document.removeEventListener('mousemove', hotEdge);
  }

  /**
   * Are we on a device that's larger than we consider "mobile"?
   *
   * @returns {boolean}
   */
  enoughSpace() {
    const nav = document.querySelector('.App-navigation');
    return nav.offsetWidth == 0 && nav.offsetHeight == 0;
  }
}
