<?php


namespace EasyCorp\Bundle\EasyAdminBundle\Factory;


use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Lukas Lücke <lukas@luecke.me>
 */
final class ControllerFactory
{
    private $dashboardControllers;
    private $crudControllers;
    private $controllerResolver;

    public function __construct(DashboardControllerRegistry $dashboardControllers, CrudControllerRegistry $crudControllers, ControllerResolverInterface $controllerResolver)
    {
        $this->dashboardControllers = $dashboardControllers;
        $this->crudControllers = $crudControllers;
        $this->controllerResolver = $controllerResolver;
    }

    public function getDashboardControllerInstance(string $contextId, Request $request): ?DashboardControllerInterface
    {
        return $this->getDashboardController($this->dashboardControllers->getControllerFqcnByContextId($contextId), $request);
    }

    public function getCrudControllerInstance(?string $crudId, ?string $crudAction, Request $request): ?CrudControllerInterface
    {
        if (null === $crudId) {
            return null;
        }

        return $this->getCrudController($this->crudControllers->findCrudFqcnByCrudId($crudId), $crudAction, $request);
    }

    public function getDashboardController(?string $dashboardControllerFqcn, Request $request): ?DashboardControllerInterface
    {
        return $this->getController(DashboardControllerInterface::class, $dashboardControllerFqcn, 'index', $request);
    }

    public function getCrudController(?string $crudControllerFqcn, ?string $crudAction, Request $request): ?CrudControllerInterface
    {
        return $this->getController(CrudControllerInterface::class, $crudControllerFqcn, $crudAction, $request);
    }

    private function getController(string $controllerInterface, ?string $controllerFqcn, ?string $controllerAction, Request $request)
    {
        if (null === $controllerFqcn || null === $controllerAction) {
            return null;
        }

        $newRequest = $request->duplicate(null, null, ['_controller' => [$controllerFqcn, $controllerAction]]);
        $controllerCallable = $this->controllerResolver->getController($newRequest);

        if (false === $controllerCallable) {
            throw new NotFoundHttpException(sprintf('Unable to find the controller "%s::%s".', $controllerFqcn, $controllerAction));
        }

        if (!\is_array($controllerCallable)) {
            return null;
        }

        $controllerInstance = $controllerCallable[0];

        return is_subclass_of($controllerInstance, $controllerInterface) ? $controllerInstance : null;
    }
}
