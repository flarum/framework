import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type { HistoryEntry, Installed } from '../types';
export interface HistoryTabAttrs extends ComponentAttrs {
    history: HistoryEntry[];
    installed: Installed[];
}
/**
 * What changed on this forum, and when — the "recently updated" list an app
 * store keeps. Read from storage/extension-manager/history.json (see History.php).
 */
export default class HistoryTab extends Component<HistoryTabAttrs> {
    view(): JSX.Element;
    private outcome;
    private tone;
}
