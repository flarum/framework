import app from '../../common/app';
import Component from '../Component';
import Button from './Button';
import LinkButton from './LinkButton';
import type Mithril from 'mithril';
import classList from '../utils/classList';
import ItemList from '../utils/ItemList';

/**
 * The `Navigation` component displays a set of navigation buttons. In `drawer`
 * mode — the fixed control strip at the top of the mobile layout — the drawer
 * toggle is always present, so the menu and the notification badge it carries
 * stay reachable on every page; the back button joins it only when there is
 * history to pop. Elsewhere (the desktop header) it is the back button alone.
 *
 * If the app has a pane, it will also include a 'pin' button which toggles the
 * pinned state of the pane.
 *
 * Accepts the following attrs:
 *
 * - `className` The name of a class to set on the root element.
 * - `drawer` Whether or not to show a button to toggle the app's drawer.
 * - `search` A rendered search control to include in `drawer` mode, so search
 *   is reachable from the fixed mobile strip on every page.
 */
export default class Navigation extends Component {
  view() {
    const { pane } = app;

    return (
      <div
        className={classList('Navigation ButtonGroup', this.attrs.className)}
        onmouseenter={pane && pane.show.bind(pane)}
        onmouseleave={pane && pane.onmouseleave.bind(pane)}
      >
        {this.items().toArray()}
      </div>
    );
  }

  /**
   * The controls shown in the navigation, as an ItemList so that other parts of
   * the app — and extensions — can contribute their own. The forum app adds a
   * search control here in `drawer` mode.
   */
  items(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    if (this.attrs.drawer) {
      items.add('drawer', this.getDrawerButton(), 100);

      // A search control supplied by the frontend — the forum passes one, so
      // search is reachable on every page from the fixed mobile strip rather
      // than only from inside the drawer. Kept generic (a rendered child, not a
      // component reference) so this common component stays free of any
      // forum-only import.
      if (this.attrs.search) {
        items.add('search', this.attrs.search, 95);
      }
    }

    if (app.history?.canGoBack()) {
      items.add('back', this.getBackButton(), 90);

      const paneButton = this.getPaneButton();

      if (paneButton) {
        items.add('pane', paneButton, 80);
      }
    }

    return items;
  }

  /**
   * Get the back button.
   */
  protected getBackButton(): Mithril.Children {
    const { history } = app;
    const previous = history?.getPrevious();

    return (
      <LinkButton
        className="Button Navigation-back Button--icon"
        href={history?.backUrl()}
        icon="fas fa-chevron-left"
        aria-label={previous?.title}
        onclick={(e: MouseEvent) => {
          if (e.shiftKey || e.ctrlKey || e.metaKey || e.which === 2) return;
          e.preventDefault();
          history?.back();
        }}
      />
    );
  }

  /**
   * Get the pane pinned toggle button.
   */
  protected getPaneButton(): Mithril.Children {
    const { pane } = app;

    if (!pane || !pane.active) return null;

    return (
      <Button
        className={classList('Button Button--icon Navigation-pin', { active: pane.pinned })}
        onclick={pane.togglePinned.bind(pane)}
        icon="fas fa-thumbtack"
        // The label names the control and stays put; `aria-pressed` carries
        // whether it is currently on. Swapping the label for the action instead
        // ("Pin"/"Unpin") would leave a screen reader reading a button whose
        // name changes under it, with the state only ever implied.
        //
        // Stringified deliberately: Mithril treats a boolean as an HTML boolean
        // attribute, so `false` omits it altogether and `true` renders it empty.
        // ARIA needs the words, and an absent `aria-pressed` makes this read as
        // a plain button rather than a toggle that happens to be off.
        aria-label={app.translator.trans('core.lib.nav.pin_pane_button')}
        aria-pressed={pane.pinned ? 'true' : 'false'}
      />
    );
  }

  /**
   * Get the drawer toggle button.
   */
  protected getDrawerButton(): Mithril.Children {
    if (!this.attrs.drawer) return null;

    const { drawer } = app;
    const user = app.session.user;

    return (
      <Button
        className={classList('Button Button--icon Navigation-drawer', { new: user?.newNotificationCount() })}
        onclick={(e: MouseEvent) => {
          e.stopPropagation();
          drawer.show();
        }}
        icon="fas fa-bars"
        aria-label={app.translator.trans('core.lib.nav.drawer_button')}
      />
    );
  }
}
