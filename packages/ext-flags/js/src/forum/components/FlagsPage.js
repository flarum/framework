import app from 'flarum/forum/app';
import Page from 'flarum/components/Page';

import FlagList from './FlagList';

/**
 * The `FlagsPage` component shows the flags list. It is only
 * used on mobile devices where the flags dropdown is within the drawer.
 */
export default class FlagsPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);

    app.history.push('flags');

    app.flags.load();

    this.bodyClass = 'App--flags';
  }

  view() {
    return (
      <div className="FlagsPage">
        <FlagList state={app.flags}></FlagList>
      </div>
    );
  }
}
