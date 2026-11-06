import ResourceDefinition from '../../../../src/common/resource/ResourceDefinition';
import ResourceRegistry from '../../../../src/common/resource/ResourceRegistry';

class Widgets extends ResourceDefinition {}

describe('ResourceRegistry', () => {
  test('get() builds the registered class with the type', () => {
    const registry = new ResourceRegistry(ResourceDefinition).register('widgets', Widgets);
    const definition = registry.get('widgets');

    expect(definition).toBeInstanceOf(Widgets);
    expect(definition.type).toBe('widgets');
  });

  test('get() returns a new instance each call', () => {
    const registry = new ResourceRegistry(ResourceDefinition).register('widgets', Widgets);

    expect(registry.get('widgets')).not.toBe(registry.get('widgets'));
  });

  test('an unregistered type gets a plain ResourceDefinition', () => {
    const registry = new ResourceRegistry(ResourceDefinition);
    const definition = registry.get('gadgets');

    expect(definition.constructor).toBe(ResourceDefinition);
    expect(definition.type).toBe('gadgets');
    expect(registry.has('gadgets')).toBe(false);
  });

  test('has() and register() replacing a class', () => {
    class MoreWidgets extends Widgets {}
    const registry = new ResourceRegistry(ResourceDefinition).register('widgets', Widgets);

    expect(registry.has('widgets')).toBe(true);

    registry.register('widgets', MoreWidgets);

    expect(registry.get('widgets')).toBeInstanceOf(MoreWidgets);
    expect(registry.definitionClass('widgets')).toBe(MoreWidgets);
    expect(registry.definitionClass('gadgets')).toBeNull();
  });
});
