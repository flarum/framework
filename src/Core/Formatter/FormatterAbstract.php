<?php namespace Flarum\Core\Formatter;

use Flarum\Core\Models\Post;
use Closure;

abstract class FormatterAbstract
{
    public function beforePurification($text, Post $post = null)
    {
        return $text;
    }

    public function afterPurification($text, Post $post = null)
    {
        return $text;
    }

    protected function ignoreTags($text, array $tags, Closure $callback)
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
