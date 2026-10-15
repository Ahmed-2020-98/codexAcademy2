<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\View\View;

/** Public verification page; doubles as the printable certificate. */
class CertificateController extends Controller
{
    public function show(Certificate $certificate): View
    {
        return view('site.certificate', ['certificate' => $certificate->load('user', 'course.instructor', 'course.department')]);
    }
}
