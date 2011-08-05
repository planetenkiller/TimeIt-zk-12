<?php

interface tiRoutingIf
{
    public function generate($name, $params);

    public function parse($url);
}