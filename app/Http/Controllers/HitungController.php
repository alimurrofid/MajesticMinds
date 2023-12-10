<?php

namespace App\Http\Controllers;

use App\Models\criteria;
use App\Models\penilaian;
use App\Models\alternatif;
use Illuminate\Http\Request;

class HitungController extends Controller
{
    public function index()
    {
        $criteria = criteria::all();
        $alternatif = alternatif::all();
        $penilaian = penilaian::with(['alternatif', 'criteria'])->get();

        //normalisasi waspas
        $minMax = [];
        $maxXi = [];
        $minXi = [];
        $ranking = [];

        $desiredDecimalPlaces = 3; // Ganti dengan jumlah desimal yang diinginkan

        foreach ($criteria as $c) {
            foreach ($penilaian as $p) {
                if ($c->id == $p->id_criteria) {
                    $minMax[$c->id][] = $p->nilai;
                }
            }

            // Check if there are values for the current $kriteriaId
            if (!empty($minMax[$c->id])) {
                $values = $minMax[$c->id];
                $maxXi[$c->id] = max($values);
                $minXi[$c->id] = min($values);

                // Normalisasi and Weighting
                foreach ($values as $value) {
                    // Initialize $normalizedValue correctly using $value
                    $normalizedValue = 0;

                    if ($c->criteria_type == "Benefit") {
                        $normalizedValue = $value / $maxXi[$c->id];
                    } else {
                        $normalizedValue = $minXi[$c->id] / $value;
                    }

                    // Round $normalizedValue to the desired number of decimal places
                    $normalizedValue = round($normalizedValue, $desiredDecimalPlaces);

                    // Store $normalizedValue in $tij
                    $tij[$c->id][] = $normalizedValue;
                }
            }
        }

        //menghitung Q waspas
        //langkah pertama
        $Q1 = [];
        foreach ($alternatif as $a) {
            $q1 = 0;
            foreach ($criteria as $c) {
                $weight = $c->weight;
                $normalizedValue = $tij[$c->id][$a->id - 1];
                $q1 += $weight * $normalizedValue;
            }
            $Q1[] = $q1 * 0.5;
        }

        //langkah kedua
        $Q2 = [];
        foreach ($alternatif as $a) {
            $q2 = 0;
            foreach ($criteria as $c) {
                $weight = $c->weight;
                $normalizedValue = $tij[$c->id][$a->id - 1];
                //pangkatkan nilai normalisasi dengan nilai bobot
                $q2 *= pow($normalizedValue, $weight);
            }
            $Q2[] = $q2 * 0.5;
        }

        //menjumlahkan Q1 dan Q2
        $Q = [];
        foreach ($alternatif as $a) {
            $q = $Q1[$a->id - 1] + $Q2[$a->id - 1];
            $Q[] = $q;
        }

        //perangkingan urutkan dari terbesar ke terkecil
        $ranking = [];
        foreach ($alternatif as $a) {
            $ranking[$a->id] = $Q[$a->id - 1];
        }
        arsort($ranking);

        // Assign new rankings
        $newRankings = [];
        $rankingValue = 1;
        foreach ($ranking as $alternatifId => $totalRanking) {
            $newRankings[$alternatifId] = $rankingValue;
            $rankingValue++;
        }

        return view('dashboard.hitung', [
            'criteria' => $criteria,
            'alternatif' => $alternatif,
            'penilaian' => $penilaian,
            'normalisasi' => $tij,
            'q_value' => $Q,
            'ranking' => $ranking,
            'rank' => $newRankings,
        ]);
    }
}
