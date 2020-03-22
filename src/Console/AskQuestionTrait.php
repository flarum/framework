<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Console;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;

trait AskQuestionTrait
{
    /**
     * @var QuestionHelper
     */
    protected $questionHelper;

    protected function questionHelper()
    {
        if (is_null($this->questionHelper)) {
            $this->questionHelper = new QuestionHelper;
        }
        return $this->questionHelper;
    }

    protected function ask($question, $default = null)
    {
        $question = new Question("<question>$question</question> ", $default);

        return $this->questionHelper()->ask($this->input, $this->output, $question);
    }

    protected function secret($question)
    {
        $question = new Question("<question>$question</question> ");

        $question->setHidden(true)->setHiddenFallback(true);

        return $this->questionHelper()->ask($this->input, $this->output, $question);
    }
}
