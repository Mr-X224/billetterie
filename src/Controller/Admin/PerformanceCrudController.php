<?php

namespace App\Controller\Admin;

use App\Entity\Performance;
use App\Enum\PerformanceStatus;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

class PerformanceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Performance::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('event', 'Événement');
        yield AssociationField::new('venue', 'Configuration de salle');
        yield DateTimeField::new('startsAt', 'Date et heure');
        yield ChoiceField::new('status', 'Statut')->setChoices([
            'Programmée' => PerformanceStatus::SCHEDULED,
            'Annulée' => PerformanceStatus::CANCELLED,
        ]);
    }
}