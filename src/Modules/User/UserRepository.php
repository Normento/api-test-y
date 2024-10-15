<?php

namespace Core\Modules\User;

use Core\Modules\User\Models\Device;
use Core\Modules\User\Models\User;
use Core\Utils\BaseRepository;
use Illuminate\Http\Request;
use Normalizer;
use Core\Modules\RecurringOrder\Repositories\RecurringOrderRepository;

class UserRepository extends BaseRepository
{
    private  User $userModel;
    // public RecurringOrderRepository $recurringOrderRepository;


    public function __construct(User $userModel)
    {
        $this->userModel = $userModel;
        // $this->recurringOrderRepository = app(RecurringOrderRepository::class);
        parent::__construct($userModel);
    }

    public function userWithRole(array $roles, bool $paginate = false)
    {
        $query = $this->userModel->with('roles')->role($roles)->orderBy('created_at', 'DESC');
        return $paginate ? $query->paginate(20) :
            $query->get();
    }
    public function isCOAssignedToCustomer(User $customer, User $co): bool
    {
        return $customer->co
            ->contains($co);
    }

    public function adminUsers()
    {
        return $this->userModel->whereHas('roles', function ($query) {
            $query->where('name', '<>', 'customer');
        })->with('roles')->paginate(20);
    }

    public function searchAdminUsers(Request $request): \Illuminate\Support\Collection
    {
        $result = [];
        if ($request->filled('role') && !$request->has('searchParam')) {
            $result = $this->userWithRole([$request->input('role')]);
        } elseif ($request->filled('searchParam') && !$request->has('role')) {
            $normalizedFilter = mb_strtolower(normalizer_normalize($request->input('searchParam'), Normalizer::FORM_D));
            $result = $this->userModel->whereHas('roles', function ($query) {
                $query->where('name', '<>', 'customer');
            })
                ->where(function ($query) use ($normalizedFilter, $request) {
                    $query->whereRaw('lower(unaccent(last_name)) ilike ?', ['%' . $normalizedFilter . '%'])
                        ->orWhere('phone_number', 'like', '%' . $request->input('searchParam') . '%')
                        ->orWhereRaw('lower(unaccent(first_name)) ilike ?', ['%' . $normalizedFilter . '%'])
                        ->orWhere('email', 'like', '%' . $request->input('searchParam') . '%');
                })->with('roles')->get();
        }
        return collect($result);
    }

    public function searchCustomerUsers(Request $request)
    {
        $result = [];
        $query = $this->userModel->role('customer');
        if ($request->has('searchParam') && !$request->has('start_date', 'end_date', "is_activated")) {
            $searchField = $request->input('searchParam');
            $normalizedFilter = mb_strtolower(normalizer_normalize($searchField, Normalizer::FORM_D));
            $result = $query
                ->where(function ($query) use ($normalizedFilter, $searchField) {
                    $query->whereRaw('lower(unaccent(last_name)) ilike ?', ['%' . $normalizedFilter . '%'])
                        ->orWhere('phone_number', 'like', '%' . $searchField . '%')
                        ->orWhereRaw('lower(unaccent(first_name)) ilike ?', ['%' . $normalizedFilter . '%'])
                        ->orWhere('email', 'like', '%' . $searchField . '%');
                })->orderBy('created_at')->get();
        }
        if ($request->has(['start_date', 'end_date']) && !$request->has(['is_activated', "searchParam"])) {
            $result = $query->whereBetween('users.created_at', [$request->input('start_date'), $request->input('end_date')])->orderBy('created_at', 'desc')->get();
        }
        if ($request->has('is_activated') && !$request->has(['start_date', 'end_date', 'searchParam'])) {
            $result = $query->where('is_activated', $request->input('is_activated'))->orderBy('created_at', 'desc')->get();
        }
        if ($request->has(['start_date', 'end_date', "is_activated"]) && !$request->has('searchParam')) {
            $result = $query->whereBetween('users.created_at', [$request->input('start_date'), $request->input('end_date')])->where('is_activated', $request->input('is_activated'))->orderBy('created_at', 'desc')->get();
        }
        return $result;
    }

    public function getStatistics(): array
    {
        $user = $this->userWithRole(['customer'], false);

        // Requête pour le nombre de clients
        $totalClients = $user->count();

        // Requête pour le nombre de clients actifs
        $activeClients = $user->where('is_activated', true)->count();

        // Requête pour le nombre de clients inactifs
        $inactiveClients = $user->where('is_activated', false)->count();

        // $orderRecurring = $this->recurringOrderRepository->getUserOrders($user, type: 1);
        // // $totalItems = 0;

        // if (!empty($orderRecurring['data']['data'])) {
        //     // Calculer le nombre total d'éléments dans le tableau 'data'
        //     $total = count($orderRecurring['data']['data']);
        // } else {
        //     $total = 0;
        // }

        // Requête pour le nombre de clients avec package
        // $recurringOrderClients = $user->whereHas('orders', function ($query) {
        //     $query->where('is_recurring', true);
        // })->count();


        // Retourner les statistiques
        return [
            'totalClients' => $totalClients,
            'activeClients' => $activeClients,
            'inactiveClients' => $inactiveClients,
            // 'total' => $total,
            // 'recurringOrderClients' => $recurringOrderClients
        ];
    }
}
