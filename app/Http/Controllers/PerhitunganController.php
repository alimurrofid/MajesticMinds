<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\penilaian;
use App\Models\alternatif;
use App\Models\criteria;

class PerhitunganController extends Controller
{
    public function assessmentsFilled()
    {
        // Check if assessments are filled (adjust this based on your data structure)
         $assessments= Penilaian::all(); // Replace it with your actual assessment model

        return $assessments->isNotEmpty();
    }
    public function index()
    {
        if (!$this->assessmentsFilled()) {
            // Redirect back or to a specific page with a message
            return redirect()->route('penilaian.index')->with('error', 'Please fill out the assessments before viewing the ranking.');
        }
        $criteria = criteria::all();
        $alternatif = alternatif::all();
        $penilaian = Penilaian::with(['alternatif', 'criteria'])->get();

        $minMax = [];
        $maxXi = [];
        $minXi = [];
        $tij = [];
        $vij = [];
        $g = [];
        $q = [];
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

                // Initialize $tij, $vij, $g, $q, and $ranking for the current $kriteriaId
                $tij[$c->id] = [];
                $vij[$c->id] = [];
                $g[$c->id] = 1;
                $q[$c->id] = [];

                // Normalisasi and Weighting
                foreach ($values as $value) {
                    // Initialize $normalizedValue correctly using $value
                    $normalizedValue = 0;

                    if ($c->criteria_type == "Benefit") {
                        $normalizedValue = ($maxXi[$c->id] - $minXi[$c->id]) != 0 ? ($value - $minXi[$c->id]) / ($maxXi[$c->id] - $minXi[$c->id]) : 0;
                    } elseif ($c->criteria_type == "Cost") {
                        $normalizedValue = ($maxXi[$c->id] - $minXi[$c->id]) != 0 ? ($maxXi[$c->id] - $value) / ($maxXi[$c->id] - $minXi[$c->id]) : 0;
                    }

                    // Get the criteria weight from the criteria model
                    $criteriaWeight = $c->weight;

                    // Calculate the weighted value
                    $weightedValue = ($criteriaWeight * $normalizedValue) + $criteriaWeight;
                    $g[$c->id] *= $weightedValue;

                    // Format the normalized value and weighted value with the desired decimal places
                    $formattedNormalizedValue = number_format($normalizedValue, $desiredDecimalPlaces);
                    $formattedWeightedValue = number_format($weightedValue, $desiredDecimalPlaces);

                    $tij[$c->id][] = $formattedNormalizedValue;
                    $vij[$c->id][] = $formattedWeightedValue;
                }

                // Calculate the prediction boundary (g)
                $g[$c->id] = pow($g[$c->id], 1 / count($alternatif));
                $g[$c->id] = number_format($g[$c->id], $desiredDecimalPlaces);

                // Calculate the distance matrix (q)
                $q[$c->id] = array_map(function ($vijValue) use ($g, $c) {
                    $desiredDecimalPlaces = 3;
                    return number_format($vijValue - $g[$c->id], $desiredDecimalPlaces);
                }, $vij[$c->id]);
            }
        }

        // Calculate the ranking by summing up values per alternative
        foreach ($alternatif as $a) {
            $totalValue = 0;

            foreach ($criteria as $c) {
                $totalValue += $q[$c->id][$a->id - 1]; // Adjust index since the array starts from 0
            }

            // Format the total value with the desired decimal places
            $formattedTotalValue = number_format($totalValue, $desiredDecimalPlaces);

            $ranking[$a->id] = $formattedTotalValue;
        }
         // Calculate ranking
        $rankings = [];
        foreach ($alternatif as $a) {
            $totalRanking = 0;
            foreach ($criteria as $c) {
                    $totalRanking += $q[$c->id][$a->id - 1];
            }
            $rankings[$a->id] = $totalRanking;
        }

        // Sort rankings in descending order
        arsort($rankings);

        // Assign new rankings
        $newRankings = [];
        $rankingValue = 1;
        foreach ($rankings as $alternatifId => $totalRanking) {
            $newRankings[$alternatifId] = $rankingValue;
            $rankingValue++;
        }
       // dd($tij, $vij, $q, $ranking);

        // Kirim data ke view
        return view('dashboard.perhitungan', [
            'normalisasi' => $tij,
            'weighted' => $vij,
            'predictionBoundary' => $g,
            'distanceMatrix' => $q,
            'ranking' => $ranking,
            'rank'=> $newRankings,
            'criteria' => $criteria,
            'alternatif' => $alternatif,
            'penilaian' => $penilaian,
        ]
    );
    }




}
