import app from '../../app';
import Select from '../../components/Select';
import type Model from '../../Model';
import ItemList from '../../utils/ItemList';
import extractText from '../../utils/extractText';
import type Stream from '../../utils/Stream';
import type Mithril from 'mithril';
import type { InputContext, InputRenderer } from '../types';
import RelationInput from './RelationInput';

/**
 * Filter types typed into rather than picked; the filter bar commits them debounced.
 *
 * @experimental
 */
export const TEXT_FILTER_TYPES = ['text', 'search', 'number', 'email', 'url'];

const any = () => app.translator.trans('core.lib.resource.filter.any', {}, true);

const labelText = (ctx: InputContext): string | undefined => (ctx.label ? extractText(ctx.label) : undefined);

/**
 * The id a form renderer gives its control, so `formGroup()`'s label points at it.
 *
 * @experimental
 */
export function inputId(ctx: InputContext): string {
  return `ResourceForm-input-${ctx.name}`;
}

/**
 * Wrap a control the way `FormGroup` does: label, help, control. For form input renderers; pass
 * `id` when the control carries `inputId(ctx)`, so clicking the label focuses it.
 *
 * @experimental
 */
export function formGroup(ctx: InputContext, control: Mithril.Children, id?: string): Mithril.Children {
  return (
    <div className="Form-group">
      {ctx.label && <label for={id}>{ctx.label}</label>}
      {ctx.help && <div className="helpText">{ctx.help}</div>}
      {control}
    </div>
  );
}

/**
 * An ordered `[value, label]` list; an options object would put integer-like keys first.
 */
export type Choices = [string, string][];

/**
 * A native select over ordered choices, `ctx.stream` holding the value (`''` for none).
 *
 * @experimental
 */
export function orderedSelect(choices: Choices, ctx: InputContext, attrs: Record<string, unknown> = {}): Mithril.Children {
  const value = ctx.stream() ?? '';

  return (
    <span className="Select">
      <select
        className="Select-input FormControl"
        name={ctx.name}
        value={value}
        onchange={(e: Event) => ctx.stream((e.target as HTMLSelectElement).value)}
        {...attrs}
      >
        {choices.map(([optionValue, label]) => (
          <option value={optionValue} selected={optionValue === value} disabled={optionValue === '' && attrs['data-placeholder'] === true}>
            {label}
          </option>
        ))}
      </select>
    </span>
  );
}

/**
 * The form input types beyond core's `FormGroup` ones.
 *
 * - `relation`: `{ related, multiple?, display?, nullable?, search? }`, the stream holds model(s).
 * - `enum`: `{ choices, nullable? }` in their given order. Without `nullable`, an empty value shows a
 *   disabled blank choice until one is picked.
 *
 * @experimental
 */
export function defaultFormInputs(): ItemList<InputRenderer> {
  const inputs = new ItemList<InputRenderer>();

  inputs.add('enum', (spec, ctx) => {
    const choices = spec.choices as Choices;
    const empty = ctx.stream() === null || ctx.stream() === undefined || ctx.stream() === '';
    const placeholder = !spec.nullable && empty;
    const id = inputId(ctx);

    return formGroup(
      ctx,
      orderedSelect(spec.nullable || placeholder ? [['', spec.placeholder ?? ''], ...choices] : choices, ctx, {
        id,
        required: ctx.required,
        'data-placeholder': placeholder,
      }),
      id
    );
  });

  inputs.add('relation', (spec, ctx) =>
    formGroup(
      ctx,
      <RelationInput
        type={spec.related}
        multiple={!!spec.multiple}
        display={spec.display ?? 'name'}
        stream={ctx.stream as Stream<Model | Model[] | null>}
        name={ctx.name}
        nullable={spec.nullable}
        disabled={spec.disabled}
        placeholder={spec.placeholder}
        search={spec.search}
      />
    )
  );

  return inputs;
}

/** `type:id` keys of filter values being fetched, so each is requested once. */
const fetching = new Set<string>();

/**
 * The model behind a filter's id. A value from the URL (deep link, reload) may not be in the
 * store yet: fetch it once, so the picker can show the active filter.
 */
function relatedModel(type: string, id: string): Model | null {
  const model = app.store.getById(type, id);

  if (model || fetching.has(`${type}:${id}`)) return model ?? null;

  fetching.add(`${type}:${id}`);
  app.store.find(type, id).then(
    () => m.redraw(),
    () => {} // An unknown or hidden id stays "Any"; the list still filters by it.
  );

  return null;
}

/**
 * The filter bar's fallback for types without a renderer: a bare input of that type.
 *
 * @experimental
 */
export const bareFilterInput: InputRenderer = (spec, ctx) => (
  <input
    className="FormControl"
    name={ctx.name}
    type={spec.type}
    value={ctx.stream()}
    aria-label={labelText(ctx)}
    oninput={(e: InputEvent) => ctx.stream((e.target as HTMLInputElement).value)}
  />
);

/**
 * The filter bar's input types: bare controls labelled by `aria-label`, over a string stream.
 *
 * - `enum`: `{ choices }` in their given order, after "any".
 * - `select`: `{ options }`.
 * - `text`, `search`, `number`, `email`, `url`: a typed input, the label as placeholder.
 * - `tri-state`: any / yes / no as `''` / `'1'` / `'0'` (core's `asBool` convention).
 * - `relation`: `{ related, display?, placeholder?, search? }`, a single picker; the stream holds the id.
 *
 * @experimental
 */
export function defaultFilterInputs(): ItemList<InputRenderer> {
  const inputs = new ItemList<InputRenderer>();

  inputs.add('enum', (spec, ctx) => orderedSelect([['', any()], ...(spec.choices as Choices)], ctx, { 'aria-label': labelText(ctx) }));

  inputs.add('select', (spec, ctx) => (
    <Select
      className="FormControl"
      name={ctx.name}
      options={spec.options}
      value={ctx.stream()}
      onchange={(v: string) => ctx.stream(v)}
      aria-label={labelText(ctx)}
    />
  ));

  TEXT_FILTER_TYPES.forEach((type) =>
    inputs.add(type, (_spec, ctx) => (
      <input
        className="FormControl"
        name={ctx.name}
        type={type}
        value={ctx.stream()}
        placeholder={labelText(ctx)}
        aria-label={labelText(ctx)}
        oninput={(e: InputEvent) => ctx.stream((e.target as HTMLInputElement).value)}
      />
    ))
  );

  inputs.add('tri-state', (_spec, ctx) =>
    orderedSelect(
      [
        ['', any()],
        ['1', app.translator.trans('core.lib.resource.filter.yes', {}, true)],
        ['0', app.translator.trans('core.lib.resource.filter.no', {}, true)],
      ],
      ctx,
      { 'aria-label': labelText(ctx) }
    )
  );

  inputs.add('relation', (spec, ctx) => {
    const related = spec.related as string;

    // Bridge the id-valued filter stream to the picker's model-valued stream.
    const bridge = (...args: [Model | Model[] | null] | []) => {
      if (args.length) {
        const value = args[0];
        const model = Array.isArray(value) ? (value[0] ?? null) : value;

        return ctx.stream(model?.id() ?? '');
      }

      return ctx.stream() ? relatedModel(related, ctx.stream()) : null;
    };

    return (
      <RelationInput
        type={related}
        multiple={false}
        nullable={true}
        placeholder={spec.placeholder ?? any()}
        display={spec.display ?? 'name'}
        stream={bridge as unknown as Stream<Model | Model[] | null>}
        name={ctx.name}
        search={spec.search}
      />
    );
  });

  return inputs;
}
