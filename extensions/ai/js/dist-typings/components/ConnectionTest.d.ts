import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import type { StreamArrival, StreamEvent } from 'flarum/common/utils/readEventStream';
/**
 * A connection as the modal sends it to be tested before it's saved.
 */
export interface CandidateConnection {
    driver: string;
    url?: string;
    region?: string;
    model?: string;
    guardrail_id?: string;
    guardrail_version?: string;
    key?: string;
    removeKey?: boolean;
}
/**
 * Runs a test of a connection and holds what it has found so far, so the
 * test carries on while the component showing it is redrawn.
 */
export declare class ConnectionTestState {
    status: 'idle' | 'running' | 'passed' | 'failed';
    text: string;
    arrivals: StreamArrival[];
    done: StreamEvent | null;
    error: StreamEvent | null;
    /** Why the server refused to start the test, such as a field it found wrong. */
    refused: string | null;
    buffering: 'streamed' | 'buffered' | 'unknown';
    /** Each run's number, so a run that's been replaced stops updating. */
    protected run: number;
    start(name: string, candidate?: CandidateConnection): Promise<void>;
    /**
     * Milliseconds from the test starting on the server to its end.
     */
    totalMs(): number | null;
}
/**
 * A duration as the admin reads it: milliseconds under a second, then seconds.
 */
export declare function formatMs(ms: number): string;
export interface ConnectionTestAttrs extends ComponentAttrs {
    state: ConnectionTestState;
}
/**
 * What a connection test has found: the reply as it's written, then whether
 * the connection works, how long it took, and whether the reply streamed.
 */
export default class ConnectionTest extends Component<ConnectionTestAttrs> {
    view(): Mithril.Children;
    passed(): Mithril.Children;
    failed(): Mithril.Children;
    streamingNote(): Mithril.Children;
}
