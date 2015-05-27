<?php

namespace Flarum\Http;

interface UrlGeneratorInterface
{
    public function toRoute($name, $parameters = []);

    public function toAsset($path);
}
