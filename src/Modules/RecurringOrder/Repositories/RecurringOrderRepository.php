<?php

namespace Core\Modules\RecurringOrder\Repositories;

use Core\Modules\Employee\Models\Employee;
use Core\Modules\RecurringOrder\Models\Proposition;
use Core\Modules\RecurringOrder\Models\RecurringOrder;
use Core\Modules\RecurringService\Models\RecurringService;
use Core\Modules\User\Models\User;
use Core\Modules\User\UserRepository;
use Core\Utils\BaseRepository;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Normalizer;
class RecurringOrderRepository extends BaseRepository
{
    protected $model;
    protected $userModel;
    protected $propositionModel;
    private UserRepository $userRepository;

    public function __construct(RecurringOrder $model, Proposition $propositionModel, UserRepository $userRepository, User $userModel)
    {
        parent::__construct($model);
        $this->model = $model;
        $this->userModel = $userModel;
        $this->propositionModel = $propositionModel;
        $this->userRepository = $userRepository;
    }

    public function storeRecurringOrder(array $data, RecurringService $service, User $user): RecurringOrder
    {
        $recurringOrder = $this->make($data);
        $recurringOrder->user()->associate($user);
        $recurringOrder->recurringService()->associate($service);
        $recurringOrder->save();
        return $recurringOrder;
    }

    public function getUserOrders(User $user, ?bool $isPaid = null, int $type = 1,bool $is_archived = false): Collection
    {
        $conditions = ['type' => $type, 'is_archived' => $is_archived];

        if (!is_null($isPaid)) {
            $conditions['is_paid'] = $isPaid;
        }
        //dd($user->id);


        $orders = $user->recurringOrders()
        ->with('recurringService:id,name,image,placement_fee')
        ->withCount('propositions')
        ->where($conditions)
        ->get();

        $orders->transform(function ($order) {
            $order->recurringService->image = Storage::temporaryUrl($order->recurringService->image, now()->addDay(7));
            return $order;
        });
        //dd($orders);

        return $orders;
    }


    public function getUserOrdersForCO(User $staff, int $type = 1)
{
    return
    $staff->customers()->whereHas('recurringOrders', function ($query) use ($type) {
                $query->whereNull('deleted_at');
                $query->where('is_archived', false);
                $query->where('type', $type);
            })
                ->with(['recurringOrders' => function ($q) use ($type) {
                    $q->where('type', $type);
                }, 'co', 'recurringOrders.recurringService:id,name,image'])
                ->paginate(10);
    }




    public function checkIfEmployeeIsAlreadyProposed(RecurringOrder $recurringOrder, Employee $employee): bool
    {
        return $recurringOrder->propositions()
            ->where('employee_id', $employee->id)
            ->exists();
    }

    public function storeProposition(array $data, $recurringOrder, $employee, $user)
    {
        $proposition = $this->propositionModel->make($data);
        $proposition->recurringOrder()->associate($recurringOrder);
        $proposition->employee()->associate($employee);
        $proposition->proposed_at = now();
        $proposition->proposedBy()->associate($user);
        $proposition->save();
        return $proposition;
    }

    public function updateProposition(Proposition $proposition, array $data): bool
    {
        return $proposition->update($data);
    }

    public function acceptedOrActivePropositions(RecurringOrder $recurringOrder): Collection
    {
        return $recurringOrder->propositions()
            ->with('employee')
            ->where('status', 1)
            ->orWhere('status', 2)
            ->orderBy('created_at')
            ->get();
    }




    public function getCustomerOrdersForStaff(User $staff, User $customer = null, int $type = 1)
    {
        //dd($staff);
        if (is_null($customer)) {
            $users = $staff->hasRole('CO')
                ? $staff->customers()->where('co_customer.status', true)->get()

                : $this->userRepository->findBy('referred_by', $staff->id, collection: true);
                //dd($users);


            return $users->map(function ($user) {

                return $this->getUserOrders($user);

            })->all();
        } else {
            $user = $staff->hasRole('CO')
                ? $staff->customers()
                    ->where('co_customer.status', true)
                    ->where('users.id', $customer->id)
                    ->first()
                : $this->model
                    ->where('referred_by', $staff->id)
                    ->where('id', $customer->id)->first();
                    //dd($user->id);
                    return $this->getUserOrders($user);
        }
    }

