import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import { jest } from '@jest/globals';
import SearchModal from '../../../../src/common/components/SearchModal';
import GeneralSearchSource from '../../../../src/admin/components/GeneralSearchSource';
import SearchState from '../../../../src/common/states/SearchState';
import { app } from '../../../../src/admin';
import Stream from '../../../../src/common/utils/Stream';

beforeAll(() => bootstrapAdmin());

describe('SearchModal.selectResult', () => {
  beforeAll(() => app.boot());
  afterEach(() => jest.restoreAllMocks());

  /**
   * Creates a minimal SearchModal instance with mocked internals
   * so that selectResult() can be tested without a full DOM mount.
   */
  function makeModal(
    itemHtml: string,
    query: string = 'test'
  ): SearchModal & { routeSetSpy: jest.Mock; buttonClickSpy: jest.Mock; linkClickSpy: jest.Mock; closeSpy: jest.Mock } {
    const modal = new SearchModal();

    // Set up required Stream properties
    (modal as any).query = Stream(query);
    (modal as any).loadingSources = [];
    (modal as any).searchTimeout = undefined;
    (modal as any).index = 0;
    (modal as any).activeSource = Stream(new GeneralSearchSource());
    (modal as any).searchState = new SearchState();
    (modal as any).sources = [new GeneralSearchSource()];

    // Build the real jsdom <li> element for getItem() to return
    const ul = document.createElement('ul');
    ul.innerHTML = itemHtml;
    const li = ul.firstElementChild!;

    // Spy on m.route.set
    const routeSetSpy = jest.fn();
    (m as any).route = { set: routeSetSpy };

    // Track button clicks
    const buttonClickSpy = jest.fn();
    const button = li.querySelector('button');
    if (button) {
      button.addEventListener('click', buttonClickSpy);
    }

    // Track clicks on the result's link, without letting jsdom follow it
    const linkClickSpy = jest.fn((e: Event) => e.preventDefault());
    li.querySelector('a')?.addEventListener('click', linkClickSpy);

    const closeSpy = jest.spyOn(app.modal, 'close').mockImplementation(() => {});

    // Mock getItem() to return a jQuery wrapper of the li
    (modal as any).getItem = () => $(li);

    return Object.assign(modal, { routeSetSpy, buttonClickSpy, linkClickSpy, closeSpy });
  }

  it('navigates via m.route.set when item has data-id and gotoItem returns a URL', () => {
    const modal = makeModal('<li data-id="ext-id"><a href="/admin/extensions/flarum-foo">Flarum Foo</a></li>');

    // Override gotoItem to return a URL (as forum sources do)
    (modal as any).activeSource().gotoItem = () => '/admin/extensions/flarum-foo';

    modal.selectResult();

    expect(modal.routeSetSpy).toHaveBeenCalledWith('/admin/extensions/flarum-foo');
  });

  // Navigating to the page that is already open doesn't start a new page,
  // which is otherwise what closes the modal (#5125).
  it('closes once it has navigated to the chosen result', () => {
    const modal = makeModal('<li data-id="ext-id"><a href="/u/admin">admin</a></li>');

    (modal as any).activeSource().gotoItem = () => '/u/admin';

    modal.selectResult();

    expect(modal.routeSetSpy).toHaveBeenCalledWith('/u/admin');
    expect(modal.closeSpy).toHaveBeenCalledTimes(1);
  });

  // GeneralSearchSource sets data-id but gotoItem() returns null, so the result's
  // link is followed. Under the admin's hash routing that link's href is
  // `#/extension/…`, and passing it to m.route.set added a second `#` (#5124).
  it('follows the link itself when gotoItem returns null (admin GeneralSearchSource)', () => {
    const modal = makeModal('<li data-id="flarum-tags-tag-name"><a href="#/extension/flarum-tags">Tags</a></li>');

    (modal as any).activeSource().gotoItem = () => null;

    modal.selectResult();

    expect(modal.linkClickSpy).toHaveBeenCalledTimes(1);
    expect(modal.routeSetSpy).not.toHaveBeenCalled();
  });

  it('follows the link itself when the item has no data-id', () => {
    const modal = makeModal('<li><a href="#/basics">Basics</a></li>');

    modal.selectResult();

    expect(modal.linkClickSpy).toHaveBeenCalledTimes(1);
    expect(modal.routeSetSpy).not.toHaveBeenCalled();
  });

  it('clicks a button when item has no link and no data-id', () => {
    const modal = makeModal('<li><button type="button">Go</button></li>', 'test');

    // No link in item, ensure no navigation
    modal.selectResult();

    expect(modal.routeSetSpy).not.toHaveBeenCalled();
    expect(modal.buttonClickSpy).toHaveBeenCalled();
  });

  it('does not throw when item has neither a link nor a button', () => {
    // Previously this would crash: item.find('button')[0].click() when no button exists
    const modal = makeModal('<li><span>No interactive element</span></li>');

    expect(() => modal.selectResult()).not.toThrow();
    expect(modal.routeSetSpy).not.toHaveBeenCalled();
  });
});

describe('SearchModal result links', () => {
  beforeAll(() => app.boot());
  afterEach(() => jest.restoreAllMocks());

  /**
   * A results list with one link, its clicks handed to the modal the way the
   * rendered list hands them over.
   */
  function results(linkAttrs: string = '') {
    const modal = new SearchModal();
    const closeSpy = jest.spyOn(app.modal, 'close').mockImplementation(() => {});

    const ul = document.createElement('ul');
    ul.innerHTML = `<li><a href="/u/admin" ${linkAttrs}>admin</a><button type="button">More</button></li>`;
    ul.addEventListener('click', (e) => (modal as any).onLinkClick(e));

    const link = ul.querySelector('a')!;
    link.addEventListener('click', (e) => e.preventDefault());

    return { modal, closeSpy, link, button: ul.querySelector('button')! };
  }

  it("closes once a result's link is clicked, even when it leads to the page already open", () => {
    const { closeSpy, link } = results();

    link.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, button: 0 }));

    expect(closeSpy).toHaveBeenCalledTimes(1);
  });

  it('stays open for a click meant for a new tab or window', () => {
    const { closeSpy, link } = results();

    for (const modifier of ['ctrlKey', 'metaKey', 'shiftKey']) {
      link.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, button: 0, [modifier]: true }));
    }

    expect(closeSpy).not.toHaveBeenCalled();
  });

  it('stays open for a link that opens in a new tab', () => {
    const { closeSpy, link } = results('target="_blank"');

    link.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, button: 0 }));

    expect(closeSpy).not.toHaveBeenCalled();
  });

  it('ignores clicks that are not on a link', () => {
    const { closeSpy, button } = results();

    button.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, button: 0 }));

    expect(closeSpy).not.toHaveBeenCalled();
  });
});
