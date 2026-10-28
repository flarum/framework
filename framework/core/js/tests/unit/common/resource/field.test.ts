import { app } from '../../../../src/admin';
import Model from '../../../../src/common/Model';
import { fieldSave, fieldValue, mergeSaveData } from '../../../../src/common/resource/field';
import { bootAdmin, fd } from './fixtures';

class Thing extends Model {}

describe('field defaults', () => {
  beforeAll(() => {
    bootAdmin();
    app.store.models.things = Thing;
    app.store.models.categories = Thing;
  });

  function thing(attributes: Record<string, unknown>, relationships: Record<string, unknown> = {}) {
    return app.store.pushObject<Thing>({ type: 'things', id: '1', attributes, relationships } as any)!;
  }

  test('fieldValue reads the attribute', () => {
    expect(fieldValue('title', { label: 'Title' }, thing({ title: 'Hello' }))).toBe('Hello');
  });

  test('fieldValue is null without a model or without the attribute', () => {
    expect(fieldValue('title', { label: 'Title' }, null)).toBeNull();
    expect(fieldValue('missing', { label: 'Missing' }, thing({}))).toBeNull();
  });

  test('fieldValue uses the field getter when present', () => {
    expect(fieldValue('title', { label: 'Title', value: () => 'custom' }, thing({ title: 'Hello' }))).toBe('custom');
  });

  test('fieldValue reads relationships for relation fields', () => {
    const category = app.store.pushObject({ type: 'categories', id: '7', attributes: { name: 'Knives' } } as any);
    const model = thing({}, { category: { data: { type: 'categories', id: '7' } } });

    expect(
      fieldValue('category', { label: 'Category', descriptor: fd('category', 'toOne', { relationship: { types: ['categories'] } }) }, model)
    ).toBe(category);
  });

  test('fieldSave defaults to { [key]: v }', () => {
    expect(fieldSave('title', { label: 'Title' }, 'Hi')).toEqual({ title: 'Hi' });
  });

  test('fieldSave puts relations under relationships', () => {
    const category = app.store.pushObject({ type: 'categories', id: '7', attributes: {} } as any);

    expect(fieldSave('category', { label: 'Category', descriptor: fd('category', 'toOne') }, category)).toEqual({ relationships: { category } });
  });

  test('fieldSave uses the field serializer when present', () => {
    expect(fieldSave('price', { label: 'Price', serialize: (v) => ({ price: Number(v) }) }, '12.5')).toEqual({ price: 12.5 });
  });

  test('mergeSaveData deep-merges translations and relationships', () => {
    const a = app.store.pushObject({ type: 'categories', id: '1', attributes: {} } as any);
    const b = app.store.pushObject({ type: 'categories', id: '2', attributes: {} } as any);

    expect(
      mergeSaveData(
        { title: 'x', translations: { de: { name: 'Name' } } },
        { translations: { de: { url: 'https://x.de' }, fr: { name: 'Nom' } } },
        { relationships: { category: a } },
        { relationships: { tags: [a, b] } }
      )
    ).toEqual({
      title: 'x',
      translations: { de: { name: 'Name', url: 'https://x.de' }, fr: { name: 'Nom' } },
      relationships: { category: a, tags: [a, b] },
    });
  });

  test('mergeSaveData does not mutate its inputs', () => {
    const first = { translations: { de: { name: 'A' } } };

    mergeSaveData(first, { translations: { de: { url: 'u' } } });

    expect(first).toEqual({ translations: { de: { name: 'A' } } });
  });
});
