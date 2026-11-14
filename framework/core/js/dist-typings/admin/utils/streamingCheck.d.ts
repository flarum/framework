export type StreamingStatus = 'idle' | 'checking' | 'streamed' | 'buffered' | 'unknown' | 'failed';
/**
 * Whether this server lets a streamed response reach the browser as it's
 * written. The dashboard checks once per browser session and warns when it's
 * held back. Anything else that streams, and can tell, can report what it saw.
 */
export declare const streamingCheck: {
    status: StreamingStatus;
    /** The last conclusive answer, kept while the check runs again. */
    verdict: "streamed" | "buffered" | null;
    /**
     * Asks the server for a few events a moment apart, and sees whether they
     * arrive apart too.
     */
    run(): Promise<void>;
    /**
     * Runs the check, unless it has already run in this browser session.
     */
    runOnce(): Promise<void>;
    /**
     * What another stream saw. One too quick to tell changes nothing.
     */
    note(verdict: "streamed" | "buffered" | "unknown"): void;
};
