<?php

class tiDefaultRouting implements tiRoutingIf
{
    protected $routes;

    public function __construct()
    {
        $this->routes = array();
    }

    public function generate($name, $params)
    {
        if($name) {
            if(!isset($this->routes[$name])) {
                return false;
            }
            $route = $this->routes[$name];
        } else {
            $route = null;
            foreach($this->routes AS $troute) {
                if($troute->matchParameters($params)) {
                    $route = $troute;
                    break;
                }
            }

            if($route == null) {
                return false;
            }
        }

        $url = $route->generete($params);

        return $url;
    }

    public function parse($url)
    {
        $params = null;
        foreach($this->routes AS $r) {
            $tparams = $r->matchesUrl($url);
            if($tparams === false) {
                continue;
            }

            $params = $tparams;
            break;
        }

        if($params == null) {
            return false;
        } else {
            return $params;
        }
    }

    public function set($name, tiRoute $route)
    {
        if(is_null($route)) {
            throw new InvalidArgumentException('tiDefaultRouting->add: $route was null!');
        }

        $this->routes[$name] = $route;
    }
}