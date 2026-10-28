import { jest } from '@jest/globals';
import { app } from '../../../../src/admin';
import Model from '../../../../src/common/Model';
import ResourceDefinition from '../../../../src/common/resource/ResourceDefinition';
import ResourceListState from '../../../../src/common/resource/ResourceListState';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
import { csvCell, exportCsv, toCsv, type ExportColumn } from '../../../../src/common/resource/csv';
import { bootAdmin, descriptor, fd, schemaDocument } from './fixtures';

class Thing extends Model {}

describe('toCsv', () => {
  test('quotes per RFC 4180, ends lines with CRLF and guards every formula-like cell', () => {
    const csv = toCsv([['a,b', 'he said "x"', 'line\nbreak', '=1+1', '@x', '-5']], ['A', 'B', 'C', 'D', 'E', 'F']);

    expect(csv).toBe('A,B,C,D,E,F\r\n' + `"a,b","he said ""x""","line\nbreak",'=1+1,'@x,'-5\r\n`);
  });

  test('guards = + - @ tab and CR, numbers included', () => {
    expect(['=HYPERLINK("x")', '+1', '-x', '@me', '\tx', '\rx', -5, 5, 'a=b'].map(csvCell)).toEqual([
      `"'=HYPERLINK(""x"")"`,
      "'+1",
      "'-x",
      "'@me",
      "'\tx",
      `"'\rx"`,
      "'-5",
      '5',
      'a=b',
    ]);
  });

  test('null and undefined are empty cells; the header is escaped too', () => {
    expect(toCsv([[null, undefined, 0, false]], ['a,b', '=x', 'c', 'd'])).toBe(`"a,b",'=x,c,d\r\n,,0,false\r\n`);
  });
});

describe('csvCell robustness', () => {
  test('a vnode-like object becomes an empty cell, never its JSON', () => {
    expect(csvCell({ tag: 'a', attrs: { href: '/u/x' }, children: ['x'] })).toBe('');
    expect(csvCell({ tag: 'div', attrs: {}, children: [] })).toBe('');
  });

  test('a value JSON.stringify cannot handle becomes an empty cell', () => {
    const circular: Record<string, unknown> = {};
    circular.self = circular;

    expect(csvCell(circular)).toBe('');
    expect(csvCell({ n: BigInt(1) })).toBe('');
  });

  test('plain objects and arrays are still exported as JSON', () => {
    expect(csvCell({ a: 1 })).toBe('"{""a"":1}"');
    expect(csvCell(['x'])).toBe('"[""x""]"');
  });
});

describe('exportCsv', () => {
  const things = descriptor([fd('name', 'string', { sortable: true }), fd('price', 'number')], {
    sorts: ['name'],
    filters: [{ key: 'status', kind: 'enum', enum: ['draft', 'live'] }],
    pagination: { defaultLimit: 20, maxLimit: 2 },
  });

  const columns: [string, ExportColumn<Thing>][] = [
    ['name', { name: 'Name' }],
    ['label', { name: 'Label', csv: (m) => `#${m.id()}` }],
    ['avatar', { name: 'Avatar' }],
    ['price', { name: 'Price', hidden: true }],
  ];

  let find: jest.Mock<any>;
  let blobs: Blob[];
  let downloads: string[];

  function thing(id: string): Thing {
    return app.store.pushObject<Thing>({ type: 'things', id, attributes: { name: `=Thing ${id}`, price: Number(id) } } as any)!;
  }

  function page(models: Model[], next: boolean, total?: number) {
    return Object.assign([...models], {
      payload: { data: [], meta: total === undefined ? {} : { page: { total } }, links: next ? { next: 'x' } : {} },
    });
  }

  async function state(): Promise<ResourceListState<Thing>> {
    window.history.replaceState(null, '', '/admin#/things?sort=-name&filter%5Bstatus%5D=live&q=th');
    const list = new ResourceListState<Thing>(new ResourceDefinition<Thing>('things'));
    await list.load();
    find.mockClear();

    return list;
  }

  async function text(blob: Blob): Promise<Uint8Array> {
    const buffer = await new Promise<ArrayBuffer>((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(reader.result as ArrayBuffer);
      reader.onerror = () => reject(reader.error);
      reader.readAsArrayBuffer(blob);
    });

    return new Uint8Array(buffer);
  }

  beforeAll(() => {
    bootAdmin();
    app.store.models.things = Thing;
  });

  beforeEach(async () => {
    clearDescriptorCache();
    app.request = jest.fn(() => Promise.resolve(schemaDocument(things))) as any;
    await new ResourceDefinition('things').load();

    find = jest.fn((_type: string, params: any) => {
      const offset = params.page?.offset ?? 0;
      return Promise.resolve(offset === 0 ? page([thing('1'), thing('2')], true, 3) : page([thing('3')], false, 3));
    });
    jest.spyOn(app.store, 'find').mockImplementation(find as any);

    blobs = [];
    downloads = [];
    (URL as any).createObjectURL = jest.fn((blob: Blob) => {
      blobs.push(blob);
      return 'blob:x';
    });
    (URL as any).revokeObjectURL = jest.fn();
    jest.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(function (this: HTMLAnchorElement) {
      downloads.push(this.download);
    });
  });

  afterEach(() => jest.restoreAllMocks());

  test('pages at maxLimit with the active filter and sort, and downloads a BOM-prefixed file', async () => {
    const list = await state();
    const progress = jest.fn();

    const result = await exportCsv(list, { columns, onProgress: progress });

    expect(result).toEqual({ rows: 3, truncated: false });
    expect(find).toHaveBeenCalledTimes(2);
    expect(find.mock.calls[0]).toEqual(['things', { sort: '-name', filter: { q: 'th', status: 'live' }, page: { offset: 0, limit: 2 } }]);
    expect(find.mock.calls[1]).toEqual(['things', { sort: '-name', filter: { q: 'th', status: 'live' }, page: { offset: 2, limit: 2 } }]);
    expect(progress).toHaveBeenLastCalledWith(3, 3);

    const now = new Date();
    const date = [now.getFullYear(), now.getMonth() + 1, now.getDate()].map((n) => String(n).padStart(2, '0')).join('-');
    expect(downloads).toEqual([`things-${date}.csv`]);

    const bytes = await text(blobs[0]);
    expect([...bytes.slice(0, 3)]).toEqual([0xef, 0xbb, 0xbf]);
    expect(new TextDecoder('utf-8', { ignoreBOM: true }).decode(bytes)).toBe("﻿Name,Label\r\n'=Thing 1,#1\r\n'=Thing 2,#2\r\n'=Thing 3,#3\r\n");
  });

  test('stops at maxRows and reports truncation', async () => {
    const list = await state();

    const result = await exportCsv(list, { columns, maxRows: 1 });

    expect(result).toEqual({ rows: 1, truncated: true });
    expect(find).toHaveBeenCalledTimes(1);
    expect(new TextDecoder().decode(await text(blobs[0]))).toBe("Name,Label\r\n'=Thing 1,#1\r\n");
  });

  test('an abort stops after the current page and downloads nothing', async () => {
    const list = await state();
    const controller = new AbortController();

    const promise = exportCsv(list, { columns, signal: controller.signal });
    controller.abort();

    await expect(promise).rejects.toMatchObject({ name: 'AbortError' });
    expect(find).toHaveBeenCalledTimes(1);
    expect(downloads).toEqual([]);
  });
});
