import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import mq from 'mithril-query';
import { jest } from '@jest/globals';
import { app } from '../../../../src/forum';
import ModalManager from '../../../../src/common/components/ModalManager';
import UserSelectionModal, { type IUserSelectionModalAttrs } from '../../../../src/common/components/UserSelectionModal';
import type User from '../../../../src/common/models/User';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  app.store.pushPayload({
    data: [
      { type: 'users', id: '2', attributes: { username: 'alice', displayName: 'alice' } },
      { type: 'users', id: '3', attributes: { username: 'bob', displayName: 'bob' } },
    ],
  } as any);
});

beforeEach(() => app.modal.close());
afterEach(() => jest.restoreAllMocks());

const alice = () => app.store.getById<User>('users', '2')!;
const bob = () => app.store.getById<User>('users', '3')!;

async function open(attrs: Partial<IUserSelectionModalAttrs>) {
  jest.spyOn(app.store, 'find').mockResolvedValue([alice(), bob()] as never);

  const manager = mq(ModalManager, { state: app.modal });

  app.modal.show(UserSelectionModal, { selected: [], onsubmit: () => {}, ...attrs });
  manager.redraw();

  // Let the search the modal starts on opening settle.
  await new Promise((resolve) => setTimeout(resolve, 0));
  manager.redraw();

  return manager;
}

describe('UserSelectionModal', () => {
  it('lists everyone as selectable when nothing marks anyone unavailable', async () => {
    const manager = await open({});

    expect(manager).not.toHaveElement('.UserSelectionModal-listItem--unavailable');

    manager.click('.UserSearchResult[data-id="3"]');
    manager.redraw();

    expect(manager).toHaveElement('.UserSelectionModal-listItem--selected[data-id="3"]');
  });

  it('leaves out users excluded by id', async () => {
    const manager = await open({ excluded: ['3'] });

    expect(manager).not.toHaveElement('.UserSearchResult[data-id="3"]');
    expect(manager).toHaveElement('.UserSearchResult[data-id="2"]');
  });

  it('leaves out users excluded by a check', async () => {
    const manager = await open({ excluded: (user: User) => user === bob() });

    expect(manager).not.toHaveElement('.UserSearchResult[data-id="3"]');
    expect(manager).toHaveElement('.UserSearchResult[data-id="2"]');
  });

  it('shows why someone is unavailable, and does not let them be selected', async () => {
    const manager = await open({ unavailable: (user: User) => (user === bob() ? 'Not available' : null) });

    expect(manager).toHaveElement('.UserSelectionModal-listItem--unavailable[data-id="3"]');
    expect(manager).toHaveElement('.UserSearchResult[data-id="3"] button[disabled]');
    expect(manager).toHaveElement('.UserSearchResult[data-id="3"] .UserSelectionModal-listItem-reason');
    expect(manager).not.toHaveElement('.UserSearchResult[data-id="3"] input[type="checkbox"]');
    expect(manager).toContainRaw('Not available');

    manager.click('.UserSearchResult[data-id="3"]', undefined, true);
    manager.redraw();

    expect(manager).not.toHaveElement('.UserSelectionModal-listItem--selected');
  });

  it('still lets everyone else be selected', async () => {
    const manager = await open({ unavailable: (user: User) => (user === bob() ? 'Not available' : null) });

    expect(manager).not.toHaveElement('.UserSelectionModal-listItem--unavailable[data-id="2"]');

    manager.click('.UserSearchResult[data-id="2"]');
    manager.redraw();

    expect(manager).toHaveElement('.UserSelectionModal-listItem--selected[data-id="2"]');
  });

  it('cannot be submitted with someone unavailable already selected', async () => {
    const manager = await open({ selected: [bob()], unavailable: (user: User) => (user === bob() ? 'Not available' : null) });

    expect(manager).toHaveElement('.UserSelectionModal-form-submit button[disabled]');
  });
});
