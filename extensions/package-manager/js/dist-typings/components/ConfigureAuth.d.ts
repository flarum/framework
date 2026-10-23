import type Mithril from 'mithril';
import ConfigureJson, { IConfigureJson } from './ConfigureJson';
export default class ConfigureAuth extends ConfigureJson<IConfigureJson> {
    protected type: string;
    title(): Mithril.Children;
    className(): string;
    content(): Mithril.Children;
    submitButton(): Mithril.Children[];
    onchange(oldHost: string | null, type: string, host: string, token: string): void;
}
