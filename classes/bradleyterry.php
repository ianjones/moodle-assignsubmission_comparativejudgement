<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    assignsubmission_comparativejudgement
 * @copyright 2020 Andrew Hancox at Open Source Learning <andrewdchancox@googlemail.com>
 * @copyright 2020 Ian Jones at Loughborough University <I.Jones@lboro.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_comparativejudgement;

/**
 * Pure-PHP Bradley-Terry model fitting via the MM algorithm.
 *
 * Replaces the R/sirt dependency for computing comparative judgement scores.
 */
class bradleyterry {

    /** @var int Maximum MM iterations. */
    const MAXITER = 400;

    /** @var float Convergence tolerance. */
    const TOL = 1e-8;

    /** @var float Floor for pi values to avoid log(0). */
    const PI_FLOOR = 1e-10;

    /**
     * Main entry point: fit Bradley-Terry from CSV and return scores + reliability.
     *
     * @param string $csv  CSV with header JudgeID,Won,Lost,TimeTaken
     * @return object {scores: [int itemid => int score], reliability: float}
     */
    public static function fitfromcsv(string $csv): object {
        $comparisons = self::parsecsv($csv);

        $itemindex = [];
        $wins = self::comparisonstomatrix($comparisons, $itemindex);

        $pi = self::fit($wins);

        $scores = self::scalescores($pi, $itemindex);
        $reliability = self::computereliability($pi, $wins, $itemindex);

        return (object) ['scores' => $scores, 'reliability' => $reliability];
    }

