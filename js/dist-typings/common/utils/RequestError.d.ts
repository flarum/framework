export default class RequestError {
    status: string;
    options: object;
    xhr: XMLHttpRequest;
    responseText: string | null;
    response: object | null;
    alert: any;
    constructor(status: string, responseText: string | null, options: object, xhr: XMLHttpRequest);
}
