<?php
declare(strict_types=1);

namespace App\Core;

use Exception;

class Router
{
    private array $routes = [];
    private array $services = [];

    public function __construct(array $services = [])
    {
        $this->services = $services;
    }

    public function post(string $path, array $controller): void
    {
        $this->addRoute('POST', $path, $controller);
    }

    public function get(string $path, array $controller): void
    {
        $this->addRoute('GET', $path, $controller);
    }

    private function addRoute(string $method, string $path, array $controller): void
    {
        $pattern = preg_replace('/\/{([^}]+)}/', '/(?<$1>[^/]+)', $path);
        $this->routes[$method][$pattern] = $controller;
    }

    public function handle(): void
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

            $route = $this->resolve($method, $path);

            [$controllerClass, $action] = $route['controller'];
            $params = $route['params'];

            // // Create controller instance with required services
            $controller = new $controllerClass(
                // $this->services['paymentProcessor']
            );

            // Execute the action
            $controller->$action($params);

        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    private function resolve(string $method, string $path): array
    {
        foreach ($this->routes[$method] ?? [] as $pattern => $controller) {
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $path, $matches)) {
                // Extract route parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                return [
                    'controller' => $controller,
                    'params' => $params
                ];
            }
        }

        throw new Exception('Route not found', 404);
    }

    private function handleError(Exception $e): void
    {
        $status = $e->getCode() ?: 500;
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
