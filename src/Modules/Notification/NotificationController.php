<?php

namespace Core\Modules\Notification;

use Core\ExternalServices\PushNotificationService;
use Core\Modules\Notification\Mail\NewNotification;
use Core\Modules\Notification\Models\Notification;
use Core\Modules\Notification\Requests\PushNotificationRequest;
use Core\Modules\Notification\Requests\UpdateNotificationRequest;
use Core\Modules\User\Models\User;
use Core\Modules\User\UserRepository;
use Core\Utils\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class NotificationController extends Controller
{
    protected PushNotificationService $pushNotificationService;
    protected NotificationRepository $notificationRepository;
    protected UserRepository $userRepository;

    public function __construct(PushNotificationService $pushNotificationService, NotificationRepository $notificationRepository, UserRepository $userRepository)
    {
        $this->pushNotificationService = $pushNotificationService;
        $this->notificationRepository = $notificationRepository;
        $this->userRepository = $userRepository;
    }

    public function index(Request $request)
    {
        if ($request->query->count() == 0 || $request->has('page')) {
            $pushNotifications = $this->notificationRepository->all(relations: ['createdBy'], paginate: true);
            $pushNotifications->transform(function ($pushNotification) {
                $pushNotification->image = !is_null($pushNotification->image) ? Storage::temporaryUrl($pushNotification->image, now()->addDays(7)) : '';
                return $pushNotification;
            });
        } else {
            $pushNotifications = $this->notificationRepository->searchNotification($request);
            foreach ($pushNotifications as $pushNotification) {
                $pushNotification->image = !is_null($pushNotification->image) ? Storage::temporaryUrl($pushNotification->image, now()->addDays(7)) : '';
            }
        }
        $response["message"] = "Liste des notifications push crées";
        $response["data"] = $pushNotifications;
        return response($response, 200);
    }


    public function store(PushNotificationRequest $request)
    {
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $pushNotificationImage = $request->file('image');
            $pushNotificationImagePath = $pushNotificationImage->store('uploadedFile');
            $data['image'] = $pushNotificationImagePath;
        }

        $pushNotification = $this->notificationRepository->make($data);
        $pushNotification = $this->notificationRepository->associate($pushNotification, ['createdBy' => Auth::user()]);

        if (!Auth::user()->hasRole(['super-admin', 'admin'])) {
            foreach (User::role(['super-admin', 'admin'])->get() as $user) {
                Mail::to($user->email)->send(new NewNotification($user, Auth::user()));
            }
        }
        $pushNotification = $pushNotification->refresh();
        $pushNotification->image = !is_null($pushNotification->image) ? Storage::temporaryUrl($pushNotification->image, now()->addDays(7)) : "";

        $response["data"] = $pushNotification;
        $response["message"] = "Notifications push crée avec succès";
        return response($response, 200);
    }

    public function show(Notification $notification)
    {
        $notification->image = !is_null($notification->image) ? Storage::temporaryUrl($notification->image, now()->addDays(7)) : '';
        $response['data'] = $notification;
        $response['message'] = "Détail d'une notification";
        return response($response, 200);
    }


    public function update(UpdateNotificationRequest $request, Notification $notification)
    {
        $data = $request->validated();
        if (!is_null($request->file('image'))) {
            $pushNotificationImage = $request->file('image');
            $pushNotificationImagePath = $pushNotificationImage->store('uploadedFile');
            $data['image'] = $pushNotificationImagePath;
        }

        $this->notificationRepository->update($notification, $data);
        $notification->image = !is_null($notification->image) ? Storage::temporaryUrl($notification->image,
            now()->addDays(7)) : "";

        $response["message"] = "Notification modifié avec succès";
        $response["data"] = $notification;
        return response($response, 200);
    }

    public function destroy(Notification $notification)
    {
        $this->notificationRepository->delete($notification);
        $response["message"] = "Notification  supprimé avec succès";
        return response($response, 200);
    }

    public function sendNotification(Notification $notification)
    {

        $this->notificationRepository->update($notification, ['status' => 1, "date_sent" => now()]);
        $notification->image = !is_null($notification->image) ? Storage::temporaryUrl($notification->image, now()->addDays(7)) : '';
        $response["message"] = "Notification envoyé avec succès";
        $response["data"] = $notification;
        return response($response, 200);
    }


    public function getUserNotifications(Request $request)
    {
        $user = Auth::user();
        $page = $request->has('page') ? (int)$request->input('page') : 2;
        $notifications = $this->notificationRepository->getUserNotificationsById($user->id);
        $totalPage = count($notifications);
        $response["totalPages"] = $totalPage;
        if($page > $totalPage){
            $response["data"] = [];
            $response["message"] = "Page inexistante";

        }else{
            $response["data"] = $notifications[$page-1];
            $response["currentPage"] = $page;
            if($page < $totalPage){
                $response["nextPage"] = $page + 1;
            }
            $response["previousPage"] = $page - 1;
            $response["message"] = "Liste des notifications de l'utilisateur";
        }

        return response($response, 200);


    }
}