    public function getStatistics(Request $request)
    {
        $recurringOrders = $this->model;

        // Requête pour le nombre de commande en attente
        $pendingOrder = $recurringOrders
            ->whereNull('recurring_orders.deleted_at')
            ->where([
                ['status', '=', 0],
                ['type','=',$request->input('type') ?? 1],
             ])
            ->count();

        // Requête pour le nombre de commande avec offre
        $orderWithProposition = $recurringOrders
            ->whereNull('recurring_orders.deleted_at')
            ->where([
                ['status', '=', 1],
                ['type','=',$request->input('type') ?? 1],
                ['is_archived','=',false]
             ])
            ->count();

        $ordersArchived = $recurringOrders
            ->whereNull('recurring_orders.deleted_at')
            ->where([
                ['is_archived', '=', true],
                ['type','=',$request->input('type') ?? 1]
             ])
            ->count();

        return [
            'pendingOrder' => $pendingOrder,
            'orderWithProposition' => $orderWithProposition,
            'ordersArchived' => $ordersArchived
        ];
    }

    public function getUsersWithRecurringOrders(int $type)
    {
        return
            $this->userModel->whereHas('recurringOrders', function ($query) use ($type) {
                $query->whereNull('deleted_at');
                $query->where('is_archived', false);
                $query->where('type', $type);
            })
                ->with(['recurringOrders' => function ($q) use ($type) {
                    $q->where('type', $type);
                }, 'co', 'recurringOrders.recurringService:id,name,image'])
                ->paginate(10);
    }
    public function getUsersWithRecurringOrder(int $type)
    {
        return
            $this->userModel->whereHas('recurringOrders', function ($query) use ($type) {
                $query->whereNull('deleted_at');
                $query->where('is_archived', false);
                $query->where('type', $type);
            })
                ->with(['recurringOrders' => function ($q) use ($type) {
                    $q->where('type', $type);
                }, 'co', 'recurringOrders.recurringService:id,name,image'])
                ->count();
    }




    public function filterRecurringOrders(Request $request)
    {

      $result = collect();

      if(!$request->has('customer') && $request->has('service') || $request->has('status') || $request->has(['start_date', 'end_date'])){
          $result = $this->filtreWithoutCustomer($request,$this->userModel);
      }



        if($request->has('customer') && !$request->has(['start_date', 'end_date','status','service'])){
            $normalizedOwner = mb_strtolower(normalizer_normalize($request->input('customer'), Normalizer::FORM_D));
            $result = $this->userModel
            ->whereRaw('lower(unaccent(first_name)) ilike ?', ['%' . $normalizedOwner . '%'])
            ->whereHas('recurringOrders', function ($query) use ($request) {
                $query->whereNull('deleted_at');
                $query->where('is_archived', false);
                $query->where('type', $request->input('type') ?? 1);

            })
            ->with(['recurringOrders' => function ($q) use ($request) {
                    $q->where('type', $request->input('type') ?? 1);
            }, 'co', 'recurringOrders.recurringService:id,name,image'])
            ->get();
        }

        if($request->has('customer') && $request->has(['start_date', 'end_date']) || $request->has('status') || $request->has('service')){
            $filtre = [];
            $resultFilter = $this->filtreWithoutCustomer($request,$this->userModel);
            $normalizedOwner = mb_strtolower(normalizer_normalize($request->input('customer'), Normalizer::FORM_D));
            foreach ($resultFilter as $key => $user) {
                $first_name = strtolower($user->first_name);
                $last_name =  strtolower($user->last_name);


                $searchLastName = strstr($last_name,$normalizedOwner);
                $searchFirstName = strstr($first_name,$normalizedOwner);


                if($searchLastName || $searchFirstName){
                    array_push($filtre,$user);
                }

            }
            $result = $filtre;
        }



        return $result;


    }


