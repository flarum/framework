import app from '../app';

/**
 * One event of a response streamed with `Flarum\Http\EventStream`: its type,
 * the milliseconds since the stream began on the server, and whatever else
 * it carries.
 */
export interface StreamEvent {
  type: string;
  ms: number;
  [key: string]: unknown;
}

/**
 * An event, and when it reached the page, by `performance.now()`.
 */
export interface StreamArrival {
  event: StreamEvent;
  at: number;
}

export interface ReadEventStreamOptions {
  method?: string;
  /** Sent as JSON. */
  body?: unknown;
}

/**
 * A request the server answered with an error before it started streaming,
 * such as a validation error, with the errors it gave.
 */
export class EventStreamRefusedError extends Error {
  constructor(
    public status: number,
    public errors: { detail?: string; code?: string; source?: { pointer?: string } }[]
  ) {
    super(`The server answered with ${status}.`);
  }
}

/**
 * Reads a response streamed with `Flarum\Http\EventStream`, handing over each
 * event as its line arrives. `app.request` can't be used for one: it gives
 * the body only once it's complete.
 *
 * It's a chunk of its own, loaded the first time it's imported:
 *
 *     const { default: readEventStream } = await import('flarum/common/utils/readEventStream');
 *
 *     await readEventStream(app.forum.attribute('apiUrl') + '/acme/import', { method: 'POST' }, (event) => {
 *       if (event.type === 'imported') this.count += event.count as number;
 *       m.redraw();
 *     });
 *
 * Resolves once the response has ended. Whether the work finished is for the
 * events to say: a dropped connection ends a response just as finished work
 * does.
 *
 * @throws EventStreamRefusedError when the server answers with an error before streaming.
 * @throws TypeError when the request can't be made, or the connection drops.
 */
export default async function readEventStream(
  url: string,
  options: ReadEventStreamOptions,
  onEvent: (event: StreamEvent, arrival: StreamArrival) => void
): Promise<void> {
  const headers: Record<string, string> = { Accept: 'application/x-ndjson' };

  if (app.session?.csrfToken) headers['X-CSRF-Token'] = app.session.csrfToken;
  if (options.body !== undefined) headers['Content-Type'] = 'application/json';

  const response = await fetch(url, {
    method: options.method || 'GET',
    headers,
    body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
    credentials: 'same-origin',
  });

  if (!response.ok || !response.body) {
    let errors = [];

    try {
      errors = (await response.json()).errors || [];
    } catch (e) {
      // Not JSON: the status says enough.
    }

    throw new EventStreamRefusedError(response.status, errors);
  }

  const reader = response.body.getReader();
  const decoder = new TextDecoder();
  let buffered = '';

  const emit = (line: string, at: number) => {
    if (line.trim() === '') return;

    const event = JSON.parse(line) as StreamEvent;

    onEvent(event, { event, at });
  };

  for (;;) {
    const { done, value } = await reader.read();

    if (done) break;

    const at = performance.now();
    buffered += decoder.decode(value, { stream: true });

    // Anything up to the last newline is complete; whatever follows it is
    // half a line and waits for the next chunk.
    const lines = buffered.split('\n');
    buffered = lines.pop() ?? '';

    lines.forEach((line) => emit(line, at));
  }

  emit(buffered + decoder.decode(), performance.now());
}

/**
 * Whether a stream's events reached the page as the server sent them, or were
 * held back and arrived together, comparing the time between the first and
 * last event on the server with the time between their arrivals. `unknown`
 * when the server took too little time to tell.
 */
export function streamBuffering(arrivals: StreamArrival[]): 'streamed' | 'buffered' | 'unknown' {
  if (arrivals.length < 2) return 'unknown';

  const first = arrivals[0];
  const last = arrivals[arrivals.length - 1];
  const serverSpan = last.event.ms - first.event.ms;

  if (serverSpan < 300) return 'unknown';

  return last.at - first.at < serverSpan / 4 ? 'buffered' : 'streamed';
}
