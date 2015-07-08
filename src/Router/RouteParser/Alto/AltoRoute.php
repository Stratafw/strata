<?php
namespace Strata\Router\RouteParser\Alto;

use AltoRouter;
use Strata\Router\RouteParser\Route;

use Strata\Controller\Controller;
use Strata\Controller\Request;
use Strata\Model\Model;

use Strata\Utility\Hash;
use Strata\Utility\Inflector;

use Exception;

class AltoRoute extends Route
{
    /**
     * Altorouter is the library that does the heavy lifting for us.
     * @var AltoRouter
     */
    private $altoRouter = null;

    const DYNAMIC_PARSE = "__strata_dynamic_parse__";
    const RESOURCE = "resources";

    public function __construct()
    {
        $this->altoRouter = new AltoRouter();
    }

    /**
     * {@inheritdoc}
     */
    public function addPossibilities($routes)
    {

        foreach ($routes as $route) {
            $this->parseRouteConfig($route);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $match = $this->altoRouter->match();

        if (!is_array($match)) {
            return;
        }

        $this->handleRouterAnswer($match);
    }

    private function parseRouteConfig($route)
    {
        if (!is_array($route)) {
            throw new Exception("Strata configuration file contains an invalid route.");
        }

        if ($this->isResourced($route)) {
            $this->parseResourceRoute($route);
        } elseif ($this->isDynamic($route)) {
            $this->parseDynamicRoute($route);
        } else {
            $this->parseMatchedRoute($route);
        }
    }

    private function isResourced($route)
    {
        return array_key_exists(self::RESOURCE, $route);
    }

    private function isDynamic($route)
    {
        return count($route) < 3;
    }

    private function parseResourceRoute($route)
    {
        foreach (Hash::normalize($route[self::RESOURCE]) as $customPostType => $config) {
            $model = Model::factory($customPostType);

            $slug = Hash::check($model->configuration, "rewrite.slug")
                ? Hash::get($model->configuration, "rewrite.slug")
                : $model->getWordpressKey();

            $controller = Controller::generateClassName($slug);

            $this->parseMatchedRoute(array('GET|POST|PATCH|PUT|DELETE', "/$slug/?", "$controller#index"));
            $this->parseMatchedRoute(array('GET|POST|PATCH|PUT|DELETE', "/$slug/[.*]/?", "$controller#show"));
        }
    }

    private function parseDynamicRoute($route)
    {
        $this->parseMatchedRoute(array($route[0], $route[1], self::DYNAMIC_PARSE));
    }

    private function parseMatchedRoute($route)
    {
        $route = $this->patchBuiltInServerPrefix($route);
        $this->altoRouter->map($route[0], $route[1], $route[2]);
    }

    private function handleRouterAnswer($match)
    {
        if ($match["target"] === self::DYNAMIC_PARSE) {
            $this->handleDynamicRouterAnswer($match);
        } else {
            $this->handleMatchedRouterAnswer($match);
        }
    }

    private function handleDynamicRouterAnswer($match)
    {
        $this->controller   = $this->getControllerFromDynamicMatch($match);
        $this->action       = $this->getActionFromDynamicMatch($match);
        $this->arguments    = $this->getArgumentsFromDynamicMatch($match);
    }

    private function handleMatchedRouterAnswer($match)
    {
        $this->controller   = $this->getControllerFromMatch($match);
        $this->action       = $this->getActionFromMatch($match);
        $this->arguments    = $this->getArgumentsFromMatch($match);
    }

    private function getControllerFromMatch($match = array())
    {
        $target = explode("#", $match["target"]);
        return Controller::factory($target[0]);
    }

    private function getControllerFromDynamicMatch($match = array())
    {
        try {
            if (array_key_exists("controller", $match["params"])) {
                return Controller::factory($match["params"]["controller"]);
            }
        } catch (Exception $e) {
            // The controller did not exist, we don't care at this point.
            // We'll just ignore the route.
        }

        return Controller::factory("App");
    }

    private function getActionFromMatch($match = array())
    {
        $target = explode("#", $match["target"]);

        if (count($target) > 1) {
            return $target[1];
        }

        $this->controller->request = new Request();
        if (is_admin() && $this->controller->request->hasGet('page')) {
            return $this->controller->request->get('page');
        // When no method is sent, guesstimate from the action post value (basic ajax)
        } elseif ($this->controller->request->hasPost('action')) {
            return $this->controller->request->post('action');
        }
    }

    private function getActionFromDynamicMatch($match)
    {
        if (array_key_exists("action", $match["params"])) {
            $action = $match["params"]["action"];
            $action = str_replace("-", "_", $action);
            return lcfirst(Inflector::camelize($action));
        }

        return "index";
    }

    private function getArgumentsFromMatch($match = array())
    {
        if (is_array($match['params']) && count($match['params'])) {
            $params = $match['params'];
            return is_array($params) ? $params : array($params);
        }

        return array();
    }

    private function getArgumentsFromDynamicMatch($match = array())
    {
        if (array_key_exists("params", $match["params"])) {
            $params = $match["params"]["params"];
            return is_array($params) ? $params : array($params);
        }

        return array();
    }

    // Built in server will generate links with index.php because
    // it doesn't have access to mod_reqrite
    private function patchBuiltInServerPrefix($route)
    {
            if (!preg_match("/^\/index.php/i", $route[1])) {
                $route[1] = "(/index.php)?" . $route[1];
            }

        return $route;
    }
}