<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;

class FiscalCodeCalculator
{
    private const MONTH_CODES = [
        1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'H',
        7 => 'L', 8 => 'M', 9 => 'P', 10 => 'R', 11 => 'S', 12 => 'T',
    ];

    private const ODD_CHARS = [
        '0' => 1, '1' => 0, '2' => 5, '3' => 7, '4' => 9, '5' => 13,
        '6' => 15, '7' => 17, '8' => 19, '9' => 21, 'A' => 1, 'B' => 0,
        'C' => 5, 'D' => 7, 'E' => 9, 'F' => 13, 'G' => 15, 'H' => 17,
        'I' => 19, 'J' => 21, 'K' => 2, 'L' => 4, 'M' => 18, 'N' => 20,
        'O' => 11, 'P' => 3, 'Q' => 6, 'R' => 8, 'S' => 12, 'T' => 14,
        'U' => 16, 'V' => 10, 'W' => 22, 'X' => 25, 'Y' => 24, 'Z' => 23,
    ];

    private const EVEN_CHARS = [
        '0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5,
        '6' => 6, '7' => 7, '8' => 8, '9' => 9, 'A' => 0, 'B' => 1,
        'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7,
        'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13,
        'O' => 14, 'P' => 15, 'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19,
        'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23, 'Y' => 24, 'Z' => 25,
    ];

    private const CHECK_CHARS = [
        0 => 'A', 1 => 'B', 2 => 'C', 3 => 'D', 4 => 'E', 5 => 'F',
        6 => 'G', 7 => 'H', 8 => 'I', 9 => 'J', 10 => 'K', 11 => 'L',
        12 => 'M', 13 => 'N', 14 => 'O', 15 => 'P', 16 => 'Q', 17 => 'R',
        18 => 'S', 19 => 'T', 20 => 'U', 21 => 'V', 22 => 'W', 23 => 'X',
        24 => 'Y', 25 => 'Z',
    ];

    public function calculate(
        string $surname,
        string $name,
        string $birthDate,
        string $gender,
        string $birthPlace
    ): string {
        $surname = $this->normalizeName($surname);
        $name = $this->normalizeName($name);

        $surnameCode = $this->calculateNameCode($surname);
        $nameCode = $this->calculateNameCode($name, true);
        $dateCode = $this->calculateDateCode($birthDate, $gender);
        $placeCode = strtoupper($birthPlace);

        $fiscalCode = $surnameCode.$nameCode.$dateCode.$placeCode;
        $checkChar = $this->calculateCheckCharacter($fiscalCode);

        return $fiscalCode.$checkChar;
    }

    private function normalizeName(string $name): string
    {
        $name = strtoupper($name);
        $name = preg_replace('/[^A-Z]/', '', $name);

        return $name;
    }

    private function calculateNameCode(string $name, bool $isFirstName = false): string
    {
        $consonants = $this->extractConsonants($name);
        $vowels = $this->extractVowels($name);

        // Special rule for first names with 4+ consonants
        if ($isFirstName && strlen($consonants) >= 4) {
            return $consonants[0].$consonants[2].$consonants[3];
        }

        // Standard rule: use consonants first, then vowels
        $code = $consonants.$vowels.'XXX';

        return substr($code, 0, 3);
    }

    private function extractConsonants(string $text): string
    {
        preg_match_all('/[BCDFGHJKLMNPQRSTVWXYZ]/', $text, $matches);

        return implode('', $matches[0]);
    }

    private function extractVowels(string $text): string
    {
        preg_match_all('/[AEIOU]/', $text, $matches);

        return implode('', $matches[0]);
    }

    private function calculateDateCode(string $birthDate, string $gender): string
    {
        $date = Carbon::parse($birthDate);

        $year = substr((string) $date->year, -2);
        $month = self::MONTH_CODES[$date->month];
        $day = $date->day;

        // For females, add 40 to the day
        if (strtoupper($gender) === 'F' || strtoupper($gender) === 'FEMALE') {
            $day += 40;
        }

        return sprintf('%s%s%02d', $year, $month, $day);
    }

    private function calculateCheckCharacter(string $code): string
    {
        $sum = 0;

        for ($i = 0; $i < 15; $i++) {
            $char = $code[$i];
            $sum += ($i % 2 === 0) ? self::ODD_CHARS[$char] : self::EVEN_CHARS[$char];
        }

        $remainder = $sum % 26;

        return self::CHECK_CHARS[$remainder];
    }
}
