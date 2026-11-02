import { jest } from '@jest/globals';
import mq from 'mithril-query';
import { app } from '../../../../src/admin';
import ResourceTable from '../../../../src/admin/components/resource/ResourceTable';
import ResourceFormModal from '../../../../src/admin/components/resource/ResourceFormModal';
import BulkResultModal from '../../../../src/admin/components/resource/BulkResultModal';
import ResourceActionModal from '../../../../src/admin/components/resource/ResourceActionModal';
import RequestError from '../../../../src/common/utils/RequestError';
import Model from '../../../../src/common/Model';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
import ResourceDefinition from '../../../../src/common/resource/ResourceDefinition';
import ResourceListState from '../../../../src/common/resource/ResourceListState';
import ColumnPreferences from '../../../../src/common/resource/ColumnPreferences';
import ViewPreferences from '../../../../src/common/resource/ViewPreferences';
import { deleteAction, executeAction } from '../../../../src/common/resource/actions';
import { bootAdmin, descriptor, fd, modalClass, schemaDocument } from '../../../unit/common/resource/fixtures';

class Thing extends Model {}

/** Markup in record values and server-sent strings; rendered, it must stay text. */
const MARKUP = '<img src=x onerror="window.__ran=1"><script>window.__ran=1</script>';

const things = descriptor(
  [
    fd('name', 'string', {
      sortable: true,
      label: `Name ${MARKUP}`,
      constraints: { maxLength: 3, messages: { maxLength: `msg ${MARKUP}` } },
      ui: { help: `help ${MARKUP}`, placeholder: `ph ${MARKUP}` } as any,
    }),
    fd('status', 'string', { enum: ['draft', 'live'], enumLabels: { draft: `Draft ${MARKUP}`, live: 'Live' } } as any),
    fd('notes', 'string'),
  ],
  {
    sorts: ['name'],
    filters: [
      { key: 'status', kind: 'enum', enum: ['draft', 'live'], label: `Status ${MARKUP}`, enumLabels: { draft: `D ${MARKUP}` } } as any,
      { key: 'flag', kind: 'boolean', negatable: true } as any,
    ],
    rowAbilities: { edit: 'canEdit', delete: 'canDelete' },
  }
);

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

function thing(id: string, attributes: Record<string, unknown> = {}): Thing {
  return app.store.pushObject<Thing>({ type: 'things', id, attributes: { name: `Thing ${id}`, ...attributes } } as any)!;
}

function modalAttrs(extra: Record<string, unknown>) {
  return { state: {}, animateShow: (cb: () => void) => cb(), animateHide: jest.fn(), ...extra };
}

/** No element or event handler came out of the markup, and none of it ran. */
function expectTextOnly(out: any) {
  const root = out.rootEl as Element;

  expect(root.querySelectorAll('img[src="x"], script').length).toBe(0);
  root.querySelectorAll('*').forEach((node) => {
    [...node.attributes].forEach((attr) => expect(attr.name.toLowerCase().startsWith('on') && attr.value.includes('__ran')).toBe(false));
  });
  expect((window as any).__ran).toBeUndefined();
}

