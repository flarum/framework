import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
import app from 'flarum/forum/app';
import ModalManager from 'flarum/common/components/ModalManager';
import m from 'mithril';

import TagSelectionModal, { type ITagSelectionModalAttrs } from '../../../src/common/components/TagSelectionModal';
import Tag from '../../../src/common/models/Tag';

const mountedRoots: HTMLElement[] = [];

beforeAll(() => {
  bootstrapForum();
  app.boot();

  app.store.models.tags = Tag;
  app.store.pushPayload({
    data: [{ type: 'tags', id: '1', attributes: { name: 'General', slug: 'general', position: 0, isPrimary: true } }],
  });

  app.tagList = { load: () => Promise.resolve([general()]) } as any;
});

beforeEach(() => {
  mountedRoots.length = 0;
  app.modal.close();
});

afterEach(() => {
  mountedRoots.forEach((root) => {
    m.mount(root, null);
    root.remove();
  });
  app.modal.close();
});

const general = () => app.store.getById<Tag>('tags', '1')!;

async function open(attrs: Partial<ITagSelectionModalAttrs> = {}) {
  const root = document.body.appendChild(document.createElement('div'));
  mountedRoots.push(root);

  m.mount(root, {
    view: () => m(ModalManager, { state: app.modal }),
  });
  app.modal.show(TagSelectionModal, { canSelect: () => true, ...attrs });
  m.redraw.sync();
  await new Promise((resolve) => setTimeout(resolve, 0));
  m.redraw.sync();

  return {
    root,
    keydown(key: string, keyCode: number, options: KeyboardEventInit = {}) {
      const input = root.querySelector('.TagsInput input')!;
      const event = new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true, ...options });
      Object.defineProperty(event, 'which', { value: keyCode });
      input.dispatchEvent(event);
      m.redraw.sync();
      return event;
    },
  };
}

describe('TagSelectionModal keyboard navigation', () => {
  it('leaves an unselected tag alone when Tab moves focus away', async () => {
    const modal = await open();

    expect(modal.keydown('Tab', 9).defaultPrevented).toBe(false);
    expect(modal.root.querySelector('.SelectTagListItem.selected')).toBeNull();
    expect(modal.root.querySelector('.TagSelectionModal')).not.toBeNull();
  });

  it.each([false, true])('does not submit existing tags on Tab (shiftKey=%s)', async (shiftKey) => {
    const onsubmit = jest.fn();
    const modal = await open({ selectedTags: [general()], onsubmit });

    expect(modal.keydown('Tab', 9, { shiftKey }).defaultPrevented).toBe(false);
    expect(onsubmit).not.toHaveBeenCalled();
    expect(modal.root.querySelector('.TagSelectionModal')).not.toBeNull();
    expect(modal.root.querySelector('.SelectTagListItem.selected')).not.toBeNull();
  });

  it('still selects the highlighted tag on Enter', async () => {
    const modal = await open();

    expect(modal.keydown('Enter', 13).defaultPrevented).toBe(true);
    expect(modal.root.querySelector('.SelectTagListItem.selected')).not.toBeNull();
  });

  it('still submits existing tags on Ctrl+Enter', async () => {
    const onsubmit = jest.fn();
    const modal = await open({ selectedTags: [general()], onsubmit });

    modal.keydown('Enter', 13, { ctrlKey: true });

    expect(onsubmit).toHaveBeenCalledWith([general()]);
  });
});
