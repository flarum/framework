<?php namespace Flarum\Api\Middleware;

use Flarum\Core\Models\AccessToken;
use Flarum\Support\Actor;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;

class LoginWithHeader implements MiddlewareInterface
{
    /**
     * @var Actor
     */
    protected $actor;

    /**
     * @var string
     */
    protected $prefix = 'Token ';

    // @todo rather than using a singleton, we should have our own HTTP
    //     Request class and store the actor on that? somehow?
    public function __construct(Actor $actor)
    {
        $this->actor = $actor;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $header = $request->getHeaderLine('authorization');
        if (starts_with($header, $this->prefix) &&
            ($token = substr($header, strlen($this->prefix))) &&
            ($accessToken = AccessToken::where('id', $token)->first())
        ) {
            $this->actor->setUser($user = $accessToken->user);

            $user->updateLastSeen()->save();
        }

        return $out ? $out($request, $response) : $response;
    }
}
