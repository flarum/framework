import Component from '../../common/Component';
import type { ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
import app from '../app';
import Button from '../../common/components/Button';
import SelectDropdown from '../../common/components/SelectDropdown';
import listItems from '../../common/helpers/listItems';
import LinkButton from '../../common/components/LinkButton';
import classList from '../../common/utils/classList';

export interface IndexSidebarAttrs extends ComponentAttrs {}

export default class IndexSidebar<CustomAttrs extends IndexSidebarAttrs = IndexSidebarAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    return (
      <nav className={classList('IndexPage-nav sideNav', this.attrs.className)}>
        <ul>{listItems(this.items().toArray())}</ul>
      </nav>
    );
  }

  /**
   * Build an item list for the sidebar of the index page. By default this is a
   * "New Discussion" button, and then a DropdownSelect component containing a
   * list of navigation items.
   */
  items() {
    const items = new ItemList<Mithril.Children>();
    const canStartDiscussion = app.forum.attribute('canStartDiscussion') || !app.session.user;

    items.add(
      'newDiscussion',
      <Button
        icon="fas fa-edit"
        className="Button Button--primary IndexPage-newDiscussion"
        itemClassName="App-primaryControl"
        onclick={() => {
          // If the user is not logged in, the promise rejects, and a login modal shows up.
          // Since that's already handled, we dont need to show an error message in the console.
          return this.newDiscussionAction().catch(() => {});
        }}
        disabled={!canStartDiscussion}
      >
        {app.translator.trans(`core.forum.index.${canStartDiscussion ? 'start_discussion_button' : 'cannot_start_discussion_button'}`)}
      </Button>
    );

    items.add(
      'nav',
      <SelectDropdown
        buttonClassName="Button"
        className="App-titleControl"
        accessibleToggleLabel={app.translator.trans('core.forum.index.toggle_sidenav_dropdown_accessible_label')}
      >
        {this.navItems().toArray()}
      </SelectDropdown>
    );

    return items;
  }

  /**
   * Build an item list for the navigation in the sidebar of the index page. By
   * default this is just the 'All Discussions' link.
   */
  navItems() {
    const items = new ItemList<Mithril.Children>();
    const params = app.search.state.stickyParams();

    items.add(
      'allDiscussions',
      <LinkButton href={app.route('index', params)} icon="far fa-comments">
        {app.translator.trans('core.forum.index.all_discussions_link')}
      </LinkButton>,
      100
    );

    return items;
  }

  /**
   * Open the composer for a new discussion or prompt the user to login.
   */
  newDiscussionAction(): Promise<unknown> {
    return new Promise((resolve, reject) => {
      if (app.session.user) {
        app.composer.load(() => import('./DiscussionComposer'), { user: app.session.user }).then(() => app.composer.show());

        return resolve(app.composer);
      } else {
        app.modal.show(() => import('./LogInModal'));

        return reject();
      }
    });
  }
}
