<?php
/**
 * Laravel Global Helper Functions for IDE Support
 * This file should not be included in your code, only analyzed by your IDE!
 */

if (false) {
    /**
     * Get the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function env($key, $default = null) {
        return \Illuminate\Support\Env::get($key, $default);
    }

    /**
     * Get / set the specified configuration value.
     *
     * @param  array|string|null  $key
     * @param  mixed  $default
     * @return mixed|\Illuminate\Config\Repository
     */
    function config($key = null, $default = null) {
        if (is_null($key)) {
            return app('config');
        }

        if (is_array($key)) {
            return app('config')->set($key);
        }

        return app('config')->get($key, $default);
    }

    /**
     * Get the application instance.
     *
     * @param  string|null  $abstract
     * @param  array  $parameters
     * @return \Illuminate\Contracts\Foundation\Application|mixed
     */
    function app($abstract = null, array $parameters = []) {
        if (is_null($abstract)) {
            return \Illuminate\Container\Container::getInstance();
        }

        return \Illuminate\Container\Container::getInstance()->make($abstract, $parameters);
    }

    /**
     * Get the base path of the Laravel installation.
     *
     * @param  string  $path
     * @return string
     */
    function base_path($path = '') {
        return app()->basePath($path);
    }

    /**
     * Get the storage path.
     *
     * @param  string  $path
     * @return string
     */
    function storage_path($path = '') {
        return app('path.storage').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Get the path to the public folder.
     *
     * @param  string  $path
     * @return string
     */
    function public_path($path = '') {
        return app()->publicPath().($path ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : $path);
    }

    /**
     * Generate the URL to a named route.
     *
     * @param  array|string  $name
     * @param  mixed  $parameters
     * @param  bool  $absolute
     * @return string
     */
    function route($name, $parameters = [], $absolute = true) {
        return app('url')->route($name, $parameters, $absolute);
    }

    /**
     * Generate a URL to an asset.
     *
     * @param  string  $path
     * @param  bool|null  $secure
     * @return string
     */
    function asset($path, $secure = null) {
        return app('url')->asset($path, $secure);
    }

    /**
     * Generate a URL to a secure asset.
     *
     * @param  string  $path
     * @return string
     */
    function secure_asset($path) {
        return asset($path, true);
    }

    /**
     * Generate the URL to a named route.
     *
     * @param  string  $path
     * @param  mixed  $parameters
     * @param  bool|null  $secure
     * @return string
     */
    function url($path = null, $parameters = [], $secure = null) {
        if (is_null($path)) {
            return app(\Illuminate\Contracts\Routing\UrlGenerator::class);
        }

        return app(\Illuminate\Contracts\Routing\UrlGenerator::class)->to($path, $parameters, $secure);
    }

    /**
     * Get the fully qualified class name for a given view.
     *
     * @param  string  $view
     * @param  array  $data
     * @param  array  $mergeData
     * @return \Illuminate\Contracts\View\View
     */
    function view($view = null, $data = [], $mergeData = []) {
        $factory = app(\Illuminate\Contracts\View\Factory::class);

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($view, $data, $mergeData);
    }

    /**
     * Get the cache instance.
     *
     * @param  array|string|null  $key
     * @param  mixed  $default
     * @return mixed|\Illuminate\Cache\CacheManager
     */
    function cache($key = null, $default = null) {
        if (is_null($key)) {
            return app('cache');
        }

        if (is_array($key)) {
            return app('cache')->put(key($key), reset($key), $default);
        }

        return app('cache')->get($key, $default);
    }

    /**
     * Get a log writer instance.
     *
     * @param  string|null  $driver
     * @return \Illuminate\Log\LogManager|\Psr\Log\LoggerInterface
     */
    function logger($message = null, array $context = []) {
        if (is_null($message)) {
            return app('log');
        }

        return app('log')->debug($message, $context);
    }

    /**
     * Create a new redirect response to the given path.
     *
     * @param  string  $to
     * @param  int  $status
     * @param  array  $headers
     * @param  bool|null  $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    function redirect($to = null, $status = 302, $headers = [], $secure = null) {
        if (is_null($to)) {
            return app('redirect');
        }

        return app('redirect')->to($to, $status, $headers, $secure);
    }

    /**
     * Return a new response from the application.
     *
     * @param  \Illuminate\Contracts\View\View|string|array|null  $content
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    function response($content = '', $status = 200, array $headers = []) {
        $factory = app(\Illuminate\Contracts\Routing\ResponseFactory::class);

        if (func_num_args() === 0) {
            return $factory;
        }

        return new \Illuminate\Http\Response($content, $status, $headers);
    }

    /**
     * Get the request instance.
     *
     * @param  array|string|null  $key
     * @param  mixed  $default
     * @return \Illuminate\Http\Request|string|array|null
     */
    function request($key = null, $default = null) {
        if (is_null($key)) {
            return app('request');
        }

        if (is_array($key)) {
            return app('request')->only($key);
        }

        $value = app('request')->__get($key);

        return is_null($value) ? value($default) : $value;
    }

    /**
     * Get the session instance.
     *
     * @param  array|string|null  $key
     * @param  mixed  $default
     * @return \Illuminate\Session\SessionManager|\Illuminate\Session\Store|mixed
     */
    function session($key = null, $default = null) {
        if (is_null($key)) {
            return app('session');
        }

        if (is_array($key)) {
            return app('session')->put($key);
        }

        return app('session')->get($key, $default);
    }
}
