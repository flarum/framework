import type Mithril from 'mithril';
import ConfigureJson, { type IConfigureJson } from './ConfigureJson';
export declare type Repository = {
    type: 'composer' | 'vcs' | 'path';
    url: string;
};
export default class ConfigureComposer extends ConfigureJson<IConfigureJson> {
    protected type: string;
    title(): Mithril.Children;
    className(): string;
    content(): Mithril.Children;
    submitButton(): Mithril.Children[];
    onchange(repository: Repository, name: string): void;
}
