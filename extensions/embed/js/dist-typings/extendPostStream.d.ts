/**
 * Remove the first post from a rendered PostStream vdom. Used when the
 * `hideFirstPost` route param is set, so the embed can show only the replies.
 */
export declare function hideFirstPost(vdom: {
    children: any[];
}): void;
/**
 * When replying inside the embed iframe, the iframe itself doesn't scroll
 * (iframe-resizer expands it to fit its content), so ask the parent frame to
 * scroll to the reply position instead. No-op outside an iframe or when the
 * composer isn't full screen.
 */
export declare function scrollParentToReply(offsetTop: number): void;
export default function extendPostStream(): void;
