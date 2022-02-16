/// <reference path="../../@types/translator-icu-rich.d.ts" />
import type Mithril from 'mithril';
import type User from '../../common/models/User';
import ItemList from '../../common/utils/ItemList';
import AdminPage from './AdminPage';
declare type ColumnData = {
    /**
     * Column title
     */
    name: Mithril.Children;
    /**
     * Component(s) to show for this column.
     */
    content: (user: User) => Mithril.Children;
};
/**
 * Admin page which displays a paginated list of all users on the forum.
 */
export default class UserListPage extends AdminPage {
    /**
     * Number of users to load per page.
     */
    private numPerPage;
    /**
     * Current page number. Zero-indexed.
     */
    private pageNumber;
    /**
     * Total number of forum users.
     *
     * Fetched from the active `AdminApplication` (`app`), with
     * data provided by `AdminPayload.php`, or `flarum/statistics`
     * if installed.
     */
    readonly userCount: number;
    /**
     * Get total number of user pages.
     */
    private getTotalPageCount;
    /**
     * This page's array of users.
     *
     * `undefined` when page loads as no data has been fetched.
     */
    private pageData;
    /**
     * Are there more users available?
     */
    private moreData;
    private isLoadingPage;
    /**
     * Component to render.
     */
    content(): JSX.Element[];
    /**
     * Build an item list of columns to show for each user.
     *
     * Each column in the list should be an object with keys `name` and `content`.
     *
     * `name` is a string that will be used as the column name.
     * `content` is a function with the User model passed as the first and only argument.
     *
     * See `UserListPage.tsx` for examples.
     */
    columns(): ItemList<ColumnData>;
    headerInfo(): {
        className: string;
        icon: string;
        title: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
        description: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    };
    /**
     * Asynchronously fetch the next set of users to be rendered.
     *
     * Returns an array of Users, plus the raw API payload.
     *
     * Uses the `this.numPerPage` as the response limit, and automatically calculates the offset required from `pageNumber`.
     *
     * @param pageNumber The page number to load and display
     */
    loadPage(pageNumber: number): Promise<void>;
    nextPage(): void;
    previousPage(): void;
}
export {};
