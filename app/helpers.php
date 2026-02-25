<?php

if (! function_exists('greet_user')) {
           function greet_user($name)
    {
        return "Hello, " . ucfirst($name);
    }
}