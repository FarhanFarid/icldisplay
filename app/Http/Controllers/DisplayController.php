<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Inertia\Inertia;
use Carbon\Carbon;
use Log;


class DisplayController extends Controller
{
    public function index(Request $request){

        return Inertia::render('Display/Index');

    }

    public function getIclListing(Request $request){

        try
        { 
            $uri = env('CATHLIST');

            $date = Carbon::now()->format('j/n/Y');

            $url = $uri . '?' . http_build_query([
                'dateFrom' => $date,
                'dateTo' => $date,
                'mrn'     => '',
                'conDr'   => '',
                'regDr'   => '',
                'sts'     => '1',
                'proc'    => '',
                'ic'      => '',
                'cath'    => '',
                'lab'     => '',
            ]);

            $client = new \GuzzleHttp\Client(['defaults' => ['verify' => false]]);

            $response = $client->request('GET', $url);

            $statusCode = $response->getStatusCode();
            $content = json_decode($response->getBody(), true);

            $filtered = array_filter($content, function ($item) {
                return isset($item['status']) && strtolower($item['status']) === 'initial';
            });

            return $response = response()->json(
                [
                    'status'  => 'success',
                    'data' => array_values($filtered),
                ], 200
            );

        }
        catch (\Exception $e){
            Log::error($e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            );

            $response = response()->json(
                [
                    'status'  => 'failed',
                    'message' => 'Internal error happened. Try again'
                ], 200
            );

            return $response;
        }
    }
}
