<?php namespace Flarum\Core\Formatter;

use Flarum\Core\Model;

/**
 * A formatter which formats a block of HTML, while leaving the contents
 * of specific tags like <code> and <pre> untouched.
 */
abstract class TextFormatter implements Formatter
{
    /**
     * A list of tags to ignore when applying formatting.
     *
     * @var array
     */
    protected $ignoreTags = ['code', 'pre'];

    /**
     * {@inheritdoc}
     */
    public function config(FormatterManager $manager)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function formatBeforePurification($text, Model $model = null)
    {
        return $this->formatAroundIgnoredTags($text, function ($text) use ($model) {
            return $this->formatTextBeforePurification($text, $model);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function formatAfterPurification($text, Model $model = null)
    {
        return $this->formatAroundIgnoredTags($text, function ($text) use ($model) {
            return $this->formatTextAfterPurification($text, $model);
        });
    }

    /**
     * Format non-ignored text before purification has taken place.
     *
     * @param string $text
     * @param Model $model
     * @return mixed
     */
    protected function formatTextBeforePurification($text, Model $model = null)
    {
        return $text;
    }

    /**
     * Format non-ignored text after purification has taken place.
     *
     * @param string $text
     * @param Model $model
     * @return string
     */
    protected function formatTextAfterPurification($text, Model $model = null)
    {
        return $text;
    }

    /**
     * Run a callback on parts of the provided text that aren't within the list
     * of ignored tags.
     *
     * @param string $text
     * @param callable $callback
     * @return string
     */
    protected function formatAroundIgnoredTags($text, callable $callback)
    {
        return $this->formatAroundTags($text, $this->ignoreTags, $callback);
    }

    /**
     * Run a callback on parts of the provided text that aren't within the
     * given list of tags.
     *
     * @param string $text
     * @param array $tags
     * @param callable $callback
     * @return string
     */
    protected function formatAroundTags($text, array $tags, callable $callback)
    {
        $chunks = preg_split('/(<.+?>)/is', $text, 0, PREG_SPLIT_DELIM_CAPTURE);
        $openTag = null;

        for ($i = 0; $i < count($chunks); $i++) {
            if ($i % 2 === 0) { // even numbers are text
                // Only process this chunk if there are no unclosed $ignoreTags
                if (null === $openTag) {
                    $chunks[$i] = $callback($chunks[$i]);
                }
            } else { // odd numbers are tags
                // Only process this tag if there are no unclosed $ignoreTags
                if (null === $openTag) {
                    // Check whether this tag is contained in $ignoreTags and is not self-closing
                    if (preg_match("`<(" . implode('|', $tags) . ").*(?<!/)>$`is", $chunks[$i], $matches)) {
                        $openTag = $matches[1];
                    }
                } else {
                    // Otherwise, check whether this is the closing tag for $openTag.
                    if (preg_match('`</\s*' . $openTag . '>`i', $chunks[$i], $matches)) {
                        $openTag = null;
                    }
                }
            }
        }

        return implode($chunks);
    }
}
