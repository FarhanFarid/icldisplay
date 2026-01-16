<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Inertia\Inertia;
use Carbon\Carbon;
use Log;

use App\Models\ProcedureIcl;

class DisplayController extends Controller
{
    public function index(Request $request){

        return Inertia::render('Display/Index');

    }

    public function getIclListing(Request $request)
    {
        try {
            $uri = env('CATHLIST');

            $date = Carbon::now()->format('j/n/Y');

            $url = $uri . '?' . http_build_query([
                'dateFrom' => $date,
                'dateTo'   => $date,
                'mrn'      => '',
                'conDr'    => '',
                'regDr'    => '',
                'proc'     => '',
                'ic'       => '',
                'cath'     => '',
                'lab'      => '',
            ]);

            $client = new \GuzzleHttp\Client(['defaults' => ['verify' => false]]);
            $response = $client->request('GET', $url);

            $content = json_decode($response->getBody(), true);

            /**
             * 1. Get all cathlist IDs from procedure table
             */
            $procedureIds = ProcedureIcl::where('patient_status', 1)
                ->where('status_id', 2)
                ->pluck('id_cathlist')
                ->toArray();

            /**
             * 2. Filter API data by matching rowID
             */
            $filtered = array_filter($content, function ($item) use ($procedureIds) {
                return isset($item['rowID']) && in_array($item['rowID'], $procedureIds);
            });

            $filtered = array_values($filtered);

            /**
             * 3. Counters
             */
            $counter = [
                'initial' => 0,
                'ongoing' => 0,
                'done'    => 0,
            ];

            // INITIAL & ONGOING → from FILTERED data
            foreach ($filtered as $item) {

                // INITIAL
                if (
                    isset($item['status']) &&
                    strtolower($item['status']) === 'initial'
                ) {
                    $counter['initial']++;
                }

                // ONGOING (has timeIn)
                if ((!empty($item['timeIn']) || !empty($item['lab'])) && strtolower($item['status']) === 'initial') {
                    $counter['ongoing']++;
                }
            }

            // DONE → from WHOLE API content
            foreach ($content as $item) {
                if (
                    isset($item['status']) &&
                    strtolower($item['status']) === 'done'
                ) {
                    $counter['done']++;
                }
            }

            return response()->json([
                'status'  => 'success',
                'data'    => $filtered,
                'counter' => $counter,
            ], 200);

        } catch (\Exception $e) {
            Log::error($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'status'  => 'failed',
                'message' => 'Internal error happened. Try again'
            ], 200);
        }
    }


}
