<?php

namespace Core\Modules\PunctualOrder\Repositories;

use Core\Modules\PunctualOrder\Models\PunctualOrder;
use Core\Modules\User\Models\User;
use Core\Utils\BaseRepository;
use Normalizer;

class PunctualOrderRepository extends BaseRepository
{
    private $punctualOrderModel;
    private $userModel;

    public function __construct(PunctualOrder $punctualOrderModel, User $userModel)
    {
        parent::__construct($punctualOrderModel);
        $this->punctualOrderModel = $punctualOrderModel;
        $this->userModel = $userModel;
    }



    public function getAllPunctualOrders(User $user = null)
    {
        $userOrder = [];
        if ($user != Null) {
            $userOrder = $user
                ->with('orders.service')
                ->whereHas('orders', function ($query) {
                    $query->whereNull('deleted_at');
                })->paginate(10);
        } else {
            $userOrder = $this->userModel
                ->with('orders.service')
                ->whereHas('orders', function ($query) {
                    $query->whereNull('deleted_at');
                })
                ->paginate(10);
        }
        return $userOrder;
    }

    public function getAllPunctualOrder()
    {
        $userOrder = $this->userModel
            ->with('orders.service')
            ->whereHas('orders', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->count();
        return $userOrder;
    }



    public function filterOrders($request)
    {
        $userOrder =  $this->userModel;

        if ($request->has('filter')) {
            $filter = $request->input('filter');
            $normalizedFilter = mb_strtolower(normalizer_normalize($filter, Normalizer::FORM_D));
            return $userOrder
                ->whereHas('orders', function ($q) use ($normalizedFilter) {
                    $q->whereNull('punctual_orders.deleted_at')
                    ->whereRaw('lower(users.last_name) ilike ?', ['%' . $normalizedFilter . '%'])
                    ->orWhereRaw('lower(users.first_name) ilike ?', ['%' . $normalizedFilter . '%']);
                })
                ->with(['orders', 'orders.service'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($request->filled('status') && empty($request->only(['service', 'start_date', 'end_date']))) {
            return   $userOrder
                ->whereHas('orders', function ($q) use ($request) {
                    $q->whereNull('punctual_orders.deleted_at')
                        ->where('punctual_orders.status', $request->input('status'));
                })
                ->with(['orders', 'orders.service'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($request->filled('service') && empty($request->only(['status', 'start_date', 'end_date']))) {
            return   $userOrder
                ->whereHas('orders', function ($q) use ($request) {
                    $q->whereNull('punctual_orders.deleted_at')
                    ->where('punctual_orders.service_id', $request->input('service'));
                })
                ->with(['orders', 'orders.service'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($request->filled(['start_date', 'end_date']) && empty($request->only(['service', 'status']))) {
            return   $userOrder
                ->whereHas('orders', function ($q) use ($request) {
                    $q->whereNull('punctual_orders.deleted_at')
                    ->whereBetween('punctual_orders.created_at', [$request->input('start_date'), $request->input('end_date')]);
                })
                ->with(['orders', 'orders.service'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($request->filled(['start_date', 'end_date', 'status']) && empty($request->only(['service']))) {
            return   $userOrder
                ->whereHas('orders', function ($q) use ($request) {
                    $q->whereNull('punctual_orders.deleted_at')
                        ->where('punctual_orders.status', $request->input('status'))
                        ->whereBetween('punctual_orders.created_at', [$request->input('start_date'), $request->input('end_date')]);
                })
                ->with(['orders', 'orders.service'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($request->filled(['status', 'service']) && empty($request->only(['start_date', 'end_date']))) {
            return   $userOrder
                ->whereHas('orders', function ($q) use ($request) {
                    $q->whereNull('punctual_orders.deleted_at')
                    ->where('punctual_orders.status', $request->input('status'))
                    ->where('punctual_orders.service_id', $request->input('service'));
                })
                ->with(['orders', 'orders.service'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($request->filled(['start_date', 'end_date', 'service']) && empty($request->only('status'))) {
            return   $userOrder
                ->whereHas('orders', function ($q) use ($request) {
                    $q->whereNull('punctual_orders.deleted_at')
                    ->where('punctual_orders.service_id', $request->input('service'))
                    ->whereBetween('punctual_orders.created_at', [$request->input('start_date'), $request->input('end_date')]);
                })
                ->with(['orders', 'orders.service'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($request->filled(['start_date', 'end_date', 'service', 'status'])) {
            return   $userOrder
                ->whereHas('orders', function ($q) use ($request) {
                    $q->whereNull('punctual_orders.deleted_at')
                    ->where('punctual_orders.status', $request->input('status'))
                    ->where('punctual_orders.service_id', $request->input('service'))
                    ->whereBetween('punctual_orders.created_at', [$request->input('start_date'), $request->input('end_date')]);;
                })
                ->with(['orders', 'orders.service'])
                ->orderBy('created_at', 'desc')
                ->get();
        }
    }

    public function getStatistics()
    {
        $orders = $this->punctualOrderModel;

        // Requête pour le nombre de commande en attente
        $pendingOrder = $orders
            ->whereNull('punctual_orders.deleted_at')
            ->where('status', '=', 0)
            ->count();

        // Requête pour le nombre de commande avec offre
        $orderWithOffer = $orders
            ->whereNull('punctual_orders.deleted_at')
            ->where('status', '=', 1)
            ->count();

        // Requête pour le nombre de commande noté
         $notedOrder = $orders
         ->whereNull('punctual_orders.deleted_at')
         ->where('status', '=', 3)
         ->where('note_id','!=', null)
         ->count();

        // Requête pour le nombre de commande avec une note inférieur à 3
         $unsatisfiedOrder = $orders
         ->note()->where('note', '<', 3)->count();

        // Retourner les statistiques
        return [
            'unsatisfiedOrder' => $unsatisfiedOrder,
            'notedOrder' => $notedOrder,
            'pendingOrder' => $pendingOrder,
            'orderWithOffer' => $orderWithOffer,
        ];
    }


}
