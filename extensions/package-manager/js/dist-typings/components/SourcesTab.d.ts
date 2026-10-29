import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import type { ConfigPayload, Repository as Repo, StoredCredential as Stored } from '../types';
export default class SourcesTab extends Component {
    protected loading: boolean;
    protected saving: boolean;
    protected data: ConfigPayload | null;
    /** Why the settings could not be read at all, when they could not. */
    protected loadError: string | null;
    protected repoType: string;
    protected repoUrl: string;
    protected filter: string;
    protected confirmRepo: string | null;
    protected authKind: string;
    protected authHost: string;
    protected authUser: string;
    protected authSecret: string;
    protected reveal: boolean;
    protected confirmAuth: string | null;
    oninit(vnode: Mithril.Vnode<ComponentAttrs, this>): void;
    view(): JSX.Element;
    /**
     * Three answers before any detail: where Composer looks, how finished a
     * release must be, and how many hosts it can sign in to. Each tile jumps to
     * the card that changes it.
     */
    overview(data: ConfigPayload): JSX.Element;
    repositories(data: ConfigPayload): JSX.Element;
    /**
     * Grouped by owner and host, so "ernestdefoe · github.com" is said once
     * instead of twenty-two times. Most forums keep their private extensions
     * under one account, and the repeated half of every row was the clutter.
     */
    groups(repos: Repo[]): {
        key: string;
        owner: string;
        where: string;
        icon: string;
        tone: string;
        repos: Repo[];
    }[];
    repoTile(r: Repo, owner: string): JSX.Element;
    /** A repository type's name: "Git repository", "Folder on this server"… */
    typeLabel(type: string): string;
    addRepo(data: ConfigPayload, repos: Repo[]): JSX.Element;
    stability(data: ConfigPayload): JSX.Element;
    credentials(data: ConfigPayload): JSX.Element;
    authRow(c: Stored): JSX.Element;
    /**
     * Every credential kind links to the page that makes one. A field that asks
     * for a token without saying where tokens come from sends somebody to search
     * GitHub's settings for the right screen and the right scope.
     */
    tokenLink(): {
        href: string;
        label: string;
    } | null;
    addAuth(kinds: string[]): JSX.Element;
    revealLabel(): string;
    load(): void;
    send(body: Record<string, unknown>, after?: () => void): void;
}
