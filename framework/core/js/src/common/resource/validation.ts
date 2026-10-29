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
 */
export class ValidationError extends Error {
  constructor(public readonly errors: Record<string, string[]>) {
    super('The form has invalid fields.');
    this.name = 'ValidationError';
  }
}

function message(key: string, params: Record<string, unknown> = {}): string {
  return app.translator.trans(key, params, true);
}

/**
 * Whether a value counts as absent, as Laravel's `required` sees it: `null`, `undefined`, a blank
 * string or an empty array. As on the server, only the required rules apply to an absent value.
 */
export function isAbsent(value: unknown): boolean {
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

export interface ValidateOptions {
  /** The form's current values by field name, for `requiredWhen`. Default: none. */
  values?: Record<string, unknown>;
  /** Whether the value is sent, for the `filled` constraint. Default true. */
  sent?: boolean;
}

/**
 * Check a value against whether the field is required in the mode (see `isRequired()`), its
 * `filled` constraint and its other constraints, returning the translated messages (none when
 * valid). Uniqueness, `present`, `requiredConditional` and anything else that needs the server is
 * left to it, as are `conditional` fields, which callers skip. A pattern JS cannot compile is skipped.
 */
export function validateValue(field: FieldDescriptor, value: unknown, mode: FormMode, options: ValidateOptions = {}): string[] {
  if (isAbsent(value)) {
    const required = isRequired(field, mode, options.values) || (!!field.constraints?.filled && options.sent !== false);

    return required ? [message('core.lib.resource.validation.required')] : [];
  }

  const c = field.constraints ?? {};
  const errors: string[] = [];

  if (typeof value === 'string') {
    const length = [...value].length;

    if (c.minLength !== undefined && length < c.minLength) errors.push(message('core.lib.resource.validation.min_length', { count: c.minLength }));
    if (c.maxLength !== undefined && length > c.maxLength) errors.push(message('core.lib.resource.validation.max_length', { count: c.maxLength }));

    if (c.pattern !== undefined) {
      const regex = pattern(field);

      if (regex && !regex.test(value)) errors.push(message('core.lib.resource.validation.pattern'));
    }

    if (c.format === 'email' && !EMAIL.test(value)) errors.push(message('core.lib.resource.validation.email'));
    if (c.format === 'url' && !isUrl(value)) errors.push(message('core.lib.resource.validation.url'));
  }

  if (c.enum && (typeof value === 'string' || typeof value === 'number') && !c.enum.includes(String(value))) {
    errors.push(message('core.lib.resource.validation.enum'));
  }

  if ((c.minimum !== undefined || c.maximum !== undefined) && (typeof value === 'number' || typeof value === 'string')) {
    const n = Number(value);

    if (Number.isFinite(n)) {
      if (c.minimum !== undefined && n < c.minimum) errors.push(message('core.lib.resource.validation.minimum', { min: c.minimum }));
      if (c.maximum !== undefined && n > c.maximum) errors.push(message('core.lib.resource.validation.maximum', { max: c.maximum }));
    }
  }

  return errors;
}
