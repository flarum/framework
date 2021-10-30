export default class RequestError {
    status: number;
    options: Record<string, unknown>;
    xhr: XMLHttpRequest;
    responseText: string | null;
    response: Record<string, unknown> | null;
    alert: any;
    constructor(status: number, responseText: string | null, options: Record<string, unknown>, xhr: XMLHttpRequest);
}
