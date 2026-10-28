import FormGroup from '../../components/FormGroup';
import ItemList from '../../utils/ItemList';
import type Mithril from 'mithril';
import type { InputContext, InputRenderer, InputSpec } from '../types';

/**
 * Turns input specs into vnodes: through the renderer registered for `spec.type`, else through
 * the fallback (`FormGroup` unless one is given). Every form and filter bar renders through one:
 * `definition.formInputs()` / `definition.filterInputs()` by default, or the factory passed to a
 * single view. Register a type to add or replace an input everywhere the factory is used.
 */
export default class InputFactory {
  /**
   * @param fallbackRenderer renders types without a registered renderer; default FormGroup.
   */
  constructor(
    readonly registry: ItemList<InputRenderer> = new ItemList<InputRenderer>(),
    protected fallbackRenderer?: InputRenderer
  ) {}

  /**
   * Add a renderer for `type`, or replace the registered one.
   */
  register(type: string, renderer: InputRenderer): this {
    if (this.registry.has(type)) this.registry.setContent(type, renderer);
    else this.registry.add(type, renderer);

    return this;
  }

  /**
   * Remove the renderer for `type`, so it renders through the fallback. No-op if none is registered.
   */
  unregister(type: string): this {
    if (this.registry.has(type)) this.registry.remove(type);

    return this;
  }

  has(type: string): boolean {
    return this.registry.has(type);
  }

  render(spec: InputSpec, ctx: InputContext<any>): Mithril.Children {
    return this.has(spec.type) ? this.registry.get(spec.type)(spec, ctx) : this.fallback(spec, ctx);
  }

  protected fallback(spec: InputSpec, ctx: InputContext<any>): Mithril.Children {
    if (this.fallbackRenderer) return this.fallbackRenderer(spec, ctx);

    return (
      <FormGroup
        name={ctx.name}
        {...(ctx.required ? { required: true } : {})}
        {...spec}
        label={ctx.label ?? spec.label}
        help={ctx.help ?? spec.help}
        stream={ctx.stream}
      />
    );
  }
}
