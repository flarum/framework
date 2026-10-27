import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
import Navigation from '../../../../src/common/components/Navigation';
import type ItemList from '../../../../src/common/utils/ItemList';
import { extend } from '../../../../src/common/extend';
import { app } from '../../../../src/forum';
import m from 'mithril';
import type Mithril from 'mithril';
import mq from 'mithril-query';

beforeAll(() => bootstrapForum());

describe('Navigation', () => {
  beforeAll(() => app.boot());

  const originalItems = Navigation.prototype.items;

  const setCanGoBack = (value: boolean) => {
    jest.spyOn(app.history, 'canGoBack').mockReturnValue(value);
    jest.spyOn(app.history, 'backUrl').mockReturnValue('/');
    jest.spyOn(app.history, 'getPrevious').mockReturnValue({ name: 'x', title: 'Previous', url: '/' } as any);
  };

  afterEach(() => {
    Navigation.prototype.items = originalItems;
    jest.restoreAllMocks();
  });

  test('renders as normal nav', () => {
    const nav = mq(Navigation);

    expect(nav).toBeTruthy();
  });

  test('renders as drawer', () => {
    const nav = mq(Navigation, { drawer: true });

    expect(nav).toBeTruthy();
  });

  test('the drawer toggle stays available even when there is history to go back to', () => {
    // The flaw this fixes: the drawer toggle used to be replaced by the back
    // button off the home page, so the menu — and the notification badge it
    // carries — became unreachable until the reader navigated home.
    setCanGoBack(true);

    const nav = mq(Navigation, { drawer: true });

    expect(nav).toHaveElement('.Navigation-drawer');
  });

  test('the back button appears only when there is history to go back to', () => {
    setCanGoBack(true);
    expect(mq(Navigation, { drawer: true })).toHaveElement('.Navigation-back');

    jest.restoreAllMocks();
    setCanGoBack(false);
    expect(mq(Navigation, { drawer: true })).not.toHaveElement('.Navigation-back');
  });

  test('without drawer mode there is no drawer toggle', () => {
    // The desktop header uses Navigation without `drawer`, and must keep its
    // back-only behaviour.
    setCanGoBack(true);

    const nav = mq(Navigation, {});

    expect(nav).not.toHaveElement('.Navigation-drawer');
    expect(nav).toHaveElement('.Navigation-back');
  });

  test('an extension can add its own control to the group', () => {
    extend(Navigation.prototype, 'items', (items: ItemList<Mithril.Children>) => {
      items.add('custom', m('button', { className: 'my-nav-control' }), 5);
    });

    const nav = mq(Navigation, { drawer: true });

    expect(nav).toHaveElement('.my-nav-control');
  });
});
