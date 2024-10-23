import app from '../../common/app';
import Component from '../Component';
import Button from './Button';
import LinkButton from './LinkButton';
import type Mithril from 'mithril';
import classList from '../utils/classList';

/**
 * The `Navigation` component displays a set of navigation buttons. Typically
 * this is just a back button which pops the app's History. If the user is on
 * the root page and there is no history to pop, then in some instances it may
 * show a button that toggles the app's drawer.
 *
 * If the app has a pane, it will also include a 'pin' button which toggles the
 * pinned state of the pane.
 *
 * Accepts the following attrs:
 *
 * - `className` The name of a class to set on the root element.
 * - `drawer` Whether or not to show a button to toggle the app's drawer if
 *   there is no more history to pop.
 */
export default class Navigation extends Component {
  view() {
    const { history, pane } = app;

    return (
      <div
        className={classList('Navigation ButtonGroup', this.attrs.className)}
        onmouseenter={pane && pane.show.bind(pane)}
        onmouseleave={pane && pane.onmouseleave.bind(pane)}
      >
        {history?.canGoBack() ? [this.getBackButton(), this.getPaneButton()] : this.getDrawerButton()}
      </div>
    );
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
