import type Mithril from 'mithril';
import type { AlertAttrs } from '../components/Alert';
export type InternalFlarumRequestOptions<ResponseType> = Mithril.RequestOptions<ResponseType> & {
    url: string;
};
export default class RequestError<ResponseType = string> {
    status: number;
    options: InternalFlarumRequestOptions<ResponseType>;
    xhr: XMLHttpRequest;
    responseText: string | null;
    response: {
        [key: string]: unknown;
        errors?: {
            detail?: string;
            code?: string;
            [key: string]: unknown;
        }[];
    } | null;
    alert: AlertAttrs | null;
    constructor(status: number, responseText: string | null, options: InternalFlarumRequestOptions<ResponseType>, xhr: XMLHttpRequest);
}
