<?php

namespace Core\Modules\PunctualOrder\Repositories;

use Core\Modules\Professional\Models\Professional;
use Core\Modules\Professional\Models\ProfessionalPunctualServices;
use Core\Modules\PunctualOrder\Models\Offer;
use Core\Modules\PunctualOrder\Models\PunctualOrder;
use Core\Utils\BaseRepository;
use Illuminate\Support\Facades\Auth;

class OffersRepository extends BaseRepository
{
    private $offerPunctualOrderModel;
    private $professionalPunctualServicesModel;
    private $professionalModel;


    public function __construct(
        Offer $offerPunctualOrderModel,
        ProfessionalPunctualServices $professionalPunctualServicesModel,
        Professional $professionalModel
    ) {
        parent::__construct($offerPunctualOrderModel);
        $this->offerPunctualOrderModel = $offerPunctualOrderModel;
        $this->professionalModel = $professionalModel;
        $this->professionalPunctualServicesModel = $professionalPunctualServicesModel;
    }

    public function getAllOffers($orderId)
    {
        return $this->offerPunctualOrderModel
            ->where('order_id', $orderId)
            ->orderBy('created_at', 'desc')->get();
    }

    public function getOffersForCurrentUser($orderId)
    {
        return $this->offerPunctualOrderModel
            ->join('punctual_orders', 'offers.order_id', '=', 'punctual_orders.id')
            ->where('offers.order_id', $orderId)
            ->where('punctual_orders.user_id', Auth::id())
            ->orderBy('created_at', 'desc')->get();
    }

    public function  getPunctualServicePro(PunctualOrder $order, Professional $pro)
    {
        return $this->professionalPunctualServicesModel
            ->whereNull('deleted_at')
            ->where('professional_id', $pro->id)
            ->where('punctual_service_id', $order->service_id)
            ->exists();
    }

    public function professionalAlreadyAssigned(PunctualOrder $order, Professional $pro)
    {
        return $this->offerPunctualOrderModel
            ->whereNull('deleted_at')
            ->where('order_id', $order->id)
            ->where('professional_id', $pro->id)
            ->exists();
    }

    public function destroyOffer(Offer $offer)
    {
        return Offer::where('order_id', $offer->order_id)
            ->whereNull('deleted_at')->get();
    }


    public function getProNote(Professional $pro)
    {
        $offers = $this->offerPunctualOrderModel->with(['orders.note','orders.service:id,name'])
            ->whereNull('deleted_at')
            ->where('professional_id', $pro->id)
            ->where('status', 2)
            ->get();
        $metaData = [];
        $notes = [];
        $sumNote = 0;
        $user = '';
        $countNotes = 0;
        $metaData['job'] = count($offers);
        $metaData['comment'] = [];
        foreach ($offers as $value) {
            if (!(is_null($value->orders->note))) {
                $value->orders->note['user'] = $value->orders->user;
                $value->orders->note['service'] = $value->orders->service;
                $notes[] = $value->orders->note;
                $sumNote += $value->orders->note->note;
                $countNotes += 1;
            }
        }
        $averageNote = $countNotes > 0 ? $sumNote / $countNotes : 0;
        $metaData['comment'] = $notes;
        $averageNoteFormated = round($averageNote,1);
        $metaData['avarageNote'] = $averageNoteFormated;
        return $metaData;
    }



    public function getProInfoJob(PunctualOrder $order)
    {
        $query = $order->offers;
        $pro =  $this->offerPunctualOrderModel->with('orders')
            ->whereNull('deleted_at')
            ->where('status', 2)
            ->whereIn('professional_id',  $query->pluck('professional_id'))
            ->get();
        return $pro;
    }
}
