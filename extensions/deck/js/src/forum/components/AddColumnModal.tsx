import app from 'flarum/forum/app';
import FormModal, { type IFormModalAttrs } from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import Form from 'flarum/common/components/Form';
import Select from 'flarum/common/components/Select';
import GambitsAutocompleteDropdown from 'flarum/common/components/GambitsAutocompleteDropdown';
import DeckSearchField from './DeckSearchField';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import deckColumnTypes from '../columns/deckColumnTypes';
import { makeColumn } from '../utils/deckLayout';
import type DeckState from '../states/DeckState';
import type { DeckColumnConfig, DeckColumnField, DeckColumnType } from '../columns/DeckColumnType';

export interface IAddColumnModalAttrs extends IFormModalAttrs {
  deck: DeckState;
  onadd?: (config: DeckColumnConfig) => void;
}

export default class AddColumnModal<CustomAttrs extends IAddColumnModalAttrs = IAddColumnModalAttrs> extends FormModal<CustomAttrs> {
  protected type: string | null = null;
  protected values: Record<string, string> = {};
  /** The chosen type's fields, built once when it's chosen. */
  protected typeFields: DeckColumnField[] = [];
  /** Params from search fields, by field key; null until something is picked. */
  protected picked: Record<string, DeckColumnConfig['params'] | null> = {};
  protected error: Mithril.Children = null;

  className() {
    return 'AddColumnModal Modal--small';
  }

  title() {
    return app.translator.trans('flarum-deck.forum.add_column.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <Form className="Form--centered">{this.type ? this.fields().toArray() : this.typeItems().toArray()}</Form>
      </div>
    );
  }

  typeItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    Object.keys(deckColumnTypes.toObject()).forEach((key) => {
      const type = deckColumnTypes.get(key);

      if (!type.isAvailable()) return;

      items.add(
        key,
        <Button className="Button Button--block AddColumnModal-type" icon={type.icon} onclick={() => this.choose(key, type)}>
          {type.label()}
        </Button>,
        deckColumnTypes.getPriority(key)
      );
    });

    return items;
  }

  fields(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const type = deckColumnTypes.get(this.type!);

    this.typeFields.forEach((field, i) => {
      items.add(
        field.key,
        <div className="Form-group">
          <label for={`AddColumnModal-${field.key}`}>{field.label}</label>
          {this.input(field)}
          {field.help && <p className="helpText">{field.help}</p>}
        </div>,
        100 - i
      );
    });

    if (this.error) {
      items.add('error', <p className="AddColumnModal-error">{this.error}</p>, 10);
    }

    items.add(
      'submit',
      <div className="Form-group Form-controls">
        <Button className="Button Button--primary Button--block" type="submit">
          {app.translator.trans('flarum-deck.forum.add_column.submit_button')}
        </Button>
        <Button className="Button Button--link" onclick={() => this.back()}>
          {app.translator.trans('flarum-deck.forum.add_column.back_button')}
        </Button>
      </div>,
      0
    );

    return items;
  }

  protected input(field: DeckColumnField): Mithril.Children {
    const id = `AddColumnModal-${field.key}`;
    const value = this.values[field.key] ?? '';
    const set = (value: string) => (this.values[field.key] = value);

    if (field.search) {
      return (
        <DeckSearchField
          id={id}
          search={field.search}
          placeholder={field.placeholder}
          onpick={(params: DeckColumnConfig['params'] | null) => (this.picked[field.key] = params)}
        />
      );
    }

    if (field.input) {
      return field.input({ id, value, onchange: set });
    }

    if (field.options) {
      return <Select id={id} options={field.options()} value={value} onchange={set} />;
    }

    const text = (
      <input
        id={id}
        className="FormControl"
        autocomplete="off"
        placeholder={field.placeholder}
        value={value}
        oninput={(e: InputEvent) => set((e.target as HTMLInputElement).value)}
      />
    );

    return field.gambits ? (
      <GambitsAutocompleteDropdown resource={field.gambits} query={value} onchange={set}>
        {text}
      </GambitsAutocompleteDropdown>
    ) : (
      text
    );
  }

  protected choose(key: string, type: DeckColumnType): void {
    this.type = key;
    this.values = {};
    this.picked = {};
    this.error = null;

    // A select starts on its first option, so that's the value until changed.
    this.typeFields = type.fields?.() ?? [];

    this.typeFields.forEach((field) => {
      if (field.options) this.values[field.key] = Object.keys(field.options())[0] ?? '';
    });

    if (!this.typeFields.length) this.add({});
  }

  protected back(): void {
    this.type = null;
    this.error = null;
  }

  onsubmit(e: SubmitEvent): void {
    e.preventDefault();

    const type = deckColumnTypes.get(this.type!);
    const params: DeckColumnConfig['params'] = {};

    for (const field of this.typeFields) {
      if (field.search) {
        const picked = this.picked[field.key];

        if (!picked) {
          this.error = app.translator.trans('flarum-deck.forum.add_column.pick_a_result_message', { field: field.label });
          return;
        }

        Object.assign(params, picked);
        continue;
      }

      const raw = (this.values[field.key] ?? '').trim();
      const value = field.parse ? field.parse(raw) : raw || null;

      if (value === null || value === '') {
        this.error = field.invalidText ?? app.translator.trans('flarum-deck.forum.add_column.invalid_value_message', { field: field.label });
        return;
      }

      params[field.key] = value;
    }

    this.add(params);
  }

  protected add(params: DeckColumnConfig['params']): void {
    const config = makeColumn(this.type!, params);

    this.attrs.deck.addColumn(config);
    this.attrs.onadd?.(config);
    this.hide();
  }
}
