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
export declare class EventStreamRefusedError extends Error {
    status: number;
    errors: {
        detail?: string;
        code?: string;
        source?: {
            pointer?: string;
        };
    }[];
    constructor(status: number, errors: {
        detail?: string;
        code?: string;
        source?: {
            pointer?: string;
        };
    }[]);
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
export default function readEventStream(url: string, options: ReadEventStreamOptions, onEvent: (event: StreamEvent, arrival: StreamArrival) => void): Promise<void>;
/**
 * Whether a stream's events reached the page as the server sent them, or were
 * held back and arrived together, comparing the time between the first and
 * last event on the server with the time between their arrivals. `unknown`
 * when the server took too little time to tell.
 */
export declare function streamBuffering(arrivals: StreamArrival[]): 'streamed' | 'buffered' | 'unknown';
