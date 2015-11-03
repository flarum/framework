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

    items.remove('scrubber');

    const count = this.discussion.repliesCount();

    items.add('replies', <h3>
      <a href={app.route.discussion(this.discussion).replace('/embed', '/d')} config={m.route}>
        {count} comment{count == 1 ? '' : 's'}
      </a>
    </h3>, 100);

    const props = items.get('controls').props;
    props.className = props.className.replace('App-primaryControl', '');

    return items;
  }
}
