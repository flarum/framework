import type Model from '../Model';

/**
 * Attributes tried, in order, to name a record in titles and confirmations.
 */
const RECORD_LABEL_ATTRIBUTES = ['name', 'title', 'label', 'displayName', 'username', 'slug'];

/**
 * A short human name for a record: the first non-empty string among the usual naming attributes,
 * else `#id`.
 */
export default function recordLabel(model: Model): string {
  for (const key of RECORD_LABEL_ATTRIBUTES) {
    const value = model.attribute<unknown>(key);

    if (typeof value === 'string' && value.trim() !== '') return value;
  }

  return `#${model.id()}`;
}
