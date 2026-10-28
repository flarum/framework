import app from '../app';
import fireDebugWarning from '../helpers/fireDebugWarning';
import { isRequired } from './descriptor';
import type { FieldDescriptor, FormMode } from './types';

const EMAIL = /^[^\s@]+@[^\s@]+$/;
const URL_SHAPE = /^[a-z][a-z\d+.-]*:\/\/[^\s/?#]+/i;

/** Compiled patterns per descriptor; null when JS cannot compile it. */
const patterns = new WeakMap<FieldDescriptor, RegExp | null>();

/**
 * Rejected by `ResourceFormState.save()` when client-side validation fails; no request was sent.
 *
 * @experimental
 */
export class ValidationError extends Error {
  constructor(public readonly errors: Record<string, string[]>) {
    super('The form has invalid fields.');
    this.name = 'ValidationError';
  }
}

function message(key: string, params: Record<string, unknown> = {}): string {
  return app.translator.trans(`core.lib.resource.validation.${key}`, params, true);
}

/**
 * Whether a value counts as absent: `null`, `undefined`, a blank string or an empty array. As on
 * the server, only `required` applies to an absent value.
 */
function isAbsent(value: unknown): boolean {
  return value === null || value === undefined || (typeof value === 'string' && value.trim() === '') || (Array.isArray(value) && !value.length);
}

function pattern(field: FieldDescriptor): RegExp | null {
  if (!patterns.has(field)) {
    let compiled: RegExp | null = null;

    try {
      compiled = new RegExp(field.constraints!.pattern!, field.constraints!.patternFlags ?? '');
    } catch (error) {
      fireDebugWarning(`Resource field [${field.name}]: its pattern cannot be compiled in JavaScript and is not checked client-side.`, error);
    }

    patterns.set(field, compiled);
  }

  return patterns.get(field)!;
}

function isUrl(value: string): boolean {
  if (!URL_SHAPE.test(value)) return false;

  try {
    new URL(value);

    return true;
  } catch {
    return false;
  }
}

/**
 * Check a value against a field's `required` flag for the mode and its constraints, returning the
 * translated messages (none when valid). Uniqueness and anything else that needs the server is left
 * to it, as are `conditional` fields, which callers skip. A pattern JS cannot compile is skipped.
 *
 * @experimental
 */
export function validateValue(field: FieldDescriptor, value: unknown, mode: FormMode): string[] {
  if (isAbsent(value)) {
    return isRequired(field, mode) ? [message('required')] : [];
  }

  const c = field.constraints ?? {};
  const errors: string[] = [];

  if (typeof value === 'string') {
    const length = [...value].length;

    if (c.minLength !== undefined && length < c.minLength) errors.push(message('min_length', { count: c.minLength }));
    if (c.maxLength !== undefined && length > c.maxLength) errors.push(message('max_length', { count: c.maxLength }));

    if (c.pattern !== undefined) {
      const regex = pattern(field);

      if (regex && !regex.test(value)) errors.push(message('pattern'));
    }

    if (c.format === 'email' && !EMAIL.test(value)) errors.push(message('email'));
    if (c.format === 'url' && !isUrl(value)) errors.push(message('url'));
  }

  if (c.enum && (typeof value === 'string' || typeof value === 'number') && !c.enum.includes(String(value))) {
    errors.push(message('enum'));
  }

  if ((c.minimum !== undefined || c.maximum !== undefined) && (typeof value === 'number' || typeof value === 'string')) {
    const n = Number(value);

    if (Number.isFinite(n)) {
      if (c.minimum !== undefined && n < c.minimum) errors.push(message('minimum', { min: c.minimum }));
      if (c.maximum !== undefined && n > c.maximum) errors.push(message('maximum', { max: c.maximum }));
    }
  }

  return errors;
}
