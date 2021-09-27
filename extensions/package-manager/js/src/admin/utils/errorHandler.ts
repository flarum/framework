import app from "flarum/admin/app";
import ComposerFailureModal from "../components/ComposerFailureModal";

export default function (e: any) {
  const error = e.response.errors[0];

  if (error.code !== 'composer_command_failure') {
    throw e;
  }

  app.modal.show(ComposerFailureModal, { error });
}
