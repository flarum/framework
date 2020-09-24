import DiscussionList from './DiscussionList';
import Component from '../../common/Component';

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
    if (!this.attrs.state.hasDiscussions()) {
      return;
    }

    return <div className="DiscussionPage-list">{this.enoughSpace() && <DiscussionList state={this.attrs.state} />}</div>;
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    const $list = $(vnode.dom);

    // When the mouse enters and leaves the discussions pane, we want to show
    // and hide the pane respectively. We also create a 10px 'hot edge' on the
    // left of the screen to activate the pane.
    const pane = app.pane;
    $list.hover(pane.show.bind(pane), pane.onmouseleave.bind(pane));

    $(document).on('mousemove', hotEdge);

    // If the discussion we are viewing is listed in the discussion list, then
    // we will make sure it is visible in the viewport – if it is not we will
    // scroll the list down to it.
    const $discussion = $list.find('.DiscussionListItem.active');
    if ($discussion.length) {
      const listTop = $list.offset().top;
      const listBottom = listTop + $list.outerHeight();
      const discussionTop = $discussion.offset().top;
      const discussionBottom = discussionTop + $discussion.outerHeight();

      if (discussionTop < listTop || discussionBottom > listBottom) {
        $list.scrollTop($list.scrollTop() - listTop + discussionTop);
      }
    }
  }

  onremove() {
    $(document).off('mousemove', hotEdge);
  }

  /**
   * Are we on a device that's larger than we consider "mobile"?
   *
   * @returns {boolean}
   */
  enoughSpace() {
    return !$('.App-navigation').is(':visible');
  }
}
