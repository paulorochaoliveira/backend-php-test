<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Kosinix\Paginator;
use Kosinix\Pagination;
use Models\Todo;
use Models\User;

$todo = $app['controllers_factory'];

$todo->post('/add', function (Request $request) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/user/login');
    }

    $user_id = $user['id'];
    $description = $request->get('description');

    if($description) {
        if (Todo::create($user, $description, $app)) {
            $app['session']->getFlashBag()->add('success', 'Success!! ToDo added to your list!');
        } else {
            $app['session']->getFlashBag()->add('error', 'Fail! ToDo couldn\'t be added to your list. Try again later!');
        }
    
    } else {
        $app['session']->getFlashBag()->add('error_messages', 'Error! A ToDo can\'t be created without a description.');
    }
    return $app->redirect('/todo/list');
});

$todo->get('/list/{page}/{sort_by}/{sorting}', function (Request $request, $page, $sort_by, $sorting) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/user/login');
    }

    $count = (int) Todo::countById($user, $app);

    /** @var \Kosinix\Paginator $paginator */
    $paginator =  $app['paginator']($count, $page);

    $todos = Todo::list($user, $sort_by, $sorting, $paginator, $app);

    $pagination = new Pagination($paginator, $app['url_generator'], 'templates', $sort_by, $sorting);

    return $app['twig']->render('todos.html', array(
        'todos' => $todos,
        'pagination' => $pagination
    ));
})
->value('page', 1)
->value('sort_by', 'id')
->value('sorting', 'asc')
->assert('page', '\d+') // Numbers only
->assert('sort_by','[a-zA-Z_]+') // Match a-z, A-Z, and "_"
->assert('sorting','(\basc\b)|(\bdesc\b)') // Match "asc" or "desc"
->bind('templates');


$todo->get('/{id}', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/user/login');
    }

    $todo = Todo::findById($id, $app);

    if ($todo) {
        if($todo['user_id'] === $user['id']) {
            return $app['twig']->render('todo.html', [
                'todo' => $todo,
            ]);
        } else {
            $app['session']->getFlashBag()->add('error' , "You are not authorized to see this ToDo.");
        }      
    } else {
        $app['session']->getFlashBag()->add('error' , 'No ToDo found with the id '. $id);
    }

    return $app->redirect('/todo/list');
})
->value('id', null);

$todo->get('/{id}/json', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/user/login');
    }

    if ($id){
        $todo = Todo::findById($id, $app);

        if ($todo) {
            if($todo['user_id'] === $user['id']) {
                return new JsonResponse($todo);
            } else {
                return new JsonResponse(array('error' => "You are not authorized to see this ToDo."));
            }      
        } else {
            return new JsonResponse(array('error' => 'No ToDo found with the id '. $id));
        } 
    } else {
        return new JsonResponse(array('error' => 'You need to provide an id.'));
    }
})
->value('id', null); 


$todo->post('/done/{id}', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/user/login');
    }

    $todo = Todo::findById($id, $app);

    if ($todo) {
        if($todo['user_id'] === $user['id']) {
            Todo::updateDone($id, $app);
        } else {
            $app['session']->getFlashBag()->add('error' , "You are not authorized to modify this ToDo.");
        }      
    } else {
        $app['session']->getFlashBag()->add('error' , 'No ToDo found with the id '. $id);
    }

    return $app->redirect('/todo/list');
});


$todo->post('/undone/{id}', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/user/login');
    }

    $todo = Todo::findById($id, $app);

    if ($todo) {
        if($todo['user_id'] === $user['id']) {
            Todo::updateUndone($id, $app);
        } else {
            $app['session']->getFlashBag()->add('error' , "You are not authorized to modify this ToDo.");
        }      
    } else {
        $app['session']->getFlashBag()->add('error' , 'No ToDo found with the id '. $id);
    }

    return $app->redirect('/todo/list');
}); 

$todo->match('/delete/{id}', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/user/login');
    }

    if ($id){
        $todo = Todo::findById($id, $app);

        if ($todo) {

            if($todo['user_id'] === $user['id']) {

                if (Todo::delete($id, $app)) {
                    $app['session']->getFlashBag()->add('info' , 'Success! The ToDo with id '. $id . ' was DELETED from your list.');
                } else {
                    $app['session']->getFlashBag()->add('error', 'Error! ToDo couldn\'t be deleted from your list. Try again later!');
                }

            } else {
                $app['session']->getFlashBag()->add('error' , "Error! You are not authorized to DELETE this ToDo.");
            }      
        } else {
            $app['session']->getFlashBag()->add('error' , 'Error! No ToDo found with the id '. $id);
        } 
    } else {
        $app['session']->getFlashBag()->add('error' , 'You need to provide an id.');
    }

    return $app->redirect('/todo/list');
});


return $todo;