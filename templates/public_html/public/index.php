<?php

$q = rtrim($_SERVER['REQUEST_URI'], '/');
$q = substr($q, 1);

echo 'query: ' . $q . '<br/>';