<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use \Illuminate\Support\Str;
class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {
    }

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        //check if the email already exists
        if (User::where('email', $email)->exists()) {
            throw new AffiliateCreateException('Email already in use');
        }

        //create discount code via API
        $discountCode = $this->apiService->createDiscountCode($merchant)['code'];

        //create the affiliate user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt(Str::random(16)),
            'type' => User::TYPE_AFFILIATE,
        ]);

        //create and return the affiliate
        $affiliate = Affiliate::create([
            'merchant_id' => $merchant->id,
            'user_id' => $user->id,
            'commission_rate' => $commissionRate,
            'discount_code' => $discountCode
        ]);

        Mail::to($user)->send(new AffiliateCreated($affiliate));

        return $affiliate;
    }
}
