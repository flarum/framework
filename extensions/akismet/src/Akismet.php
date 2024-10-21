<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Akismet;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class Akismet
{
    private string $apiUrl;
    private array $params = [];

    public function __construct(
        private readonly string $apiKey,
        string $homeUrl,
        private readonly string $flarumVersion,
        private readonly string $extensionVersion,
        bool $inDebugMode = false
    ) {
        $this->apiUrl = "https://$apiKey.rest.akismet.com/1.1";
        $this->params['blog'] = $homeUrl;

        if ($inDebugMode) {
            $this->params['is_test'] = true;
        }
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * @param  string  $type  e.g. comment-check, submit-spam or submit-ham;
     * @throws GuzzleException
     */
    protected function sendRequest(string $type): ResponseInterface
    {
        $client = new Client();

        return $client->request('POST', "$this->apiUrl/$type", [
            'headers' => [
                'User-Agent' => "Flarum/$this->flarumVersion | Akismet/$this->extensionVersion",
            ],
            'form_params' => $this->params,
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function checkSpam(): array
    {
        $response = $this->sendRequest('comment-check');

        return [
            'isSpam' => $response->getBody()->getContents() === 'true',
            'proTip' => $response->getHeaderLine('X-akismet-pro-tip'),
        ];
    }

    /**
     * @throws GuzzleException
     */
    public function submitSpam(): void
    {
        $this->sendRequest('submit-spam');
    }

    /**
     * @throws GuzzleException
     */
    public function submitHam(): void
    {
        $this->sendRequest('submit-ham');
    }

    /**
     * Allows you to set additional parameter
     * This lets you use Akismet features not supported directly in this util.
     */
    public function withParam(string $key, mixed $value): Akismet
    {
        $new = clone $this;
        $new->params[$key] = $value;

        return $new;
    }

    /**
     * The front page or home URL of the instance making the request. For a blog or wiki this would be the front page. Note: Must be a full URI, including http://.
     */
    public function withBlog(string $url): Akismet
    {
        return $this->withParam('blog', $url);
    }

    /**
     * IP address of the comment submitter.
     */
    public function withIp(string $ip): Akismet
    {
        return $this->withParam('user_ip', $ip);
    }

    /**
     * User agent string of the web browser submitting the comment - typically the HTTP_USER_AGENT cgi variable. Not to be confused with the user agent of your Akismet library.
     */
    public function withUserAgent(string $userAgent): Akismet
    {
        return $this->withParam('user_agent', $userAgent);
    }

    /**
     * The content of the HTTP_REFERER header should be sent here.
     */
    public function withReferrer(string $referrer): Akismet
    {
        return $this->withParam('referrer', $referrer);
    }

    /**
     * The full permanent URL of the entry the comment was submitted to.
     */
    public function withPermalink(string $permalink): Akismet
    {
        return $this->withParam('permalink', $permalink);
    }

    /**
     * A string that describes the type of content being sent
     * Examples:
     * comment: A blog comment.
     * forum-post: A top-level forum post.
     * reply: A reply to a top-level forum post.
     * blog-post: A blog post.
     * contact-form: A contact form or feedback form submission.
     * signup: A new user account.
     * message: A message sent between just a few users.
     * You may send a value not listed above if none of them accurately describe your content. This is further explained here: https://blog.akismet.com/2012/06/19/pro-tip-tell-us-your-comment_type/.
     */
    public function withType(string $type): Akismet
    {
        return $this->withParam('comment_type', $type);
    }

    /**
     * Name submitted with the comment.
     */
    public function withAuthorName(string $name): Akismet
    {
        return $this->withParam('comment_author', $name);
    }

    /**
     * Email address submitted with the comment.
     */
    public function withAuthorEmail(string $email): Akismet
    {
        return $this->withParam('comment_author_email', $email);
    }

    /*
     * URL submitted with comment. Only send a URL that was manually entered by the user, not an automatically generated URL like the user’s profile URL on your site.
     */
    public function withAuthorUrl(string $url): Akismet
    {
        return $this->withParam('comment_author_url', $url);
    }

    /**
     * The content that was submitted.
     */
    public function withContent(string $content): Akismet
    {
        return $this->withParam('comment_content', $content);
    }

    /**
     * The UTC timestamp of the creation of the comment, in ISO 8601 format. May be omitted for comment-check requests if the comment is sent to the API at the time it is created.
     */
    public function withDateGmt(string $date): Akismet
    {
        return $this->withParam('comment_date_gmt', $date);
    }

    /**
     * The UTC timestamp of the publication time for the post, page or thread on which the comment was posted.
     */
    public function withPostModifiedDateGtm(string $date): Akismet
    {
        return $this->withParam('comment_post_modified_gmt', $date);
    }

    /**
     * Indicates the language(s) in use on the blog or site, in ISO 639-1 format, comma-separated. A site with articles in English and French might use “en, fr_ca”.
     */
    public function withLanguage(string $language): Akismet
    {
        return $this->withParam('blog_lang', $language);
    }

    /**
     * This is an optional parameter. You can use it when submitting test queries to Akismet.
     */
    public function withTest(): Akismet
    {
        return $this->withParam('is_test', true);
    }

    /**
     * If you are sending content to Akismet to be rechecked, such as a post that has been edited or old pending comments that you’d like to recheck, include the parameter recheck_reason with a string describing why the content is being rechecked. For example, edit.
     */
    public function withRecheckReason(string $reason): Akismet
    {
        return $this->withParam('recheck_reason', $reason);
    }
}
