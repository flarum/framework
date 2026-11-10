import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type { HistoryEntry, Installed, RunState } from '../types';
export interface HistoryTabAttrs extends ComponentAttrs {
    history: HistoryEntry[];
    installed: Installed[];
    /** The id of the entry that can still be undone, if any: see ExtensionManagerPage.undoableFromHistory. */
    undoable?: string | null;
    rollingBack?: boolean;
    onundo?: () => void;
}
/**
 * What changed on this forum, and when — the "recently updated" list an app
 * store keeps. Read from storage/extension-manager/history.json (see History.php).
 */
export default class HistoryTab extends Component<HistoryTabAttrs> {
    view(): JSX.Element;
    protected outcome(entry: HistoryEntry): string | any[];
    protected tone(state: RunState): string;
}
