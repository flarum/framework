import { jest } from '@jest/globals';
import { app } from '../../../../src/admin';
import Model from '../../../../src/common/Model';
import ItemList from '../../../../src/common/utils/ItemList';
import ResourceDefinition from '../../../../src/common/resource/ResourceDefinition';
import {
  actionConfirmation,
  actionLabel,
  executeAction,
  isActionRunning,
  isBulkAction,
  isRowAction,
  needsConfirmation,
  partitionByAction,
  type ResourceAction,
} from '../../../../src/common/resource/actions';
import { ConcurrentBulkRunner, type BulkActionContext } from '../../../../src/common/resource/BulkRunner';
import { bootAdmin, descriptor, fd } from './fixtures';

class Thing extends Model {}

function thing(id: string, attributes: Record<string, unknown> = {}): Thing {
  return app.store.pushObject<Thing>({ type: 'things', id, attributes } as any)!;
}

function definition(extra: Record<string, unknown> = {}): ResourceDefinition<Thing> {
  const d = new ResourceDefinition<Thing>('things');
  d.descriptor = descriptor([fd('name', 'string', { writable: { create: true, update: true } })], extra);

  return d;
}

describe('resource actions', () => {
  beforeAll(() => {
    bootAdmin();
    app.store.models.things = Thing;
  });

  afterEach(() => jest.restoreAllMocks());

  test('rowAbilities give a row-only edit and a row-only destructive delete', () => {
    const actions = definition({ rowAbilities: { edit: 'mayEdit', delete: 'mayDelete' } }).resourceActions();
    const edit = actions.get('edit');
    const remove = actions.get('delete');

    expect(isRowAction(edit) && !isBulkAction(edit)).toBe(true);
    expect(isRowAction(remove) && !isBulkAction(remove)).toBe(true);
    expect(remove.destructive).toBe(true);
    expect(needsConfirmation(remove)).toBe(true);

    const yes = thing('1', { mayEdit: true, mayDelete: true });
    const no = thing('2', { canEdit: true, canDelete: true });

    expect([edit.applies!(yes), remove.applies!(yes)]).toEqual([true, true]);
    // The named attributes decide, not canEdit/canDelete.
    expect([edit.applies!(no), remove.applies!(no)]).toEqual([false, false]);
  });

  test('an ability the descriptor leaves out has no action', () => {
    const actions = definition({ rowAbilities: { delete: 'canDelete' } }).resourceActions();

    expect(actions.has('edit')).toBe(false);
    expect(actions.has('delete')).toBe(true);
    expect(definition({ rowAbilities: {} }).resourceActions().isEmpty()).toBe(true);
  });

  test('without rowAbilities there is no edit or delete action', () => {
    expect(definition().resourceActions().isEmpty()).toBe(true);
  });

  test('the edit and delete open the context’s edit and delete for a row', () => {
    const actions = definition({ rowAbilities: { edit: 'canEdit', delete: 'canDelete' } }).resourceActions();
    const context = { edit: jest.fn(), delete: jest.fn() } as any;
    const model = thing('4');

    actions.get('edit').open!(model, context);
    actions.get('delete').open!(model, context);

    expect(context.edit).toHaveBeenCalledWith(model);
    expect(context.delete).toHaveBeenCalledWith(model);
  });

  test('label(): the descriptor label by count, else the humanized type', () => {
    expect(definition({ label: { one: 'Widget', other: 'Widgets' } }).label(1)).toBe('Widget');
    expect(definition({ label: { one: 'Widget', other: 'Widgets' } }).label(0)).toBe('Widgets');
    expect(definition({ label: { one: 'Widget', other: 'Widgets' } }).label(3)).toBe('Widgets');
    expect(new ResourceDefinition('access-tokens').label(2)).toBe('Access tokens');
  });

  test('labels and confirmations take the count; applies() splits applicable from skipped', () => {
    const action: ResourceAction<Thing> = {
      key: 'archive',
      label: (count) => `Archive ${count}`,
      confirm: (count) => `Archive ${count} things?`,
      applies: (model) => model.attribute('live') === true,
      run: async () => {},
    };

    expect(actionLabel(action, 3)).toBe('Archive 3');
    expect(actionConfirmation(action, 2)).toBe('Archive 2 things?');
    expect(needsConfirmation({ key: 'x', label: 'X', run: async () => {} })).toBe(false);

    const live = thing('5', { live: true });
    const draft = thing('6', { live: false });

    expect(partitionByAction(action, [live, draft])).toEqual({ applicable: [live], skipped: [draft] });
  });

  test('executeAction runs with the values, shows the summary with the action and reloads the page', async () => {
    const run = jest.fn((_model: Thing, _values: Record<string, unknown>) => Promise.resolve());
    const action: ResourceAction<Thing> = { key: 'tag', label: 'Tag', fields: () => new ItemList(), run };
    const list = { gotoAfterRemoval: jest.fn(() => Promise.resolve()) } as any;
    const showResult = jest.fn();
    const context: BulkActionContext<Thing> = { list, runner: new ConcurrentBulkRunner(), showResult, runAction: jest.fn() as any };
    const a = thing('7');
    const b = thing('8');

    const summary = await executeAction(action, [a], [b], { tag: 'red' }, context);

    expect(run).toHaveBeenCalledWith(a, { tag: 'red' });
    expect(summary).toEqual({ ok: [a], failed: [], skipped: [b] });
    expect(showResult).toHaveBeenCalledWith(summary, action);
    // Nothing was removed, so the same page reloads.
    expect(list.gotoAfterRemoval).toHaveBeenCalledWith(0);
  });

  test('a second run for a record already running returns the run in flight; runs on other records are tracked apart', async () => {
    const pending: (() => void)[] = [];
    const run = jest.fn(() => new Promise<void>((resolve) => pending.push(resolve)));
    const action: ResourceAction<Thing> = { key: 'ping', label: 'Ping', run };
    const list = { gotoAfterRemoval: jest.fn(() => Promise.resolve()) } as any;
    const context: BulkActionContext<Thing> = { list, runner: new ConcurrentBulkRunner(), showResult: jest.fn(), runAction: jest.fn() as any };
    const a = thing('21');
    const b = thing('22');

    const first = executeAction(action, [a], [], {}, context);
    const again = executeAction(action, [a, b], [], {}, context);
    const other = executeAction(action, [b], [], {}, context);

    expect(again).toBe(first);
    expect(run).toHaveBeenCalledTimes(2);
    expect([isActionRunning(list, 'ping', a), isActionRunning(list, 'ping', b), isActionRunning(list, 'other', a)]).toEqual([true, true, false]);

    pending[0]();
    await first;

    // The first run ending does not clear the one still running.
    expect([isActionRunning(list, 'ping', a), isActionRunning(list, 'ping', b), isActionRunning(list, 'ping')]).toEqual([false, true, true]);

    pending[1]();
    await other;

    expect(isActionRunning(list)).toBe(false);
  });
});
