import DiscussionList from 'flarum/components/DiscussionList';
import DiscussionListItem from 'flarum/components/DiscussionListItem';
import Button from 'flarum/components/Button';
import LoadingIndicator from 'flarum/components/LoadingIndicator';
import Placeholder from 'flarum/components/Placeholder';

/**
 * The `UserDiscussionList` component displays a list of discussions.
 * This extends the `DiscussionList` component. The only difference being
 * Placeholder posts are added in the place of discussions the actor
 * cannot view.
 *
 * ### Props
 *
 * - `params` A map of parameters used to construct a refined parameter object
 *   to send along in the API request to get discussion results.
 */

export default class UserDiscussionList extends DiscussionList {
   view() {
    const params = this.props.params;
    let discussions_remaining = (this.props.user.data.attributes.discussionsCount - this.discussions.length);
    let loading;

    if (this.loading) {
      loading = LoadingIndicator.component();
    } else if (this.moreResults) {
      loading = Button.component({
        children: app.translator.trans('core.forum.discussion_list.load_more_button'),
        className: 'Button',
        onclick: this.loadMore.bind(this)
      });
    }

    if (this.discussions.length === 0 && !this.loading && discussions_remaining == 0) {
      const text = app.translator.trans('core.forum.discussion_list.empty_text');
      return (
        <div className="DiscussionList">
          {Placeholder.component({text})}
        </div>
      );
    }

    return (
      <div className="DiscussionList">
        <ul className="DiscussionList-discussions">
          {this.discussions.map(discussion => {
            return (
              <li key={discussion.id()} data-id={discussion.id()}>
                {DiscussionListItem.component({discussion, params})}
              </li>
            );
          })}
          {(discussions_remaining > 0 && ! this.moreResults && !this.loading) ? (
          <li>
            <div class="DiscussionListItem">
              <div class="DiscussionListItem-content">
                <span class="DiscussionListItem-author" title="" data-original-title="{app.translator.transChoice('core.forum.user.discussions_missing_head', discussions_remaining, {count: discussions_remaining})}!">
                  <span class="Avatar " style="background: rgb(255, 214, 108);">?</span>
                </span>
                <ul class="DiscussionListItem-badges badges"></ul>
                <span class="DiscussionListItem-main">
                  <h3 class="DiscussionListItem-title">{app.translator.transChoice('core.forum.user.discussions_missing_head', discussions_remaining, {count: discussions_remaining})}!</h3>
                  <ul class="DiscussionListItem-info">
                    <li class="item-terminalPost">
                      <span>{app.translator.trans('core.forum.user.content_missing_text')}</span>
                    </li>
                  </ul>
                </span>
              </div>
            </div>
          </li>
          ) : ''}
        </ul>
        <div className="DiscussionList-loadMore">
          {loading}
        </div>
      </div>
    );
  }
}
