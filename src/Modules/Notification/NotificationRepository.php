<?php

namespace Core\Modules\Notification;

use Core\Modules\Notification\Models\Notification;
use Core\Modules\Notification\Models\UserNotification;
use Core\Utils\BaseRepository;
use Illuminate\Http\Request;
use Normalizer;

class NotificationRepository extends BaseRepository
{
    protected  $model;
    protected $userNotificationRepository;
    public function __construct(Notification $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }

    public function searchNotification(Request $request)
    {
        $query = "";

        // Filtrage par titre
        if ($request->has('title') && !$request->has(['status', 'start_date', 'end_date'])) {
            $filter = $request->input('title');
            $normalizedFilter = mb_strtolower(normalizer_normalize($filter, Normalizer::FORM_D));

            $query = $this->model->whereRaw('lower(unaccent(title)) ilike ?', ['%' . $normalizedFilter . '%']);
        }


        if ($request->has('status') && !$request->has(['title', 'start_date', 'end_date'])) {
            $query = $this->model->where('status', $request->input('status'));
        }


        if ($request->has(['start_date', 'end_date']) && !$request->has(['title', 'status'])) {
            $query = $this->model
             ->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')]);
        }


        if ($request->has(['start_date', 'end_date', 'status']) && !$request->has(['title'])) {
            $status =$request->input('status');
            $query = $this->model
            ->where('status', $status)
            ->whereBetween($status == 1 ? 'date_sent' : 'created_at', [$request->input('start_date'), $request->input('end_date')]);
        }


        $notifications = $query->with(['createdBy:id,first_name,last_name'])->get();

        return $notifications;
    }


    // User notification functions

    public function createUserNotification(array $data)
    {
        return UserNotification::create($data);
    }

    public function getUserNotificationsById($userId)
    {

        $notificationsCollections = UserNotification::where('user_id', $userId)
        ->orderByDesc('created_at')
        ->get()
        ->groupBy('created_at');
        $notificationsCollections = $notificationsCollections->chunk(10);
        return $notificationsCollections;

    }


}
