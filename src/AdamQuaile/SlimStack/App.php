<?php

namespace AdamQuaile\SlimStack;

use Pimple;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class App
{
    /**
     * @var Pimple
     */
    private $container;

    private $responseSent = false;

    private $autoMatch = false;

    /**
     * @var string The URL, after normalisation, that will be matched against patterns
     */
    private $urlToMatch;

    /**
     * @var string[]
     */
    private $routes = array(
        'GET'       => [],
        'POST'      => [],
        'PUT'       => [],
        'DELETE'    => [],
        'HEAD'      => [],


    );

    /**
     * @var Request
     */
    private $request;

    public function __construct()
    {
        $this->container = new \Pimple();
        $this->request = Request::createFromGlobals();

        $this->urlToMatch = substr($this->request->server->get('REQUEST_URI'), strlen($this->request->server->get('SCRIPT_NAME')));
    }

    /**
     * If auto-match set to true, matching will occur every time
     * get(), put(), delete(), etc.. is called, no further routes will
     * be matched.
     *
     * This is for performance reasons, if you need to test matching, this
     * should be turned off.
     *
     * @param boolean $autoMatch
     */
    public function setAutoMatch($autoMatch)
    {
        $this->autoMatch = $autoMatch;
    }

    public function get($pattern, callable $func)
    {
        $this->map('GET', $pattern, $func);
        return $this;
    }
    public function post($pattern, callable $func)
    {
        $this->map('POST', $pattern, $func);
        return $this;
    }
    public function put($pattern, callable $func)
    {
        $this->map('PUT', $pattern, $func);
        return $this;
    }

    public function head($pattern, callable $func)
    {
        $this->map('HEAD', $pattern, $func);
        return $this;
    }

    public function map($method, $pattern, callable $func) {

        // Don't do anything else if we've already returned content
        if ($this->responseSent) {
            return;
        }
        if ($this->autoMatch) {

            if ($this->request->getMethod() != $method) {
                return;
            }

            $arguments = $this->matchURL($pattern, $this->urlToMatch);
            if (false !== $arguments) {
                $response = call_user_func_array($func, $arguments);
                $this->outputResponse($response);
                return;
            }
        }
        $this->routes[$method][$pattern] = $func;

    }




    public function run()
    {
        $response = null;

        if ($this->autoMatch) {

            // If we're auto-matching and we haven't found anything, return 404
            if (!$this->responseSent) {
                $this->outputResponse(null);
            }
            return;
        } else {

            // We're not auto-matching, try and go through all the routes now.

            $requestURL = $this->urlToMatch;

            $possibleRoutes = $this->routes[$this->request->getMethod()];

            foreach ($possibleRoutes as $pattern => $func) {
                $arguments = $this->matchURL($pattern, $requestURL);

                if (false !== $arguments) {
                    $response = call_user_func_array($func, $arguments);
                    break;

                }

            }

            $this->outputResponse($response);
            return;

        }

    }

    private function outputResponse(Response $response=null)
    {
        if ($this->responseSent) {
            throw new \LogicException('Response already sent');
        }

        if (is_null($response)) {
            $response = $this->get404Response();
        }
        $response->send();
        $this->responseSent = true;

    }




    /**
     * Test an URL against a pattern, returns boolean false or
     * array of matched arguments
     *
     * @param string $pattern
     * @param string $url
     *
     * @return array|false $arguments
     */
    private function matchURL($pattern, $url)
    {
        $patternLength = strlen($pattern);

        $foundStartCharacter = false;
        $foundEndCharacter = false;

        $argumentStartIndex = 0;
        $argumentEndIndex = 0;

        $arguments = [];

        $pregURL = '^';

        for ($i=0;$i<$patternLength;$i++) {

            $char = $pattern[$i];
            if (':' == $char) {

                $foundStartCharacter = true;
                $argumentStartIndex = $i + 1;
                continue;
            }
            if ($foundStartCharacter) {

                // Entering matching mode
                if (!ctype_alnum($char) || ($i == $patternLength-1))  {

                    // End matching mode
                    $foundEndCharacter = true;
                    $argumentEndIndex = $i;
                }

            } else {

                $pregURL .= $char;
            }

            if ($foundStartCharacter && $foundEndCharacter) {

                $arguments[] = substr($pattern, $argumentStartIndex, $argumentEndIndex);
                $pregURL .= '([a-z0-9]*)';

                // Reset for next iteration
                $foundStartCharacter = false;
                $foundEndCharacter = false;
            }
        }
        $pregURL .= '$^i';

        $matches = [];
        if (preg_match($pregURL, $url, $matches)) {
            unset($matches[0]);
            return array_combine($arguments, $matches);
        } else {
            return false;
        }

    }

    /**
     * @return \Pimple
     */
    public function getContainer()
    {
        return $this->container;
    }


    private function get404Response()
    {
        return new Response('Page Not found', 404);
    }
}