<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Enum\EventType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class EventCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Event::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Titre');
        yield ChoiceField::new('type', 'Type')->setChoices([
            'Concert' => EventType::CONCERT,
            'Théâtre' => EventType::THEATRE,
            'Conférence' => EventType::CONFERENCE,
        ]);
        yield IntegerField::new('durationMinutes', 'Durée (min)');
        yield TextareaField::new('description', 'Description')->hideOnIndex();
    }
}