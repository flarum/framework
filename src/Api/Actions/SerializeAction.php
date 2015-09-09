<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Actions;

use Flarum\Events\BuildApiAction;
use Flarum\Events\WillSerializeData;
use Flarum\Api\Request;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Criteria;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\SerializerInterface;
use Zend\Diactoros\Response\JsonResponse;

abstract class SerializeAction implements Action
{
    /**
     * The name of the serializer class to output results with.
     *
     * @var string
     */
    public $serializer;

    /**
     * The relationships that are available to be included (keys), and which
     * ones are included by default (boolean values).
     *
     * @var array
     */
    public $include = [];

    /**
     * The relationships that are linked by default.
     *
     * @var array
     */
    public $link = [];

    /**
     * The maximum number of records that can be requested.
     *
     * @var integer
     */
    public $limitMax = 50;

    /**
     * The number of records included by default.
     *
     * @var integer
     */
    public $limit = 20;

    /**
     * The fields that are available to be sorted by.
     *
     * @var array
     */
    public $sortFields = [];

    /**
     * The default sort field and order to user.
     *
     * @var string
     */
    public $sort;

    /**
     * Handle an API request and return an API response.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request)
    {
        $request = $this->buildJsonApiRequest($request);
        $document = new Document();

        $data = $this->data($request, $document);

        event(new WillSerializeData($this, $data, $request));

        $serializer = new $this->serializer($request->actor, $request->include, $request->link);

        $document->setData($this->serialize($serializer, $data));

        return new JsonResponse($document, 200, ['content-type' => 'application/vnd.api+json']);
    }

    /**
     * Get the data to be serialized and assigned to the response document.
     *
     * @param JsonApiRequest $request
     * @param Document $document
     * @return array
     */
    abstract protected function data(JsonApiRequest $request, Document $document);

    /**
     * Serialize the data as appropriate.
     *
     * @param SerializerInterface $serializer
     * @param array $data
     * @return \Tobscure\JsonApi\Elements\ElementInterface
     */
    abstract protected function serialize(SerializerInterface $serializer, $data);

    /**
     * Extract parameters from the request input and assign them to the
     * request, restricted by the action's specifications.
     *
     * @param Request $request
     * @return JsonApiRequest
     */
    protected function buildJsonApiRequest(Request $request)
    {
        $request = new JsonApiRequest($request->input, $request->actor, $request->http);

        $criteria = new Criteria($request->input);

        event(new BuildApiAction($this));

        $request->include = $this->sanitizeInclude($criteria->getInclude());
        $request->sort = $this->sanitizeSort($criteria->getSort());
        $request->offset = $criteria->getOffset();
        $request->limit = $this->sanitizeLimit($criteria->getLimit());
        $request->link = $this->link;

        return $request;
    }

    /**
     * Sanitize an array of included relationships according to the action's
     * configuration.
     *
     * @param array $include
     * @return array
     */
    protected function sanitizeInclude(array $include)
    {
        return array_intersect($include, array_keys($this->include)) ?: array_keys(array_filter($this->include));
    }

    /**
     * Sanitize an array of sort criteria according to the action's
     * configuration.
     *
     * @param array $sort
     * @return array
     */
    protected function sanitizeSort(array $sort)
    {
        return array_intersect_key($sort, array_flip($this->sortFields)) ?: $this->sort;
    }

    /**
     * Sanitize a limit according to the action's configuration.
     *
     * @param int $limit
     * @return int
     */
    protected function sanitizeLimit($limit)
    {
        return min($limit, $this->limitMax) ?: $this->limit;
    }

    /**
     * Add pagination links to a JSON-API response, based on input parameters
     * and the default parameters of this action.
     *
     * @param Document $document
     * @param JsonApiRequest $request
     * @param string $url The base URL to build pagination links with.
     * @param integer|boolean $total The total number of results (used to build
     *     a 'last' link), or just true if there are more results but how many
     *     is unknown ('last' link is ommitted).
     * @return void
     */
    protected function addPaginationLinks(Document $document, JsonApiRequest $request, $url, $total = true)
    {
        $input = [];
        if ($request->limit != $this->limit) {
            array_set($input, 'page.limit', $request->limit);
        }

        array_set($input, 'page.offset', 0);
        $document->addLink('first', $url.'?'.http_build_query($input));

        if ($request->offset > 0) {
            array_set($input, 'page.offset', max(0, $request->offset - $request->limit));
            $document->addLink('prev', $url.'?'.http_build_query($input));
        }

        if ($total === true || $request->offset + $request->limit < $total) {
            array_set($input, 'page.offset', $request->offset + $request->limit);
            $document->addLink('next', $url.'?'.http_build_query($input));
        }

        if ($total && $total !== true) {
            array_set($input, 'page.offset', $total - $request->limit);
            $document->addLink('last', $url.'?'.http_build_query($input));
        }
    }
}