describe('resource UI with markup in data and descriptor strings', () => {
  let find: jest.Mock<any>;

  beforeAll(() => {
    bootAdmin();
    app.store.models.things = Thing;
  });

  beforeEach(async () => {
    clearDescriptorCache();
    window.history.replaceState(null, '', '/admin#/things');
    app.request = jest.fn(() => Promise.resolve(schemaDocument(things))) as any;
    await new ResourceDefinition('things').load();
    find = jest.fn(() =>
      Promise.resolve(
        Object.assign(
          [
            thing('1', { name: MARKUP, status: 'draft', canEdit: true, canDelete: true }),
            thing('2', { name: 'ok', canEdit: true, canDelete: false }),
          ],
          {
            payload: { data: [], meta: {}, links: {} },
          }
        )
      )
    );
    jest.spyOn(app.store, 'find').mockImplementation(find as any);
    app.modal.show = jest.fn() as any;
    ColumnPreferences.clear();
    ViewPreferences.clear();
    window.localStorage.clear();
    delete (window as any).__ran;
  });

  afterEach(() => jest.restoreAllMocks());

  test('the table renders record values, labels, enum labels, URL filter values and the search as text', async () => {
    window.history.replaceState(null, '', `/admin#/things?q=${encodeURIComponent(MARKUP)}&filter[status]=${encodeURIComponent(MARKUP)}`);
    const out = mq(ResourceTable, { definition: new ResourceDefinition<Thing>('things') });
    await flush();
    await flush();
    out.redraw();

    expect((out.rootEl as Element).textContent).toContain('<img src=x onerror=');
    expectTextOnly(out);
  });

  test('BulkResultModal renders error details and record labels as text', () => {
    const error = new RequestError(422, '', {}, {} as any);
    error.response = { errors: [{ status: '422', detail: MARKUP }] } as any;
    const out = mq(
      BulkResultModal,
      modalAttrs({ summary: { ok: [], failed: [{ model: thing('9', { name: MARKUP }), error }], skipped: [thing('8', { name: MARKUP })] } })
    );

    expect((out.rootEl as Element).textContent).toContain('<img src=x onerror=');
    expectTextOnly(out);
  });

  test('the form renders labels, help, placeholders, custom messages and server errors as text', async () => {
    const oninit = jest.spyOn(ResourceFormModal.prototype as any, 'oninit');
    const out = mq(ResourceFormModal, modalAttrs({ definition: new ResourceDefinition<Thing>('things'), model: null }));
    const modal = oninit.mock.contexts[0] as any;
    oninit.mockRestore();
    await flush();
    await flush();

    modal.formState.stream('name')('too long');
    modal.formState.validate();
    modal.formState.errors.notes = `server ${MARKUP}`;
    out.redraw();

    expectTextOnly(out);
  });

  test('ResourceActionModal renders record labels in its confirmation as text', () => {
    const out = mq(
      ResourceActionModal,
      modalAttrs({
        definition: new ResourceDefinition<Thing>('things'),
        action: deleteAction<Thing>('canDelete', 'bulk'),
        models: [thing('7', { name: MARKUP, canDelete: true })],
        context: {},
        ondone: () => {},
      })
    );

    expectTextOnly(out);
  });

  test('the translator does not parse markup inside interpolated values', () => {
    app.translator.addTranslations({ 'resource-ui.test': 'Hello <b>{name}</b>' });
    const out = mq({ view: () => <div>{app.translator.trans('resource-ui.test', { name: MARKUP })}</div> } as any);

    expect(out).toHaveElement('b');
    expect(out).not.toHaveElement('img');
    expect(out).not.toHaveElement('script');
  });

  test('a bulk delete asks for confirmation first; a row without canDelete cannot be selected', async () => {
    class DeleteDefinition extends ResourceDefinition<Thing> {
      resourceActions() {
        const items = super.resourceActions();
        items.setContent('delete', { ...items.get('delete'), scope: 'both' });
        return items;
      }
    }

    const out = mq(ResourceTable, { definition: new DeleteDefinition('things') });
    await flush();
    await flush();
    out.redraw();
    const request = jest.fn(() => Promise.resolve());
    app.request = request as any;

    out.first('tr[data-id="1"] input.ResourceTable-select').checked = true;
    out.trigger('tr[data-id="1"] input.ResourceTable-select', 'change');
    expect(out).toHaveElement('tr[data-id="2"] input.ResourceTable-select[disabled]');
    out.click('.ResourceTable-batchBar .ResourceTable-bulkDelete');
    await flush();

    expect(await modalClass((app.modal.show as jest.Mock<any>).mock.calls.at(-1)![0])).toBe(ResourceActionModal);
    expect(request).not.toHaveBeenCalled();
  });

  test('a selection holding a row without canDelete, or an id not on the page, deletes nothing', async () => {
    const state = new ResourceListState<Thing>(new ResourceDefinition<Thing>('things'), { syncUrl: false });
    await state.load();
    thing('99', { canDelete: true });
    state.selection.ids.add('2'); // canDelete: false
    state.selection.ids.add('99'); // not on the page

    const ran: string[] = [];
    const runner = { run: async (models: Thing[]) => (models.forEach((model) => ran.push(model.id()!)), { ok: [], failed: [] }) };
    const action = deleteAction<Thing>('canDelete', 'bulk');
    const selected = state.selectedModels();

    await executeAction(
      action,
      selected.filter((model) => action.applies!(model)),
      [],
      {},
      { list: state, runner, showResult: () => {} } as any
    );

    expect(selected.map((model) => model.id())).toEqual(['2']);
    expect(ran).toEqual([]);
  });
});
