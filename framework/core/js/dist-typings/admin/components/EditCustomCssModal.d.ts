/// <reference path="../../@types/translator-icu-rich.d.ts" />
export default class EditCustomCssModal extends SettingsModal {
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    form(): JSX.Element[];
}
import SettingsModal from "./SettingsModal";
