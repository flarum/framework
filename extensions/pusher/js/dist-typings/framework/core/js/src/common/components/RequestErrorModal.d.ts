/// <reference types="mithril" />
import type RequestError from '../utils/RequestError';
import Modal, { IInternalModalAttrs } from './Modal';
export interface IRequestErrorModalAttrs extends IInternalModalAttrs {
    error: RequestError;
    formattedError: string[];
}
export default class RequestErrorModal<CustomAttrs extends IRequestErrorModalAttrs = IRequestErrorModalAttrs> extends Modal<CustomAttrs> {
    className(): string;
    title(): string;
    content(): JSX.Element;
}
