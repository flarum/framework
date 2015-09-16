import Component from 'flarum/Component';
import PostStream from 'flarum/components/PostStream';

export default class DiscussionPage extends Component {
  constructor(...args) {
    super(...args);

    /**
     * The discussion that is being viewed.
     *
     * @type {Discussion}
     */
    const discussion = this.discussion = app.preloadedDocument();

    let includedPosts = [];
    if (discussion.payload && discussion.payload.included) {
      includedPosts = discussion.payload.included
        .filter(record => record.type === 'posts' && record.relationships && record.relationships.discussion)
        .map(record => app.store.getById('posts', record.id))
        .sort((a, b) => a.id() - b.id())
        .slice(0, 20);
    }

    this.stream = new PostStream({discussion, includedPosts});
  }

  view() {
    return (
      <div className="DiscussionPage">
        <div class="container">
          <div className="DiscussionPage-discussion">
            <div className="DiscussionPage-stream">
              {this.stream.render()}
            </div>
          </div>
        </div>
      </div>
    );
  }
}