    /**
     * Parse JudgeID,Won,Lost,TimeTaken CSV into an array of comparisons.
     *
     * @param string $csv
     * @return array of ['judge' => int, 'won' => int, 'lost' => int]
     */
    public static function parsecsv(string $csv): array {
        $lines = explode("\n", trim($csv));
        array_shift($lines); // Remove header.

        $comparisons = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = str_getcsv($line);
            if (count($parts) < 3) {
                continue;
            }
            $comparisons[] = [
                'judge' => (int) $parts[0],
                'won'   => (int) $parts[1],
                'lost'  => (int) $parts[2],
            ];
        }
        return $comparisons;
    }

    /**
     * Build an NxN win-count matrix from comparisons.
     *
     * Item IDs are sorted numerically; $itemindex maps item ID => matrix position.
     *
     * @param array $comparisons  Output of parsecsv().
     * @param array &$itemindex   Populated with [itemid => matrix position].
     * @return array  NxN matrix where $wins[$i][$j] = number of times item i beat item j.
     */
    public static function comparisonstomatrix(array $comparisons, array &$itemindex): array {
        // Collect unique item IDs.
        $items = [];
        foreach ($comparisons as $comp) {
            $items[$comp['won']] = true;
            $items[$comp['lost']] = true;
        }
        $itemids = array_keys($items);
        sort($itemids, SORT_NUMERIC);

        // Map item ID to matrix position.
        $itemindex = [];
        foreach ($itemids as $idx => $id) {
            $itemindex[$id] = $idx;
        }

        $n = count($itemids);
        $wins = array_fill(0, $n, array_fill(0, $n, 0));

        foreach ($comparisons as $comp) {
            $w = $itemindex[$comp['won']];
            $l = $itemindex[$comp['lost']];
            $wins[$w][$l]++;
        }

        return $wins;
    }

    /**
     * Fit the Bradley-Terry model using the MM (Minorization-Maximization) algorithm.
     *
     * Returns normalised ability parameters pi (sum to 1).
     *
     * @param array $wins   NxN win matrix from comparisonstomatrix().
     * @param int   $maxiter  Maximum iterations (default MAXITER).
     * @param float $tol      Convergence tolerance (default TOL).
     * @return array  Normalised pi values indexed 0..n-1.
     */
    public static function fit(array $wins, int $maxiter = self::MAXITER, float $tol = self::TOL): array {
        $n = count($wins);
        if ($n === 0) {
            return [];
        }

        // Total comparisons between each pair: n_ij = wins[i][j] + wins[j][i].
        $nij = array_fill(0, $n, array_fill(0, $n, 0));
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $total = $wins[$i][$j] + $wins[$j][$i];
                $nij[$i][$j] = $total;
                $nij[$j][$i] = $total;
            }
        }

        // Total wins for each item.
        $totalwins = array_fill(0, $n, 0);
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $totalwins[$i] += $wins[$i][$j];
            }
        }

        // Initialise pi uniformly.
        $pi = array_fill(0, $n, 1.0 / $n);

        for ($iter = 0; $iter < $maxiter; $iter++) {
            $piold = $pi;

            for ($i = 0; $i < $n; $i++) {
                $denom = 0.0;
                for ($j = 0; $j < $n; $j++) {
                    if ($j === $i || $nij[$i][$j] === 0) {
                        continue;
                    }
                    $denom += $nij[$i][$j] / ($pi[$i] + $pi[$j]);
                }

                if ($denom > 0 && $totalwins[$i] > 0) {
                    $pi[$i] = $totalwins[$i] / $denom;
                } else {
                    $pi[$i] = self::PI_FLOOR;
                }
            }

            // Normalise so pi sums to 1.
            $sum = array_sum($pi);
            if ($sum > 0) {
                for ($i = 0; $i < $n; $i++) {
                    $pi[$i] /= $sum;
                }
            }

            // Check convergence.
            $maxdiff = 0.0;
            for ($i = 0; $i < $n; $i++) {
                $diff = abs($pi[$i] - $piold[$i]);
                if ($diff > $maxdiff) {
                    $maxdiff = $diff;
                }
            }
            if ($maxdiff < $tol) {
                break;
            }
        }

        // Floor any near-zero values.
        for ($i = 0; $i < $n; $i++) {
            if ($pi[$i] < self::PI_FLOOR) {
                $pi[$i] = self::PI_FLOOR;
            }
        }

        return $pi;
    }

    /**
     * Convert pi values to integer scores on a 0-100 scale.
     *
     * log(pi) -> z-score (sample SD, n-1 denominator) -> z*15 + 65 -> round -> cap [0,100].
     *
     * @param array $pi        Normalised ability parameters from fit().
     * @param array $itemindex Map of item ID => matrix position.
     * @return array [int itemid => int score]
     */
    public static function scalescores(array $pi, array $itemindex): array {
        $sd = 15;
        $mean = 65;
        $min = 0;
        $max = 100;

        $n = count($pi);
        if ($n === 0) {
            return [];
        }

        // Compute theta = log(pi).
        $theta = [];
        foreach ($pi as $val) {
            $theta[] = log($val);
        }

        // Mean of theta.
        $thetamean = array_sum($theta) / $n;

        // Sample variance (n-1 denominator).
        $variance = 0.0;
        foreach ($theta as $t) {
            $variance += ($t - $thetamean) ** 2;
        }
        $thetasd = ($n > 1) ? sqrt($variance / ($n - 1)) : 0;

        // Map back to item IDs.
        $flip = array_flip($itemindex);
        $scores = [];

        foreach ($theta as $idx => $t) {
            $z = ($thetasd > 0) ? ($t - $thetamean) / $thetasd : 0;
            $score = (int) round($z * $sd + $mean);
            $score = (int) max($min, min($max, $score));
            $itemid = $flip[$idx];
            $scores[$itemid] = $score;
        }

        return $scores;
    }

    /**
     * Compute separation reliability (SSR) via Fisher information.
     *
     * SSR = Var(theta) / (Var(theta) + mean(SE^2))
     * where SE_i^2 = 1/I(theta_i) and I(theta_i) = sum_j n_ij * p_ij * (1-p_ij).
     *
     * @param array $pi        Normalised ability parameters from fit().
     * @param array $wins      NxN win matrix.
     * @param array $itemindex Map of item ID => matrix position.
     * @return float Reliability capped at 0, rounded to 2 dp.
     */
    public static function computereliability(array $pi, array $wins, array $itemindex): float {
        $n = count($pi);
        if ($n < 2) {
            return 0.0;
        }

        // Total comparisons matrix.
        $nij = array_fill(0, $n, array_fill(0, $n, 0));
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $total = $wins[$i][$j] + $wins[$j][$i];
                $nij[$i][$j] = $total;
                $nij[$j][$i] = $total;
            }
        }

        // Theta = log(pi).
        $theta = [];
        foreach ($pi as $val) {
            $theta[] = log($val);
        }

        // Fisher information for each item.
        $fisherinfo = array_fill(0, $n, 0.0);
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                if ($j === $i || $nij[$i][$j] === 0) {
                    continue;
                }
                $pij = $pi[$i] / ($pi[$i] + $pi[$j]);
                $fisherinfo[$i] += $nij[$i][$j] * $pij * (1.0 - $pij);
            }
        }

        // SE^2 = 1 / I(theta_i).
        $totalsesq = 0.0;
        for ($i = 0; $i < $n; $i++) {
            if ($fisherinfo[$i] > 0) {
                $totalsesq += 1.0 / $fisherinfo[$i];
            }
        }
        $meansesq = $totalsesq / $n;

        // Sample variance of theta (n-1 denominator).
        $thetamean = array_sum($theta) / $n;
        $variance = 0.0;
        foreach ($theta as $t) {
            $variance += ($t - $thetamean) ** 2;
        }
        $vartheta = $variance / ($n - 1);

        // SSR = Var(theta) / (Var(theta) + mean(SE^2)).
        $denom = $vartheta + $meansesq;
        if ($denom > 0) {
            $ssr = $vartheta / $denom;
        } else {
            $ssr = 0.0;
        }

        $ssr = max(0.0, $ssr);
        return round($ssr, 2);
    }
}
