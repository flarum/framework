import Component, { type ComponentAttrs } from '../../common/Component';
import Avatar from '../../common/components/Avatar';
import classList from '../../common/utils/classList';

export interface ILoadingPostAttrs extends ComponentAttrs {}

/**
 * The `LoadingPost` component shows a placeholder that looks like a post,
 * indicating that the post is loading.
 */
export default class LoadingPost<CustomAttrs extends ILoadingPostAttrs = ILoadingPostAttrs> extends Component<CustomAttrs> {
  view() {
    return (
      <div className={classList(this.attrs.className, 'Post CommentPost LoadingPost')}>
        <header className="Post-header">
          <Avatar user={null} className="PostUser-avatar" />
          <div className="fakeText" />
        </header>

        <div className="Post-body">
          <div className="fakeText" />
          <div className="fakeText" />
          <div className="fakeText" />
        </div>
      </div>
    );
  }
}
