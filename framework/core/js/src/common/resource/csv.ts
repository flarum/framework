import app from '../app';
import type Mithril from 'mithril';
import type Model from '../Model';
import type { ApiQueryParamsPlural } from '../Store';
import extractText from '../utils/extractText';
import type ResourceListState from './ResourceListState';

/**
 * What `exportCsv` needs from a table column. `ResourceTable`'s `ColumnData` satisfies it.
 */
export interface ExportColumn<M extends Model = Model> {
  /** The header cell; reduced to its text. */
  name: Mithril.Children;
  /** The cell value. Without it, the raw attribute when the column key is a descriptor field, else the column is skipped. */
  csv?: (model: M) => string | number | null;
  /** Hidden columns are not exported. */
  hidden?: boolean;
}

export interface ExportCsvOptions<M extends Model = Model> {
  /** The table's columns in display order, keyed as in its `ItemList`. */
  columns: [string, ExportColumn<M>][];
  /** Stop after this many rows. Default 5000. */
  maxRows?: number;
  /** Called after each page with the rows so far and the expected total (capped at `maxRows`), if known. */
  onProgress?: (rows: number, total: number | null) => void;
  /** Aborting stops after the page being loaded; the promise rejects with an `AbortError` and nothing is downloaded. */
  signal?: AbortSignal;
}

export interface ExportCsvResult {
  rows: number;
  /** More rows matched than `maxRows`. */
  truncated: boolean;
}

/** A leading character that makes a spreadsheet read the cell as a formula. */
const FORMULA_START = /^[=+\-@\t\r]/;
const NEEDS_QUOTES = /[",\r\n]/;

/** An object cell as JSON; a vnode (rendered content, never exported) or an unserializable value is empty. */
function jsonCell(value: object): string {
  if ('tag' in value) return '';

  try {
    return JSON.stringify(value) ?? '';
  } catch {
    return '';
  }
}

/**
 * One CSV cell: RFC 4180 quoting, plus a formula-injection guard that prefixes `'` to any value
 * starting with `= + - @`, a tab or a carriage return.
 *
 * The guard is uniform, so it also changes harmless values: `-5` is exported as `'-5`, and a
 * spreadsheet reads it as text. Telling a negative number from `-2+3+cmd|...` is exactly the kind
 * of judgement such guards get wrong, so we don't try. A vnode-like object (with a `tag`) or a
 * value `JSON.stringify` rejects becomes an empty cell.
 */
export function csvCell(value: unknown): string {
  if (value === null || value === undefined) return '';

  let text = value instanceof Date ? value.toISOString() : typeof value === 'object' ? jsonCell(value) : String(value);

  if (FORMULA_START.test(text)) text = `'${text}`;

  return NEEDS_QUOTES.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
}

/**
 * A CSV document: the header, then one record per row, each line ending with CRLF.
 */
export function toCsv(rows: unknown[][], header: string[]): string {
  return [header, ...rows].map((row) => row.map(csvCell).join(',') + '\r\n').join('');
}

function today(): string {
  const now = new Date();

  return [now.getFullYear(), now.getMonth() + 1, now.getDate()].map((n) => String(n).padStart(2, '0')).join('-');
}

function abortError(signal: AbortSignal): unknown {
  return signal.reason instanceof Error && signal.reason.name === 'AbortError'
    ? signal.reason
    : new DOMException('The export was aborted.', 'AbortError');
}

/**
 * Save a file through a temporary Blob link.
 */
function download(filename: string, content: string, type: string): void {
  const url = URL.createObjectURL(new Blob([content], { type }));
  const link = document.createElement('a');

  link.href = url;
  link.download = filename;
  link.style.display = 'none';
  document.body.appendChild(link);
  link.click();
  link.remove();

  setTimeout(() => URL.revokeObjectURL(url), 0);
}

/**
 * Export a list's records (its current filter and sort, every page) as `{type}-{YYYY-MM-DD}.csv`.
 *
 * Pages are requested at the descriptor's `pagination.maxLimit` through the regular list endpoint,
 * so the file holds only what the actor could already see. A cell is the column's `csv(model)`,
 * else the raw attribute when the column key is a descriptor field (relationships excluded), else
 * the column is left out; rendered cell content is never stringified. Hidden columns are left out.
 * The file starts with a UTF-8 BOM so spreadsheet apps detect the encoding.
 */
export async function exportCsv<M extends Model>(state: ResourceListState<M>, options: ExportCsvOptions<M>): Promise<ExportCsvResult> {
  const { maxRows = 5000, onProgress, signal } = options;
  const descriptor = state.definition.descriptor;
  const attributes = new Set(
    (descriptor?.fields ?? []).filter((field) => field.kind !== 'toOne' && field.kind !== 'toMany').map((field) => field.name)
  );

  const columns = options.columns
    .filter(([key, column]) => !column.hidden && (column.csv || attributes.has(key)))
    .map(([key, column]) => ({
      header: extractText(column.name),
      value: column.csv ?? ((model: M) => model.attribute(key) as unknown),
    }));

  if (signal?.aborted) throw abortError(signal);

  const limit = descriptor?.pagination?.maxLimit ?? state.pageSize ?? 20;
  const { include, ...rest } = state.listParams();
  const params: ApiQueryParamsPlural = { ...rest };

  if (include !== undefined) params.include = Array.isArray(include) ? include.join(',') : include;
  const rows: unknown[][] = [];
  let truncated = false;
  let offset = 0;
  let total: number | null = null;

  while (true) {
    const results = await app.store.find<M[]>(state.type, { ...params, page: { offset, limit } });
    const payload = results.payload;
    const pageTotal = payload?.meta?.page?.total;

    if (typeof pageTotal === 'number') total = Math.min(pageTotal, maxRows);

    for (const model of results) {
      if (rows.length >= maxRows) {
        truncated = true;
        break;
      }

      rows.push(columns.map((column) => column.value(model)));
    }

    offset += limit;

    // `links.next` can be missing on a post-filtered page that came back short, so the total and
    // a full page count as "more" too.
    const more = results.length > 0 && (!!payload?.links?.next || (total !== null ? offset < (pageTotal as number) : results.length === limit));

    if (more && rows.length >= maxRows) truncated = true;

    onProgress?.(rows.length, total);

    if (signal?.aborted) throw abortError(signal);

    if (!more || truncated) break;
  }

  download(
    `${state.type}-${today()}.csv`,
    '﻿' +
      toCsv(
        rows,
        columns.map((column) => column.header)
      ),
    'text/csv;charset=utf-8'
  );

  return { rows: rows.length, truncated };
}
