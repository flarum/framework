import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import m from 'mithril';
import AbstractPost from '../../../../src/forum/components/AbstractPost';
import Button from '../../../../src/common/components/Button';

beforeAll(() => bootstrapForum());

let freshness = new Date('2024-01-01T00:00:00Z');

class TestPost extends AbstractPost {
  user() {
    return false as const;
  }

  controls() {
    return [m(Button, 'Edit')];
  }

  freshness() {
    return freshness;
  }

  createdByStarter() {
    return false;
  }
}

describe('AbstractPost controls dropdown', () => {
  let root: HTMLElement;

  const actions = () => root.querySelector('.Post-actions')!;
  const controls = () => $(root.querySelector('.Post-controls')!);

  beforeEach(() => {
    root = document.createElement('div');
    document.body.append(root);
    m.mount(root, { view: () => m(TestPost) });
  });

  afterEach(() => {
    m.mount(root, null);
    root.remove();
  });

  it('marks the action bar as open while the dropdown is open', () => {
    controls().trigger('shown.bs.dropdown');
    m.redraw.sync();

    expect(actions().classList.contains('openWithin')).toBe(true);

    controls().trigger('hidden.bs.dropdown');
    m.redraw.sync();

    expect(actions().classList.contains('openWithin')).toBe(false);
  });

  it('clears the open state when the dropdown closes after the post was rebuilt while open', () => {
    controls().trigger('shown.bs.dropdown');

    // Rebuild the retained subtree while the dropdown is open.
    freshness = new Date(freshness.getTime() + 1000);
    m.redraw.sync();

    expect(actions().classList.contains('openWithin')).toBe(true);

    // Close the dropdown without any further change to the post or its author,
    // so the post keeps its subtree on the next redraw.
    controls().trigger('hidden.bs.dropdown');
    m.redraw.sync();

    expect(controls().hasClass('open')).toBe(false);
    expect(actions().classList.contains('openWithin')).toBe(false);
  });
});
