<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Container\ContainerInterface as ContainerInterface;
use Slim\Routing\RouteContext;
use Slim\Routing\RouteCollector;

// grab the current route for pathFor to use later
// will use it to get the current route name and use it as a default if one isn't provided
// will also use it to supply the current route args as defaults for any links where they aren't provided
class pathForMiddleware {
  public $Container;

  function __construct(ContainerInterface $Container) {
    $this->Container = $Container;
  }

  function __invoke(Request $Req, RequestHandler $Handler) {
    $pathFor = $this->Container->get('pathFor');
    $pathFor->CurrentRoute = RouteContext::fromRequest($Req)->getRoute();
    $pathFor->Request = $Req;
    return $Handler->handle($Req);
  }
}

class pathFor {
  public $RouteCollector;
  public $StdRouteParser;
  public $CurrentRoute;
  public $Request;

  function __construct(ContainerInterface $Container, RouteCollector $RouteCollector) {
    $this->StdRouteParser = new FastRoute\RouteParser\Std();
    $this->RouteCollector = $RouteCollector;
  }

  function __invoke($name, $params=[]) {
    // if a route name wasn't provided, use the current route name if we can
    if (!$name) {
      if ($this->CurrentRoute) $name = $this->CurrentRoute->getName();
      else return '#';
    }
    $Route = $this->RouteCollector->getNamedRoute($name);
    if (! $Route) return '#';
    
    // if the current route has any args, use them as defaults for all links we generate
    $neededArgs = $this->getNeededArguments($Route);
    if ($this->CurrentRoute) {
      $current_args = $this->CurrentRoute->getArguments();
      $current_args = array_intersect_key($current_args, array_flip($neededArgs));
      $params = array_merge($current_args, $params);
    }

    // if keep_current_params is set, grab any query params and use them as defaults
    // except for those excluded with exclude_params
    if (@$params['keep_current_params'] && $this->Request) {
      $exclude = @explode(',', strval($params['exclude_params']));
      unset($params['keep_current_params']);
      unset($params['exclude_params']);
      $params = array_merge($this->Request->getQueryParams(), $params);
      if ($exclude) $params = array_diff_key($params, array_flip($exclude));
    }

    $prefix = '';
    if (@$params['pathfor_fullpath']) {
      unset($params['pathfor_fullpath']);
      $prefix = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
    }

    // args are placeholders in the url, part of the route
    // query params go after the url, like ?key=value
    $args = $query = [];
    if ($params) {
      $args = array_intersect_key($params, array_flip($neededArgs));
      $query = array_diff_key($params, $args);
    }

    $SlimRouteParser = $this->RouteCollector->getRouteParser();
    return $prefix . $SlimRouteParser->urlFor($name, $args, $query);
  }

  function getNeededArguments($Route) {
    $neededArgs = [];

    $routeDatas = $this->StdRouteParser->parse($Route->getPattern());
    foreach ($routeDatas as $routeData) {
      foreach ($routeData as $item) {
        if (! is_array($item)) continue;
        $neededArgs[] = $item[0];
      }
    }

    return $neededArgs;
  }
}