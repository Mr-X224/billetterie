<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Enum\OrderChannel;
use App\Enum\OrderStatus;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('reference', 'Référence');
        yield AssociationField::new('customer', 'Client');
        yield AssociationField::new('seller', 'Guichetier');
        yield ChoiceField::new('channel', 'Canal')->setChoices([
            'En ligne' => OrderChannel::WEB,
            'Guichet' => OrderChannel::BOX_OFFICE,
        ]);
        yield ChoiceField::new('status', 'Statut')->setChoices([
            'En attente' => OrderStatus::PENDING,
            'Payée' => OrderStatus::PAID,
            'Échouée' => OrderStatus::FAILED,
            'Expirée' => OrderStatus::EXPIRED,
            'Annulée' => OrderStatus::CANCELLED,
            'Remboursée' => OrderStatus::REFUNDED,
        ]);
        yield MoneyField::new('totalCents', 'Total')->setCurrency('MAD')->setStoredAsCents();
        yield MoneyField::new('refundedCents', 'Remboursé')->setCurrency('MAD')->setStoredAsCents();
        yield DateTimeField::new('createdAt', 'Créée le');
        yield DateTimeField::new('paidAt', 'Payée le')->hideOnIndex();
    }
}