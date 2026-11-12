<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
      public function index(): Response
    {
        // Provisoire : sera remplacé par le tableau de bord (remplissage, recettes) en partie 5
        return $this->redirectToRoute('admin_event_index');
    }
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Billetterie Atlas');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');

        yield MenuItem::section('Programmation');
        yield MenuItem::linkTo(EventCrudController::class, 'Événements', 'fa fa-masks-theater');
        yield MenuItem::linkTo(PerformanceCrudController::class, 'Représentations', 'fa fa-calendar-days');

        yield MenuItem::section('Tarification');
        yield MenuItem::linkTo(TariffCrudController::class, 'Tarifs', 'fa fa-tags');
        yield MenuItem::linkTo(PriceCrudController::class, 'Grille de prix', 'fa fa-coins');

        yield MenuItem::section('Salle');
        yield MenuItem::linkTo(VenueCrudController::class, 'Configurations de salle', 'fa fa-building');
        yield MenuItem::linkTo(ZoneCrudController::class, 'Zones', 'fa fa-layer-group');
        yield MenuItem::linkTo(SeatCrudController::class, 'Places', 'fa fa-chair');

        yield MenuItem::section('Ventes');
        yield MenuItem::linkTo(OrderCrudController::class, 'Commandes', 'fa fa-receipt');

        yield MenuItem::section('Comptes');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-users');
    }
}