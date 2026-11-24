<?php
declare(strict_types=1);

namespace App\Controller;

use Engine\Controller;
use Engine\Request;
use Engine\Response;

/** Simplest controller there is: one action, one template, no dependencies. */
final class HelloWorldController extends Controller
{
    public function index(Request $request, array $params = []): Response
    {
        // Template name relative to templates/, without .php
        return $this->render('home');
    }
}
