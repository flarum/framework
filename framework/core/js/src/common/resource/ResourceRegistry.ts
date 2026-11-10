import type Model from '../Model';
import type ResourceDefinition from './ResourceDefinition';

/** A definition class: constructed with the resource type (subclasses that fix their type may ignore it). */
export type ResourceDefinitionClass<M extends Model = any> = new (type: string) => ResourceDefinition<M>;

/**
 * The definition class of each resource type, as the admin's `app.resources`. Core registers its own
 * at boot (users, groups, access tokens); an extension registers one for its resource, or
 * `extend()`s / replaces a registered class. A type with no registered class gets `fallback`
 * (the admin passes `ResourceDefinition`).
 */
export default class ResourceRegistry {
  protected definitions = new Map<string, ResourceDefinitionClass>();

  constructor(protected readonly fallback: ResourceDefinitionClass) {}

  /** Register (or replace) the definition class of `type`. */
  register<M extends Model>(type: string, definition: ResourceDefinitionClass<M>): this {
    this.definitions.set(type, definition);

    return this;
  }

  /** Whether `type` has a registered definition class. */
  has(type: string): boolean {
    return this.definitions.has(type);
  }

  /** The registered class of `type`, or null. */
  definitionClass<M extends Model>(type: string): ResourceDefinitionClass<M> | null {
    return (this.definitions.get(type) as ResourceDefinitionClass<M> | undefined) ?? null;
  }

  /**
   * A new definition of `type`, of the registered class else `fallback`. Each call builds a fresh
   * instance, so a page owns its definition; `await definition.load()` before reading its
   * descriptor, fields or filters.
   */
  get<M extends Model = Model>(type: string): ResourceDefinition<M> {
    const Definition = (this.definitionClass<M>(type) ?? this.fallback) as ResourceDefinitionClass<M>;

    return new Definition(type) as ResourceDefinition<M>;
  }
}
