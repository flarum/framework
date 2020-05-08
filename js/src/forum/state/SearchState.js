import ItemList from '../../common/utils/ItemList';
import DiscussionsSearchSource from '../components/DiscussionsSearchSource';
import UsersSearchSource from '../components/UsersSearchSource';

export default class SearchState {
  constructor() {
    /**
     * An array of SearchSources.
     *
     * @type {SearchSource[]}
     */
    this.sources = this.sourceItems().toArray();
  }

  /**
   * Build an item list of SearchSources.
   *
   * @return {ItemList}
   */
  sourceItems() {
    const items = new ItemList();

    if (app.forum.attribute('canViewDiscussions')) items.add('discussions', new DiscussionsSearchSource());
    if (app.forum.attribute('canViewUserList')) items.add('users', new UsersSearchSource());

    return items;
  }
}
