import BaseDiscussionPage from 'flarum/components/DiscussionPage';
import PostStream from 'flarum/components/PostStream';

export default class DiscussionPage extends BaseDiscussionPage {
  init() {
    super.init();

    this.bodyClass = null;
  }

  view() {
    return (
      <div className="DiscussionPage">
        <div class="container">
          <div className="DiscussionPage-discussion">
            <div className="DiscussionPage-stream">
              {this.stream ? this.stream.render() : ''}
            </div>
          </div>
        </div>
      </div>
    );
  }
}
