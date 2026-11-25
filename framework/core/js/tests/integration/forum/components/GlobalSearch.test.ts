import m from 'mithril';
import mq from 'mithril-query';
import { jest } from '@jest/globals';
import GlobalSearch from '../../../../src/forum/components/GlobalSearch';
import SearchState from '../../../../src/common/states/SearchState';
import app from '../../../../src/forum/app';
import { bsTooltip, removeTooltips, tooltipShown, useRealBootstrapTooltips } from '../../common/tooltips';

beforeAll(async () => {
  await useRealBootstrapTooltips();
  app.boot();

  // The component only renders once it has somewhere to search, and the
  // sources it offers depend on what the actor is allowed to see.
  app.forum.pushAttributes({ canViewForum: true, canSearchUsers: true });
});

let root: HTMLElement | undefined;
let drawer: HTMLElement | undefined;

beforeEach(() => {
  // The forum bootstrap creates the drawer's focus trap before the test adds a
  // #drawer element, so activating it would throw for want of a container;
  // only the drawer's open state matters here.
  jest.spyOn(app.drawer.focusTrap, 'activate').mockImplementation(() => {});
  jest.spyOn(app.drawer.focusTrap, 'deactivate').mockImplementation(() => {});
});

afterEach(() => {
  if (root) {
    m.mount(root, null);
    root.remove();
    root = undefined;
  }

  app.drawer.hide();
  drawer?.remove();
  drawer = undefined;

  removeTooltips();
  jest.restoreAllMocks();
});

/**
 * Searching happens in a modal. The control in the header used to be a
 * `readonly` text field that opened it, which meant assistive technology
 * announced a textbox that could not be typed into, and the header had to
 * reserve a text field's width for something that only ever behaved as a
 * button. These cover the shape of the control rather than how it looks.
 */
describe('GlobalSearch renders a button, not a text field', () => {
  const search = () => mq(m(GlobalSearch, { state: new SearchState() }));

  it('renders a button', () => {
    expect(search()).toHaveElement('button.Search-input');
  });

  it('renders no input at all', () => {
    // The point of the change: nothing here should be typed into.
    expect(search()).not.toHaveElement('input');
  });

  it('gives the button an explicit type so it never submits a form', () => {
    expect(search()).toHaveElement('button.Search-input[type=button]');
  });

  it('gives the button an accessible name', () => {
    // Its label is hidden in the header, so the name has to come from aria.
    expect(search()).toHaveElement('button.Search-input[aria-label]');
  });

  it('describes the control with the standard tooltip', () => {
    const rendered = search();
    const button = rendered.find('button.Search-input')[0] as HTMLElement;

    expect(bsTooltip(button).getTitle()).toBe('Search Forum');
  });

  it('does not add a native tooltip alongside the standard tooltip', () => {
    expect(search()).not.toHaveElement('button.Search-input[title]:not([title=""])');
  });

  it('marks the surrounding element as a search landmark', () => {
    expect(search()).toHaveElement('[role=search]');
    expect(search()).toHaveElement('.Search[aria-label]');
  });

  it('renders an icon and a label, like the other header controls', () => {
    const rendered = search();

    expect(rendered).toHaveElement('.Button-icon');
    expect(rendered).toHaveElement('.Button-label');
  });

  it('styles itself as a flat button, matching the controls beside it', () => {
    // `Button--link` forces a transparent background and a link-coloured
    // hover; the notification and message controls are `Button--flat`, and
    // search sits among them.
    expect(search()).toHaveElement('button.Search-input.Button--flat');
  });
});

describe('GlobalSearch opens the search modal', () => {
  it('opens the modal when clicked', () => {
    jest.useFakeTimers();
    const show = jest.spyOn(app.modal, 'show').mockImplementation(() => {});

    const rendered = mq(m(GlobalSearch, { state: new SearchState() }));
    rendered.click('button.Search-input');

    // The click closes the drawer first and opens the modal just after, so
    // that the drawer is not left sitting behind it.
    jest.runAllTimers();

    expect(show).toHaveBeenCalled();

    show.mockRestore();
    jest.useRealTimers();
  });

  it('hides the drawer before opening, so it is not left behind the modal', () => {
    jest.useFakeTimers();
    const hide = jest.spyOn(app.drawer, 'hide').mockImplementation(() => {});
    jest.spyOn(app.modal, 'show').mockImplementation(() => {});

    const rendered = mq(m(GlobalSearch, { state: new SearchState() }));
    rendered.click('button.Search-input');

    expect(hide).toHaveBeenCalled();

    jest.runAllTimers();
    jest.restoreAllMocks();
    jest.useRealTimers();
  });
});

describe('GlobalSearch reflects the current query', () => {
  it('shows the prompt when nothing has been searched for', () => {
    const rendered = mq(m(GlobalSearch, { state: new SearchState() }));

    expect(rendered).toContainRaw('Search Forum');
  });

  it('shows the query in place of the prompt once one has been run', () => {
    const state = new SearchState();
    state.setValue('flarum');

    const rendered = mq(m(GlobalSearch, { state }));

    expect(rendered).toContainRaw('flarum');
  });

  it('offers a way to clear an active query', () => {
    const state = new SearchState();
    state.setValue('flarum');

    expect(mq(m(GlobalSearch, { state }))).toHaveElement('button.Search-clear');
  });

  it('offers nothing to clear when there is no query', () => {
    expect(mq(m(GlobalSearch, { state: new SearchState() }))).not.toHaveElement('button.Search-clear');
  });

  it('clears the query without opening the modal', () => {
    const state = new SearchState();
    state.setValue('flarum');
    const show = jest.spyOn(app.modal, 'show').mockImplementation(() => {});

    const rendered = mq(m(GlobalSearch, { state }));
    rendered.click('button.Search-clear');

    expect(state.getValue()).toBe('');
    expect(show).not.toHaveBeenCalled();

    show.mockRestore();
  });
});

describe('GlobalSearch in the phone drawer', () => {
  it('shows no tooltip while the drawer is open, and shows it once the drawer closes', () => {
    // The search control sits in the drawer, so its "Search Forum" tooltip
    // would otherwise cover the notification row below it.
    drawer = document.createElement('div');
    drawer.id = 'drawer';
    document.body.appendChild(drawer);

    root = document.createElement('div');
    drawer.appendChild(root);
    m.mount(root, { view: () => m(GlobalSearch, { state: new SearchState() }) });

    app.drawer.show();

    const button = root.querySelector<HTMLElement>('button.Search-input')!;
    $(button).trigger('mouseenter');
    expect(tooltipShown()).toBe(false);

    // The control keeps its tooltip for after the drawer closes.
    app.drawer.hide();
    $(button).trigger('mouseleave');
    $(button).trigger('mouseenter');
    expect($('.tooltip.in .tooltip-inner').text()).toBe('Search Forum');
  });
});
