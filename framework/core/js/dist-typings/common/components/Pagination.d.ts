/// <reference types="mithril" />
import Component, { ComponentAttrs } from '../Component';
export interface IPaginationInterface extends ComponentAttrs {
    total: number;
    perPage: number;
    currentPage: number;
    loadingPageNumber?: number;
    onChange: (page: number) => void;
}
export default class Pagination<CustomAttrs extends IPaginationInterface = IPaginationInterface> extends Component<CustomAttrs> {
    view(): JSX.Element;
}
