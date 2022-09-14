/// <reference types="mithril" />
import DashboardWidget from './DashboardWidget';
export default class DebugWarningWidget extends DashboardWidget {
    className(): string;
    content(): JSX.Element;
}