    public function filtreWithoutCustomer($request,$userModel){
        $resultFiltre = collect();
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        // ======================================== Refacoring code

         // ======================================== Refacoring once
          //============== Status once
          if($request->has('status') && !$request->has(['service','start_date', 'end_date'])){

            $resultFiltre = $userModel
            ->whereHas('recurringOrders', function ($query) use ($request) {
                $query->whereNull('deleted_at');
                $query->where('is_archived', false);
                $query->where('type', $request->input('type') ?? 1);
                $query->where('status', $request->input('status'));
            })
            ->with(['recurringOrders' => function ($q) use ($request) {
                    $q->where('type', $request->input('type') ?? 1);
            }, 'co', 'recurringOrders.recurringService:id,name,image'])
            ->get();

       }

       // Service once
       if($request->has(['service']) && !$request->has(['status','start_date', 'end_date'])){

            $filtre = [];
            $service = $request->input('service');
            $resultFiltre = $userModel
            ->whereHas('recurringOrders', function ($query) use ($request) {
                $query->whereNull('deleted_at');
                $query->where('is_archived', false);
                $query->where('type', $request->input('type') ?? 1);
            })
            ->with(['recurringOrders' => function ($q) use ($request) {
                    $q->where('type', $request->input('type') ?? 1);
            }, 'co', 'recurringOrders.recurringService:id,name,image'])
            ->get();

            foreach ($resultFiltre as $key => $user) {
                foreach ($user->recurringOrders as $key => $order) {
                    if($order->recurringService->id == $service){
                        array_push($filtre,$user);

                    }
            }
            }

            $resultFiltre = $filtre;


       }


        // Date  once
        if($request->has(['start_date', 'end_date']) && !$request->has(['status','service'])){

            $resultFiltre = $userModel
            ->whereHas('recurringOrders', function ($query) use ($request){
                $query->whereNull('deleted_at');
                $query->where('is_archived', false);
                $query->where('type', $request->input('type') ?? 1);
                $query->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')]);
            })
            ->with(['recurringOrders' => function ($q) use ($request) {
                    $q->where('type', $request->input('type') ?? 1);
            }, 'co', 'recurringOrders.recurringService:id,name,image'])
            ->get();

        }

       // ======================================== Refacoring once

          // Status and service
          if($request->has(['service','status']) && !$request->has(['start_date', 'end_date'])){

            $filtre = [];
            $service = $request->input('service');
            $status =  $request->input('status');
            $resultFiltre = $userModel
            ->whereHas('recurringOrders', function ($query) use ($request,$service,$status) {
                $query->whereNull('deleted_at');
                $query->where('is_archived', false);
                $query->where('type', $request->input('type') ?? 1);
                $query->where('status', $status);
            })
            ->with(['recurringOrders' => function ($q) use ($request) {
                    $q->where('type', $request->input('type') ?? 1);
            }, 'co', 'recurringOrders.recurringService:id,name,image'])
            ->get();

            foreach ($resultFiltre as $key => $user) {
                foreach ($user->recurringOrders as $key => $order) {
                    $output->writeln($order->recurringService->id);
                    if($order->recurringService->id == $service){
                        array_push($filtre,$user);

                    }
               }
            }

            $resultFiltre = $filtre;


        }


        if($request->has(['service','status','start_date', 'end_date'])){

            $filtre = [];
            $service = $request->input('service');
            $status =  $request->input('status');

            $resultFiltre = $userModel
            ->whereHas('recurringOrders', function ($query) use ($request,$status) {
                $query->whereNull('deleted_at');
                $query->where('is_archived', false);
                $query->where('type', $request->input('type') ?? 1);
                $query->where('status', $status);
                $query->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')]);
            })
            ->with(['recurringOrders' => function ($q) use ($request){
                    $q->where('type', $request->input('type') ?? 1);
            }, 'co', 'recurringOrders.recurringService:id,name,image'])
            ->get();

            foreach ($resultFiltre as $key => $user) {
                foreach ($user->recurringOrders as $key => $order) {
                    if($order->recurringService->id == $service){
                        array_push($filtre,$user);

                    }
               }
            }

            $resultFiltre = $filtre;


        }



        if($request->has(['start_date', 'end_date','status']) && !$request->has(['service'])){
            $status =  $request->input('status');

            $resultFiltre = $userModel
            ->whereHas('recurringOrders', function ($query) use ($request,$status) {
                $query->whereNull('deleted_at');
                $query->where('is_archived', false);
                $query->where('type', $request->input('type') ?? 1);
                $query->where('status', $status);
                $query->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')]);
            })
            ->with(['recurringOrders' => function ($q) use ($request) {
                    $q->where('type', $request->input('type') ?? 1);
            }, 'co', 'recurringOrders.recurringService:id,name,image'])
            ->get();

        }


        return $resultFiltre;

        }



        public function assignPackageToStaff(User $staff, User $customer)
{
    if ($staff->hasAnyRole(['CO', 'supervisor'])) {

        if ($staff->hasRole('CO')) {
            if (!$staff->customers()->where('customer_id', $customer->id)->exists()) {
                $staff->customers()->attach($customer->id, [
                    'status' => true,
                    'assign_at' => now(),
                    'terminate_at' => null,
                ]);
            }
        } else if ($staff->hasRole('supervisor')) {
            $customer->referred_by = $staff->id;
            $customer->save();
        }

        return $this->getUserOrders($customer)->toArray();

    }


}

    public function getPackageBy($column, $value)
    {
        $package =  $this->model->where($column, $value)->get();
        return $package;
    }






}
