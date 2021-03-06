# Custom Routing for WordPress
Inspired by [Themosis Routing](https://framework.themosis.com/docs/1.3/routing/) which in turns uses [Laravel's Routing](https://laravel.com/docs/5.8/routing) this WP Router abstraction adds support for adding custom routes and callbacks on WordPress' routes while using WordPress functions ([`add_rewrite_rule`](https://codex.wordpress.org/Rewrite_API/add_rewrite_rule), [`add_rewrite_tag`](https://codex.wordpress.org/Rewrite_API/add_rewrite_tag)) and without the need of adding third-party routers.

Developed in collaboration with [@tatundkraft](https://github.com/tatundkraft).

## Installation
Install this package via [composer](https://getcomposer.org):

```bash
php composer.phar require alpipego/awp-router
```

Initialise the dispatcher:

```php
$dispatcher = new \Alpipego\AWP\Router\Dispatcher();
```

The dispatcher takes a `CustomRouterInterface`, a `TemplateRouterInterface`, and a `AjaxRouterInterface` as optional arguments, if none are provided, the default Routers are initialised. 

## Custom Routes
```php
$dispatcher->custom->get('/my-custom-route', function(WP_Query $query) {
    // do something with your query
});
``` 

The previous example adds a custom route `my-custom-route` that only responds to `GET` (and `HEAD`) request, and passes back a `WP_Query` object to your callback.

### Methods
At the moment the methods `GET`, `POST`, and `HEAD` are implemented; custom routes can be added via the following methods:

* `head` (matches `HEAD` requests): `head(string $route, callable $callable)`
* `get` (matches `GET` and `HEAD` requests): `get(string $route, callable $callable)`
* `post` (matches `POST` requests): `post(string $route, callable $callable)`
* `any` (matches `GET`, `POST`, and `HEAD` requests): `any(string $route, callable $callable)`
* `match` methods get passed as first argument: `match(array $methods, string $route, callable $callable)` 

and `match` which takes possible methods as a first parameter:
```php
// match(array $methods, string $route, callable $callable)
$dispatcher->custom->match(['GET', 'POST'], '/true-get', function(WP_Query $query) {
    // ...
});
```

### Variables
Variables can be either matched and thus be used as [`query_vars`](https://codex.wordpress.org/Function_Reference/get_query_var) or be non-matching and only act as a way of validating the route. 

Variables are added in the following form `{VARNAME:REGEX}` and you can leave out either `VARNAME` or the `REGEX`. 

In the following example `form_id` and `input_name` are passed back as `query_vars` in the `$query` object. The last regex simply matches any three characters, e.g. `abc` but not `abcd` (and does not add a query var): 

```php
$dispatcher->custom->post('/forms/{form_id:\d+}/input/{input_name}/{[a-z]{3}}', function(WP_Query $query) {
    // ...
});
```

*Notes*:
* These variables are "private" and can therefore not be used in a URL query string; cf. [Public vs. Private query vars](https://codex.wordpress.org/WordPress_Query_Vars#Public_vs._Private_query_vars)

### Rewrite Tags
Already registered query vars (either [built-in public vars](https://codex.wordpress.org/WordPress_Query_Vars#List_of_Query_Vars) or registered through plugins et al.) can be used in the route, and are registered with leading and trailing `%`, i.e. `{%QUERYVAR%:REGEX}`:

```php
$dispatcher->custom->post('/pages/{%page_id%}/fields/{%field_id%:\d+}', function(WP_Query $query) {
    // ...
});
```

This will match the built-in `page_id` query var and add a custom `field_id` query variable to `WP_Query`.
 
 *Notes*:
 * All rewrite tags are "public query vars" and can be used in a URL query string;  cf. [Public vs. Private query vars](https://codex.wordpress.org/WordPress_Query_Vars#Public_vs._Private_query_vars)
 * Be careful when adding a regular expression to an already defined rewrite tag, as this will override the previous regex.

### Redirects
The `RouterInterface` has a method to add redirects:

```php
// redirect(string $route, string $target, array $methods = ['GET', 'HEAD'], int $status = 308)
$dispatcher->custom->redirect('/twitter/{twitter_user}', 'https://twitter.com/{twitter_user}');
```

This route will redirect `https://YOURSITE.com/twitter/alpipego` to https://twitter.com/alpipego

Of course, redirects can also have regular expressions and rewrite tags in the route.

```php
$dispatcher->custom->redirect('/attachment/{%attachment_id%}', 'https://external-attachment-handler.com/{attachment_id}');
```

*Notes*:
* By default only `GET` and `HEAD` requests get redirected
* The default redirect code is `308` making sure the HTTP method gets redirected properly 
### Callbacks
You can pass any `callable` as a callback. 

The callback will receive the current `WP_Query` object as the first parameter, and the following return types can be handled:

* If the callback returns `false` propagation is stopped, and the request ends (i.e., no template gets rendered).
* The callback may return the full path to a valid template file (basically any PHP file), which then gets required.
* If the callback does not have a return value, WordPress' default [template loading and hierarchy](https://developer.wordpress.org/themes/basics/template-hierarchy/) kicks in.

## Template Routing

### Conditionals
The `condition` method takes a callable that returns a boolean value as its first argument and a callback to be executed right before rendering the template if the condition is true as its second argument. Both callbacks receive the current `WP_Query` to act on, the callable also receives the full path to the template file. See the [section on callbacks](#callbacks) on how they are handled.

```php
// public function condition(callable $condition, callable $callable);
$dispatcher->template->condition(function(WP_Query $query) {
    //    if this  is true
    return $query->is_page;
}, function(WP_Query $query, string $template) {
    // then execute this
    
    return $template;
});  
```

### Post Type Templates
Register [post type templates](https://developer.wordpress.org/themes/template-files-section/page-template-files/) without the constraints the WordPress implementation has. The `template` method takes a template file path as the first argument&mdash;relative to the current (child) theme, the template name (for the wp-admin sidebar select box) as the second, the post types for which this template should be available as the third and a callback as the final argument. See the [section on callbacks](#callbacks) on how they are handled. In addition to the current `WP_Query` object, the callback function receives the full path to the template as the second argument:

```php
// public function template(string $template, string $name, array $postTypes, callable $callable);
$dispatcher->template->template('my-page-template.php', __('Page Template Name', 'textdomain'), ['page', 'post'], function(WP_Query $query, string $template) {
    // ...
});  
```

## Ajax Routing
AJAX requests can be either `GET` or `POST` requests (or both), and apply to logged-in users only or all users.
You can define custom routes for AJAX requests in the following format:

```php
// public function get(string $route, callable $callback, bool $private = false);
$dispatcher->ajax->get('/ajax/user/{user_id:\d+}', function () {
    $userId = (int)$_GET['query']['user_id'];
    $user = new WP_User($userId);
    if ($user->ID === 0) {
        wp_send_json_error(sprintf('User #%d does not exist', $userId));
    }
    wp_send_json_success($user);
}, true);
```

*Notes*:
* Compared to the other routers, the callback does not receive a `WP_Query` object; the query vars are included in the `$_REQUEST` super global
* In contrast to WordPress' AJAX actions, requests that are not marked as private apply to both logged-in and not logged-in users (`priv` vs. `nopriv`).
