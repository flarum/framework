import BaseDiscussionPage from 'flarum/components/DiscussionPage';
import PostStream from 'flarum/components/PostStream';
import listItems from 'flarum/helpers/listItems';

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
            <nav className="DiscussionPage-nav--embed">
              <ul>{listItems(this.sidebarItems().toArray())}</ul>
            </nav>
            <div className="DiscussionPage-stream">
              {this.stream ? this.stream.render() : ''}
            </div>
          </div>
        </div>
      </div>
    );
  }

  sidebarItems() {
    const items = super.sidebarItems();

    delete items.scrubber;

    return items;
  }
}
