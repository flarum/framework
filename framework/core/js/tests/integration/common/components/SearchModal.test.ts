import bootstrapAdmin from '@flarum/jest-config/src/boostrap/admin';
import { jest } from '@jest/globals';
import SearchModal from '../../../../src/common/components/SearchModal';
import GeneralSearchSource from '../../../../src/admin/components/GeneralSearchSource';
import SearchState from '../../../../src/common/states/SearchState';
import { app } from '../../../../src/admin';
import Stream from '../../../../src/common/utils/Stream';

beforeAll(() => bootstrapAdmin());

describe('SearchModal.selectResult', () => {
  beforeAll(() => app.boot());

  /**
   * Creates a minimal SearchModal instance with mocked internals
   * so that selectResult() can be tested without a full DOM mount.
   */
  function makeModal(itemHtml: string, query: string = 'test'): SearchModal & { routeSetSpy: jest.Mock; buttonClickSpy: jest.Mock } {
    const modal = new SearchModal();

    // Set up required Stream properties
    (modal as any).query = Stream(query);
    (modal as any).loadingSources = [];
    (modal as any).searchTimeout = undefined;
    (modal as any).index = 0;
    (modal as any).activeSource = Stream(new GeneralSearchSource());
    (modal as any).searchState = new SearchState();
    (modal as any).sources = [new GeneralSearchSource()];

    // Build a real jsdom <li> element for getItem() to return
    const li = document.createElement('li');
    li.innerHTML = itemHtml;

    // Spy on m.route.set
    const routeSetSpy = jest.fn();
    (m as any).route = { set: routeSetSpy };

    // Track button clicks
    const buttonClickSpy = jest.fn();
    const button = li.querySelector('button');
    if (button) {
      button.addEventListener('click', buttonClickSpy);
    }

    // Mock getItem() to return a jQuery wrapper of the li
    (modal as any).getItem = () => $(li);

    return Object.assign(modal, { routeSetSpy, buttonClickSpy });
  }

  it('navigates via m.route.set when item has data-id and gotoItem returns a URL', () => {
    const modal = makeModal('<li data-id="ext-id"><a href="/admin/extensions/flarum-foo">Flarum Foo</a></li>');

    // Override gotoItem to return a URL (as forum sources do)
    (modal as any).activeSource().gotoItem = () => '/admin/extensions/flarum-foo';

    modal.selectResult();

    expect(modal.routeSetSpy).toHaveBeenCalledWith('/admin/extensions/flarum-foo');
  });

  it('navigates via the anchor href when gotoItem returns null (admin GeneralSearchSource)', () => {
    // This is the bug case: GeneralSearchSource sets data-id but gotoItem() returns null.
    // The item contains a <Link> (<a>) — selectResult() should navigate via the href.
    const modal = makeModal('<li data-id="flarum-tags-tag-name"><a href="/admin/extensions/flarum-tags">Tags</a></li>');

    // gotoItem returns null, as GeneralSearchSource does
    (modal as any).activeSource().gotoItem = () => null;

    modal.selectResult();

    expect(modal.routeSetSpy).toHaveBeenCalledWith('/admin/extensions/flarum-tags');
  });

  it('navigates via anchor href when item has no data-id but contains a link', () => {
    const modal = makeModal('<li><a href="/admin/basics">Basics</a></li>');

    modal.selectResult();

    expect(modal.routeSetSpy).toHaveBeenCalledWith('/admin/basics');
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
