<?php

class tiRoute
{
    protected $urlpattern;
    protected $defaults;
    protected $requirements;

    protected $compiled;
    protected $regex;
    protected $variables;

    protected $tokens;

    public function __construct($urlpattern, $defaults, $requirements)
    {
        $this->compiled = false;
        $this->variables = array();

        if(substr($urlpattern, -1) != '/') {
            $urlpattern .= '/';
        }
        $urlpattern .= '*';
        $this->urlpattern = $urlpattern;
        $this->defaults = $defaults;
        $this->requirements = $requirements;
    }

    public function generete($params)
    {
        if(!$this->compiled) {
            $this->compile();
        }

        $tparams = array_merge($this->defaults, $params);

        $diff = array_diff_key(array_flip($this->variables), $tparams);
        if($diff) {
            throw new InvalidArgumentException('The "'.$this->urlpattern.'" route has some missing mandatory parameters ('.implode(',', $diff).').');
        }

        $url = '';
        foreach($this->tokens AS $token) {
            switch ($token[0]) {
                case 'variable':
                    $url .= urlencode($tparams[$token[1]]);
                    break;

                case 'text':
                    if($token[1] != '*') {
                        $url .= $token[1];
                    }
                    break;
                case 'separator':
                    $url .= $token[1];
                    break;
            }
        }

        if(substr($url, -1) == '/') {
            $url = substr($url, 0, strlen($url)-1);
        }

        if (false !== strpos($this->regex, '<_star>')) {
            $tmp = array();
            foreach (array_diff_key($tparams, array_flip($this->variables), $this->defaults) as $key => $value) {
               $tmp[] = urlencode($key).'/'.urlencode($value);
            }
            $url .= '/'.implode('/', $tmp);
        }

        return $url;
    }

    public function matchesUrl($url)
    {
        if(!$this->compiled) {
            $this->compile();
        }

        if(!preg_match($this->regex, $url, $matches)) {
            return false;
        }

        $parameters = array();

        // * in urlpattern
        if(isset($matches['_star'])) {
            $tmp = explode('/', $matches['_star']);
            for($i=0,$max=count($tmp); $i < $max; $i+=2) {
                if(!empty($tmp[$i])) {
                    $parameters[$tmp[$i]] = isset($tmp[$i+1])? $tmp[$i+1] : true;
                }
            }

            unset($matches['_star']);
        }

        $parameters = array_merge($parameters, $this->defaults);

        // variables
        foreach($matches AS $key => $value) {
            if(!is_int($key)) {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }

    public function matchParameters($params) {
        if(!$this->compiled) {
            $this->compile();
        }

        if (!is_array($params)) {
            return false;
        }

        $tparams = array_merge($this->defaults, $params);

        // all $variables must be defined in the $tparams array
        if(array_diff_key(array_flip($this->variables), $tparams)) {
            return false;
        }

        // check requirements
        foreach($this->variables AS $variable) {
            // no value no check
            if(!$tparams[$variable]) {
                continue;
            }

            if(!preg_match('#'.$this->requirements[$variable].'#', $tparams[$variable])) {
                return false;
            }
        }

        // check that $params does not override a default value that is not a variable
        foreach ($this->defaults as $key => $value) {
            if (!isset($this->variables[$key]) && $tparams[$key] != $value) {
                return false;
            }
        }

        return true;
    }

    protected function compile()
    {
        if(!$this->compiled) {
            // parse pattern
            $pattern = $this->urlpattern;
            while(strlen($pattern)) {
                // variable
                if(preg_match('#^:([a-zA-z0-6_]+)#', $pattern, $match)) {
                    $name = $match[1];
                    $this->tokens[] = array('variable', $name);
                    $this->variables[] = $name;

                    $pattern = substr($pattern, strlen($match[0]));
                // separator
                } else if(preg_match('#^(?:/|\.|\-)#', $pattern, $match)) {
                    $this->tokens[] = array('separator', $match[0]);

                    $pattern = substr($pattern, strlen($match[0]));
                // text
                } else if(preg_match('#^(.+?)(?:(?:/|\.|\-)|$)#', $pattern, $match)) {
                    $text = $match[1];
                    $this->tokens[] = array('text', $text);

                    $pattern = substr($pattern, strlen($match[1]));
                } else {
                    throw new InvalidArgumentException('Invalid pattern "'.$this->urlpattern.'" near "'.$pattern.'"!');
                }
            }

            // create regex
            $regex = '#';
            for($i=0,$max=count($this->tokens); $i < $max; $i++) {
                $token = $this->tokens[$i];
                if($token[0] == 'variable') {
                    if(!isset($this->requirements[$token[1]])) {
                        $this->requirements[$token[1]] = '[^/\.\-]+';
                    }
                    $regex .= '(?P<'.$token[1].'>'.$this->requirements[$token[1]].')';
                } else if($token[0] == 'text' || $token[0] == 'separator') {
                    if($token[1] == '*') {
                        if($this->tokens[$i-1] && $this->tokens[$i-1][0] == 'separator') {
                            $sep_regex = $this->tokens[$i-1][1];
                        } else {
                            $sep_regex = '/';
                        }
                        $regex .= '(?:'.$sep_regex.'(?P<_star>.*))?';
                    } else {
                        if($token[0] == 'separator' && $this->tokens[$i+1] && $this->tokens[$i+1][1] == '*') {

                        } else {
                            $regex .= $token[1];
                        }
                    }
                }
            }
            $regex .= '#';
            $this->regex = $regex;

            $this->compiled = true;
        }
    }
}