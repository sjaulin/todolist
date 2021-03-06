<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Exception;
use App\Service\TaskService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 *
 * @Route("/tasks")
 *
 * @IsGranted("ROLE_USER")
 */
class TaskController extends AbstractController
{

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * @Route("/list/{done}", name="tasks")
     * @param bool $done
     */
    public function listAction(bool $done): Response
    {
        return $this->render(
            'task/list.html.twig',
            [
                'tasks' => $this->taskRepository->findBy(['isDone' => $done]),
            ]
        );
    }

    /**
     * @Route("/create", name="task_create")
     */
    public function createAction(Request $request, TaskService $taskService): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $taskService->create($task);
            $this->addFlash('success', 'La tâche a été bien été ajoutée.');
            return $this->redirectToRoute('tasks', ['done' => 0]);
        }

        return $this->render('task/create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/{id}/edit", name="task_edit")
     */
    public function editAction(Task $task, Request $request, TaskService $taskService): Response
    {

        $this->denyAccessUnlessGranted('ENTITY_EDIT', $task, "Vous ne pouvez pas modifier cette tâche");

        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $taskService->update($task);
            $this->addFlash('success', 'La tâche a bien été modifiée.');
            return $this->redirectToRoute('tasks', ['done' => (int) $task->getIsDone()]);
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    /**
     * @Route("/{id}/toggle", name="task_toggle")
     */
    public function toggleTaskAction(Task $task): Response
    {

        $this->denyAccessUnlessGranted('ENTITY_EDIT', $task, "Vous ne pouvez pas modifier cette tâche");

        $task->setIsDone(!$task->getIsDone());
        $this->getDoctrine()->getManager()->flush();

        if ($task->getIsDone()) {
            $this->addFlash('success', sprintf('La tâche %s a bien été marquée comme terminée.', $task->getTitle()));
        } else {
            $this->addFlash('success', sprintf('La tâche %s a bien été marquée comme à faire.', $task->getTitle()));
        }

        return $this->redirectToRoute('tasks', ['done' => (int) !$task->getIsDone()]);
    }

    /**
     * @Route("/{id}/delete", name="task_delete")
     */
    public function deleteTaskAction(Task $task): Response
    {

        $this->denyAccessUnlessGranted('ENTITY_DELETE', $task, "Vous ne pouvez pas supprimer cette tâche");

        $em = $this->getDoctrine()->getManager();
        $em->remove($task);
        $em->flush();

        $this->addFlash('success', 'La tâche a bien été supprimée.');

        return $this->redirectToRoute('tasks', ['done' => (int) $task->getIsDone()]);
    }
}
