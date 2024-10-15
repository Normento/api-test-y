<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Core\Modules\Access\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentsPermissions = [
            'view-payments' => "Voir les paiements",
            'create-payments' => "Créer un paiement",
            'edit-payments' => "Modifier un paiement",
            'delete-payments' => "Supprimer un paiement",
        ];

        $propositionsPermissions = [
            'view-propositions' => "Voir les propositions",
            'create-propositions' => "Créer une proposition",
            'edit-propositions' => "Modifier une proposition",
            'delete-propositions' => "Supprimer une proposition",
        ];

        $prospectsPermissions = [
            'view-prospects' => "Voir les prospects",
            'create-prospects' => "Créer un prospect",
            'edit-prospects' => "Modifier un prospect",
            'delete-prospects' => "Supprimer un prospect",
        ];

        $punctualOrdersPermissions = [
            'view-punctual-orders' => "Voir les commandes ponctuelles",
            'create-punctual-orders' => "Créer une commande ponctuelle",
            'edit-punctual-orders' => "Modifier une commande ponctuelle",
            'delete-punctual-orders' => "Supprimer une commande ponctuelle",
        ];

        $suivisPermissions = [
            'view-suivis' => "Voir les suivis",
            'create-suivis' => "Créer un suivi",
            'edit-suivis' => "Modifier un suivi",
            'delete-suivis' => "Supprimer un suivi",
        ];

        $transactionsPermissions = [
            'view-transactions' => "Voir les transactions",
            'create-transactions' => "Créer une transaction",
            'edit-transactions' => "Modifier une transaction",
            'delete-transactions' => "Supprimer une transaction",
        ];

        $walletsPermissions = [
            'view-wallets' => "Voir les portefeuilles",
            'create-wallets' => "Créer un portefeuille",
            'edit-wallets' => "Modifier un portefeuille",
            'delete-wallets' => "Supprimer un portefeuille",
        ];

        $usersPermissions = [
            'view-users' => "Voir les utilisateurs",
            'create-users' => "Créer un utilisateur",
            'edit-users' => "Modifier un utilisateur",
            'delete-users' => "Supprimer un utilisateur",
        ];

        $offersPermissions = [
            'view-offers' => "Voir les offres",
            'create-offers' => "Créer une offre",
            'edit-offers' => "Modifier une offre",
            'delete-offers' => "Supprimer une offre",
        ];

        $notificationsPermissions = [
            'view-notifications' => "Voir les notifications",
            'create-notifications' => "Créer une notification",
            'edit-notifications' => "Modifier une notification",
            'delete-notifications' => "Supprimer une notification",
        ];

        $messagesPermissions = [
            'view-messages' => "Voir les messages",
            'create-messages' => "Créer un message",
            'edit-messages' => "Modifier un message",
            'delete-messages' => "Supprimer un message",
        ];

        $conversationsPermissions = [
            'view-conversations' => "Voir les conversations",
            'create-conversations' => "Créer une conversation",
            'edit-conversations' => "Modifier une conversation",
            'delete-conversations' => "Supprimer une conversation",
        ];

        $jobOffersPermissions = [
            'view-job-offers' => "Voir les offres d'emploi",
            'create-job-offers' => "Créer une offre d'emploi",
            'edit-job-offers' => "Modifier une offre d'emploi",
            'delete-job-offers' => "Supprimer une offre d'emploi",
        ];

        $applyForJobsPermissions = [
            'view-applications' => "Voir les candidatures",
            'create-applications' => "Soumettre une candidature",
            'edit-applications' => "Modifier une candidature",
            'delete-applications' => "Supprimer une candidature",
        ];

        $permissions = array_merge(
            $applyForJobsPermissions,
            $jobOffersPermissions,
            $conversationsPermissions,
            $messagesPermissions,
            $notificationsPermissions,
            $offersPermissions,
            $usersPermissions,
            $walletsPermissions,
            $transactionsPermissions,
            $punctualOrdersPermissions,
            $propositionsPermissions,
            $paymentsPermissions,
            $prospectsPermissions,
            $suivisPermissions,
        );



        foreach ($permissions as $key => $value) {
            Permission::create(['name' => $key, 'display_name' => $value]);
        }

    }
}
