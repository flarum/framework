import Modal from './Modal';
import {ComponentProps} from '../Component';
import RequestError from '../utils/RequestError';

export interface RequestErrorModalProps extends ComponentProps {
  error: RequestError,
}

export default class RequestErrorModal<T extends RequestErrorModalProps = RequestErrorModalProps> extends Modal<T> {
    className(): string {
        return 'RequestErrorModal Modal--large';
    }

    title(): string {
        return this.props.error.xhr
            ? `${this.props.error.xhr.status} ${this.props.error.xhr.statusText}`
            : '';
    }

    content() {
        let responseText;

        try {
            responseText = JSON.stringify(JSON.parse(this.props.error.responseText), null, 2);
        } catch (e) {
            responseText = this.props.error.responseText;
        }

        return <div className="Modal-body">
            <pre>
                {this.props.error.options.method} {this.props.error.options.url}<br/><br/>
                {responseText}
            </pre>
        </div>
    }
}
