import Model from 'flarum/common/Model';
export default class DataType extends Model {
    type(): string;
    exportDescription(): string;
    anonymizeDescription(): string;
    deleteDescription(): string;
    extension(): string | null;
}
