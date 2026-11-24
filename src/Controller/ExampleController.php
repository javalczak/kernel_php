<?php
declare(strict_types=1);

namespace App\Controller;

use App\Repository\ExampleRepository;
use App\Service\ExampleService;
use Engine\Controller;
use Engine\Csrf;
use Engine\Request;
use Engine\Response;

/** Example CRUD: list, detail, create, delete, search. Validation lives in ExampleService. */
final class ExampleController extends Controller
{
    public function index(Request $request, array $params = []): Response
    {
        $search = $request->string('q');

        $items = $search === ''
            ? $this->repository()->findAll('created_at DESC')
            : $this->repository()->searchByTitle($search);

        return $this->render('example/index', [
            'items'  => $items,
            'search' => $search,
            'csrf'   => Csrf::token(),
        ]);
    }

    public function show(Request $request, array $params = []): Response
    {
        $item = $this->repository()->findById((int) $params['id']);

        if ($item === null) {
            return Response::notFound($this->trans('example.errors.not_found'));
        }

        return $this->render('example/show', ['item' => $item]);
    }

    public function create(Request $request, array $params = []): Response
    {
        $errors = [];
        $title  = '';

        // No CSRF check here: the kernel rejects a write without a valid token.
        if ($request->isPost()) {
            $title = $request->string('title');

            try {
                $this->service()->create($title);

                return $this->redirect('/items');
            } catch (\InvalidArgumentException $e) {
                $errors['title'] = $e->getMessage();
            }
        }

        return $this->render('example/create', [
            'errors'    => $errors,
            'title'     => $title, // on error, hand back what was typed
            'csrfToken' => Csrf::token(),
        ]);
    }

    /** POST only: a GET would be fired by any link preview or crawler. */
    public function delete(Request $request, array $params = []): Response
    {
        $item = $this->repository()->findById((int) $params['id']);

        if ($item === null) {
            return Response::notFound($this->trans('example.errors.not_found'));
        }

        $this->repository()->delete($item);

        return $this->redirect('/items');
    }

    private function repository(): ExampleRepository
    {
        return new ExampleRepository($this->db());
    }

    private function service(): ExampleService
    {
        return new ExampleService($this->repository(), $this->db());
    }
}
