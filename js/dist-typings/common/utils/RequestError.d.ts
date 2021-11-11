import type Mithril from 'mithril';
export declare type InternalFlarumRequestOptions<ResponseType> = Mithril.RequestOptions<ResponseType> & {
    errorHandler: (error: RequestError) => void;
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
    alert: any;
    constructor(status: number, responseText: string | null, options: InternalFlarumRequestOptions<ResponseType>, xhr: XMLHttpRequest);
}
