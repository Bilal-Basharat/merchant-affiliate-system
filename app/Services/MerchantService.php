<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;

class MerchantService
{
    /**
     * Register a new user and associated merchant.
     * Hint: Use the password field to store the API key.
     * Hint: Be sure to set the correct user type according to the constants in the User model.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
    public function register(array $data): Merchant
    {
        //create the user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['api_key'],
            'type' => User::TYPE_MERCHANT
        ]);

        //create and return the merchant associated
        return $user->merchant()->create([
            // 'user_id' => $user->id,
            'domain' => $data['domain'],
            'display_name' => $data['name'],
            'turn_customers_into_affiliates' => true,
            'default_commission_rate' => 0.1
        ]);
    }

    /**
     * Update the user
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data)
    {
        //update the user inf0
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['api_key']
        ]);

        //update the merchant info.
        $user->merchant->update([
            'domain' => $data['domain'],
            'display_name' => $data['name']
        ]);
    }

    /**
     * Find a merchant by their email.
     * Hint: You'll need to look up the user first.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        //find by email
        $user = User::where('email', $email)->first();

        //return the merchant
        return $user?->merchant;
    }

    /**
     * Pay out all of an affiliate's orders.
     * Hint: You'll need to dispatch the job for each unpaid order.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate)
    {
        //dispatch the job
        $unpaidOrders = Order::where('affiliate_id', $affiliate->id)->where('payout_status', Order::STATUS_UNPAID)
            ->get();

        //dispatch each and every unpaid order
        foreach ($unpaidOrders as $order) {
            PayoutOrderJob::dispatch($order);
        }
    }
}
