<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\ExceptionHandler;

use Exception;
use Psr\Log\LoggerInterface;
use Tobscure\JsonApi\Exception\Handler\ExceptionHandlerInterface;
use Tobscure\JsonApi\Exception\Handler\ResponseBag;

class FallbackExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param bool $debug
     * @param LoggerInterface $logger
     */
    public function __construct($debug, LoggerInterface $logger)
    {
        $this->debug = $debug;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function manages(Exception $e)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Exception $e)
    {
        $status = 500;
        $error = $this->constructError($e, $status);

        $this->logger->error($e);

        return new ResponseBag($status, [$error]);
    }

    /**
     * @param Exception $e
     * @param $status
     * @return array
     */
    private function constructError(Exception $e, $status)
    {
        $error = ['code' => $status, 'title' => 'Internal server error'];

        if ($this->debug) {
            $error['detail'] = (string) $e;
        }

        return $error;
    }
}
