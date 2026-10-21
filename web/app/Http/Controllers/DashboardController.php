<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function index(DashboardService $data)
    {
        $halaman = [
            'kpi' => $data->kpi(),
            'kelas' => $data->tutupanKelas(),
            'tutupan' => $data->tutupanLahan(),
            'deforestasi' => $data->deforestasi(),
            'karhutla' => $data->karhutla(),
            'kayu' => $data->produksiKayu(),
            'indeks' => $data->indeksGabungan(),
            'benangMerah' => $data->benangMerahProvinsi(),
            'apiHutan' => $data->kebakaranVsDeforestasi(),
            'validasi' => $data->validasiSilang(),
            'sebaran' => $data->sebaran(),
            'integrasi' => $data->statusIntegrasi(),
        ];

        // Data yang dikirim ke JavaScript (Chart.js & peta sebaran)
        $halaman['grafik'] = collect($halaman)->only([
            'kelas', 'tutupan', 'deforestasi', 'karhutla', 'kayu', 'indeks', 'apiHutan', 'benangMerah', 'sebaran',
        ])->put('deret', $halaman['kpi']['deret']);

        return view('dashboard.index', $halaman);
    }
}
