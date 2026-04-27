<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WfpFormController extends Controller
{
    public function show(Request $request, string $orderId): \Illuminate\View\View
    {
        $params = cache()->pull('wfp:form:' . $orderId);

        if (! $params) {
            abort(410, 'Посилання на оплату застаріло. Спробуйте ще раз.');
        }

        return view('payments.wfp-form', [
            'actionUrl' => 'https://secure.wayforpay.com/pay',
            'params'    => $params,
        ]);
    }
}
