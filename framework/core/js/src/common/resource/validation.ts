import app from '../app';
import fireDebugWarning from '../helpers/fireDebugWarning';
import { constraintsFor, isRequired } from './descriptor';
import { snapshot } from './field';
import type { FieldConstraints, FieldDescriptor, FormMode } from './types';

const EMAIL = /^[^\s@]+@[^\s@]+$/;
const URL_SHAPE = /^[a-z][a-z\d+.-]*:\/\/[^\s/?#]+/i;
/** For what `new URL()` rejects but Laravel's `url` rule may accept, e.g. a host like `999.1.1.1`. */
const LOOSE_URL = /^[a-z][a-z\d+.-]*:\/\/[^\s/?#]+(?:[/?#]\S*)?$/i;
const UUID = /^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$/i;
/** As Laravel's `Str::isUlid()`: 26 Crockford base32 characters, the first at most 7. */
const ULID = /^[0-7][0-9a-hjkmnp-tv-z]{25}$/i;
/** Leading zeros are let through, the server decides on those. */
const IPV4 = /^(?:(?:25[0-5]|2[0-4]\d|1?\d?\d|0+\d{1,3})\.){3}(?:25[0-5]|2[0-4]\d|1?\d?\d|0+\d{1,3})$/;
const HEX_COLOR = /^#(?:[0-9a-f]{3}){1,2}$|^#(?:[0-9a-f]{4}){1,2}$/i;
const INTEGER = /^\s*[+-]?\d+\s*$/;
const ACCEPTED: unknown[] = [true, 'yes', 'on', 1, '1', 'true'];
const DECLINED: unknown[] = [false, 'no', 'off', 0, '0', 'false'];

/** Compiled patterns by source and flags; null when JS cannot compile it. */
const patterns = new Map<string, RegExp | null>();

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
 * The server's custom message for a constraint (`c.messages[rule]`) when it sent one, else the
 * generic translation `core.lib.resource.validation.{key}`.
 */
function ruleMessage(c: FieldConstraints, rule: string, key: string, params: Record<string, unknown> = {}): string {
  return c.messages?.[rule] ?? message(`core.lib.resource.validation.${key}`, params);
}

/**
 * Whether a value counts as absent, as Laravel's `required` sees it: `null`, `undefined`, a blank
 * string or an empty array. As on the server, only the required rules apply to an absent value.
 */
export function isAbsent(value: unknown): boolean {
  return value === null || value === undefined || (typeof value === 'string' && value.trim() === '') || (Array.isArray(value) && !value.length);
}

/**
 * Compile a server pattern, always in unicode mode (PCRE's `\p{L}` and the like need it). A pattern
 * JS cannot compile is skipped, with one debug warning.
 */
function pattern(field: FieldDescriptor, source: string, flags: string = ''): RegExp | null {
  const all = new Set(flags.split(''));

  if (!all.has('v')) all.add('u');

  const normalized = [...all].sort().join('');
  const key = `${normalized}/${source}`;

  if (!patterns.has(key)) {
    let compiled: RegExp | null = null;

    try {
      compiled = new RegExp(source, normalized);
    } catch (error) {
      fireDebugWarning(`Resource field [${field.name}]: its pattern cannot be compiled in JavaScript and is not checked client-side.`, error);
    }

    patterns.set(key, compiled);
  }

  return patterns.get(key)!;
}

function isUrl(value: string): boolean {
  if (!URL_SHAPE.test(value)) return false;

  try {
    new URL(value);

    return true;
  } catch {
    return LOOSE_URL.test(value);
  }
}

function isIpv6(value: string): boolean {
  if (!/^[\da-f:.]+$/i.test(value) || !value.includes(':')) return false;

  try {
    new URL(`http://[${value}]/`);

    return true;
  } catch {
    return false;
  }
}

/** Whether the format holds; unknown formats hold. */
function formatHolds(format: string, value: string): boolean {
  switch (format) {
    case 'email':
      return EMAIL.test(value);
    case 'uri':
      return isUrl(value);
    case 'uuid':
      return UUID.test(value);
    case 'ulid':
      return ULID.test(value);
    case 'ipv4':
      return IPV4.test(value);
    case 'ipv6':
      return isIpv6(value);
    case 'ip':
      return IPV4.test(value) || isIpv6(value);
    case 'hex-color':
      return HEX_COLOR.test(value);
    default:
      return true;
  }
}

/** The translation key of a format's message. */
const FORMAT_MESSAGES: Record<string, string> = {
  email: 'email',
  uri: 'url',
  uuid: 'uuid',
  ulid: 'ulid',
  ip: 'ip',
  ipv4: 'ipv4',
  ipv6: 'ipv6',
  'hex-color': 'hex_color',
};

/** Whether `n` is a multiple of `step`, tolerating float error (`0.3` of `0.1`). */
function isMultipleOf(n: number, step: number): boolean {
  const ratio = n / step;

  return Math.abs(ratio - Math.round(ratio)) <= 1e-9 * Math.max(1, Math.abs(ratio));
}

function patternErrors(field: FieldDescriptor, c: FieldConstraints, value: string): boolean {
  const list = [...(c.pattern !== undefined ? [{ pattern: c.pattern, flags: c.patternFlags }] : []), ...(c.patterns ?? [])];

  return list.some((p) => {
    const regex = pattern(field, p.pattern, p.flags ?? '');

    return !!regex && !regex.test(value);
  });
}

export interface ValidateOptions {
  /** The form's current values by field name, for `requiredWhen`. Default: none. */
  values?: Record<string, unknown>;
  /** Whether the value is sent, for the `filled` constraint. Default true. */
  sent?: boolean;
}

/**
 * The translated messages for `value` against the field in `mode`, none when valid. Uniqueness,
 * `present`, `requiredConditional` and other server-only rules are left to the server; so are a
 * pattern JS cannot compile and an unknown format. Callers skip `conditional` fields.
 */
export function validateValue(field: FieldDescriptor, value: unknown, mode: FormMode, options: ValidateOptions = {}): string[] {
  const c = constraintsFor(field, mode);

  if (isAbsent(value)) {
    if (isRequired(field, mode, options.values)) return [ruleMessage(c, 'required', 'required')];

    return c.filled && options.sent !== false ? [ruleMessage(c, 'filled', 'required')] : [];
  }

  const errors: string[] = [];
  const scalar = typeof value === 'string' || typeof value === 'number';

  if (c.accepted && !ACCEPTED.includes(value)) errors.push(ruleMessage(c, 'accepted', 'accepted'));
  if (c.declined && !DECLINED.includes(value)) errors.push(ruleMessage(c, 'declined', 'declined'));

  if (typeof value === 'string') {
    const length = [...value].length;

    if (c.minLength !== undefined && length < c.minLength) errors.push(ruleMessage(c, 'minLength', 'min_length', { count: c.minLength }));
    if (c.maxLength !== undefined && length > c.maxLength) errors.push(ruleMessage(c, 'maxLength', 'max_length', { count: c.maxLength }));
    if (patternErrors(field, c, value)) errors.push(ruleMessage(c, 'pattern', 'pattern'));

    if (c.format !== undefined && !formatHolds(c.format, value)) {
      errors.push(ruleMessage(c, 'format', FORMAT_MESSAGES[c.format]));
    }
  }

  if (c.enum && scalar && !c.enum.includes(String(value))) errors.push(ruleMessage(c, 'enum', 'enum'));
  if (c.not?.enum && scalar && c.not.enum.includes(String(value))) errors.push(ruleMessage(c, 'not', 'not_in'));

  if (field.kind === 'integer' && ((typeof value === 'number' && !Number.isInteger(value)) || (typeof value === 'string' && !INTEGER.test(value)))) {
    errors.push(ruleMessage(c, 'integer', 'integer'));
  }

  if (scalar) {
    const n = Number(value);

    if (Number.isFinite(n)) {
      if (c.minimum !== undefined && n < c.minimum) errors.push(ruleMessage(c, 'minimum', 'minimum', { min: c.minimum }));
      if (c.maximum !== undefined && n > c.maximum) errors.push(ruleMessage(c, 'maximum', 'maximum', { max: c.maximum }));
      if (c.exclusiveMinimum !== undefined && n <= c.exclusiveMinimum) {
        errors.push(ruleMessage(c, 'exclusiveMinimum', 'exclusive_minimum', { min: c.exclusiveMinimum }));
      }
      if (c.exclusiveMaximum !== undefined && n >= c.exclusiveMaximum) {
        errors.push(ruleMessage(c, 'exclusiveMaximum', 'exclusive_maximum', { max: c.exclusiveMaximum }));
      }
      if (c.multipleOf && !isMultipleOf(n, c.multipleOf)) errors.push(ruleMessage(c, 'multipleOf', 'multiple_of', { value: c.multipleOf }));
    }
  }

  if (Array.isArray(value)) {
    if (c.minItems !== undefined && value.length < c.minItems) errors.push(ruleMessage(c, 'minItems', 'min_items', { count: c.minItems }));
    if (c.maxItems !== undefined && value.length > c.maxItems) errors.push(ruleMessage(c, 'maxItems', 'max_items', { count: c.maxItems }));

    if (c.uniqueItems && new Set(value.map((item) => snapshot(item))).size < value.length) {
      errors.push(ruleMessage(c, 'uniqueItems', 'unique_items'));
    }

    const allowed = c.items?.enum;

    if (allowed && value.some((item) => (typeof item === 'string' || typeof item === 'number') && !allowed.includes(String(item)))) {
      errors.push(ruleMessage(c, 'items', 'items_enum'));
    }
  }

  return errors;
}
