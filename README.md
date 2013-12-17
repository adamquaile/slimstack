slimstack
===

Tiny front-controller offering very small and light routing.

* This is built for very specific purposes. You might consider similar systems such as <a href='silex.sensiolabs.org'>Silex</a> or <a href='http://www.slimframework.com/'>Slim</a> which offer more features and flexibility. *

Getting the code.
-----

This can be installed in your project with `composer require adamquaile/slimstack`

Creating simple front-controllers / bootstrap files
-------

    require __DIR__ . '/../vendor/autoload.php';

    $app = new \AdamQuaile\SlimStack\App();

    // Simple dummy GET request
    $app->get('/test/:name', function($name) {

        $data = [
            "name" => $name,
            "ids" => [1,2,3]
        ];

        $response = new Response(json_encode($data));
        return $response;

    });

    $app->run();


