<?php

namespace Core\Modules\Auth;

use Core\Modules\Auth\Models\Sponsorship;
use Core\Modules\User\Models\User;

class AuthRepository
{
    protected readonly Sponsorship $sponsorshipModel;

    public function __construct(Sponsorship $sponsorshipModel)
    {
        $this->sponsorshipModel = $sponsorshipModel;
    }

    public function storeSponsorship(array $data, $user): Sponsorship
    {
        $sponsorship = $this->sponsorshipModel->make($data);
        $sponsorship->user()->associate($user);
        $sponsorship->save();
        return $sponsorship;
    }
    public function userSponsorship(User $user): void
    {
        $fullNameFirstOption = trim($user->last_name) . " " . trim($user->first_name);
        $fullNameSecondOption = trim($user->first_name) . " " . trim($user->last_name);
        $sponsorship =  $this->findSponsorshipBy('email', $user->email);
        if (is_null($sponsorship)) {
            $sponsorship =  $this->findSponsorshipBy('full_name', $fullNameFirstOption);
            if (is_null($sponsorship)) {
                $sponsorship =  $this->findSponsorshipBy('full_name', $fullNameSecondOption);
            }
        }
        if (!is_null($sponsorship)) {
            $user->referredBy()->associate($sponsorship->user);
            $user->save();
        }
    }

    public function findSponsorshipBy($column, $value)
    {
        return $this->sponsorshipModel->with('user')->where($column, $value)->first();
    }



}
