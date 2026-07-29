import { ClassComponent, Vnode } from 'mithril';
import AuditLog from '../models/AuditLog';
interface AuditItemAttrs {
    log: AuditLog;
    changeQuery: (q: string) => void;
}
export default class AuditItem implements ClassComponent<AuditItemAttrs> {
    showRaw: boolean;
    view(vnode: Vnode<AuditItemAttrs>): JSX.Element;
}
export {};
