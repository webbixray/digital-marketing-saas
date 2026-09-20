<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show()
    {
        $user = Auth::user();

        return view('auth.two-factor', compact('user'));
    }

    public function enable(Request $request)
    {
        $user = Auth::user();
        $google2fa = new Google2FA;

        $secret = $google2fa->generateSecretKey();
        $user->two_factor_secret = encrypt($secret);
        $user->save();

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $qrCode = new QrCode(
            data: $qrCodeUrl,
            size: 300,
            margin: 10
        );

        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        return response()->json([
            'secret' => $secret,
            'qr_code' => $qrCodeUrl,
            'qr_svg' => $result->getString(),
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $user = Auth::user();
        $google2fa = new Google2FA;

        $secret = decrypt($user->two_factor_secret);
        $valid = $google2fa->verifyKey($secret, $request->code);

        if ($valid) {
            $user->two_factor_enabled = true;
            $user->save();

            return redirect()->route('agency.settings')->with('success', 'Two-factor authentication enabled.');
        }

        return back()->with('error', 'Invalid verification code.');
    }

    public function disable(Request $request)
    {
        $user = Auth::user();
        $user->two_factor_enabled = false;
        $user->two_factor_secret = null;
        $user->save();

        return back()->with('success', 'Two-factor authentication disabled.');
    }
}
