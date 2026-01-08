<?php

namespace Velocix\Http;

abstract class Middleware
{
    abstract public function handle(Request $request, $next);
}