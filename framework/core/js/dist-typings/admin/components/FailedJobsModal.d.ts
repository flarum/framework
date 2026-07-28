import Modal, { type IInternalModalAttrs } from '../../common/components/Modal';
import type Mithril from 'mithril';
interface FailedJob {
    id: string;
    connection: string | null;
    queue: string;
    name: string;
    failed_at: string;
    exception: string;
}
/**
 * Lists failed queue jobs with their exception/stack trace, and lets an admin
 * retry or delete them (individually or all at once). Backed by the
 * `queue.failed.*` endpoints, which are driver-agnostic.
 */
export default class FailedJobsModal extends Modal<IInternalModalAttrs> {
    loading: boolean;
    working: boolean;
    jobs: FailedJob[];
    expanded: Record<string, boolean>;
    className(): string;
    title(): string | any[];
    oninit(vnode: Mithril.Vnode<IInternalModalAttrs, this>): void;
    load(): Promise<void>;
    content(): JSX.Element;
    row(job: FailedJob): Mithril.Children;
    retry(job: FailedJob): void;
    retryAll(): void;
    forget(job: FailedJob): void;
    private act;
}
export {};
