import app from '../app';
import extractText from '../utils/extractText';
import type Mithril from 'mithril';
import { formatRange, parseRange } from './resolvers';
import type { InputContext, InputSpec } from './types';

/**
 * The filter bar's `range` input: `{ kind: 'date' | 'number' }`, two bare inputs "from" and "to"
 * over a string stream holding `from..to` (either end may be empty, dates as `YYYY-MM-DD`).
 */
export function rangeFilterInput(spec: InputSpec, ctx: InputContext<string>): Mithril.Children {
  const type = spec.kind === 'number' ? 'number' : 'date';
  const [from, to] = parseRange(ctx.stream() ?? '');
  const label = ctx.label ? extractText(ctx.label) : '';
  const end = (which: 'from' | 'to', value: string) => (
    <input
      className={`FormControl ResourceTable-range${which === 'from' ? 'From' : 'To'}`}
      name={`${ctx.name}[${which}]`}
      type={type}
      value={value}
      step={type === 'number' ? 'any' : undefined}
      aria-label={app.translator.trans(`core.lib.resource.filter.range_${which}_label`, { label }, true)}
      oninput={(e: InputEvent) => {
        const next = (e.target as HTMLInputElement).value;

        ctx.stream(which === 'from' ? formatRange(next, to) : formatRange(from, next));
      }}
    />
  );

  return (
    <span className="ResourceTable-range">
      {end('from', from)}
      <span className="ResourceTable-rangeSeparator" aria-hidden="true">
        –
      </span>
      {end('to', to)}
    </span>
  );
}

/**
 * How a range value reads on a filter's button and chip: "2024-01-01 – …".
 */
export function rangeLabel(value: string): string {
  const [from, to] = parseRange(value);
  const open = app.translator.trans('core.lib.resource.filter.range_open', {}, true);

  if (from === to) return from;

  return app.translator.trans('core.lib.resource.filter.range_value', { from: from || open, to: to || open }, true);
}
